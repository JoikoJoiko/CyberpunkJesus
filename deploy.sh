#!/usr/bin/env bash
set -euo pipefail

source_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
app_dir=/var/www/grekhometr
data_dir=/var/lib/grekhometr
site_ip=185.171.83.215

if [[ $EUID != 0 || "$source_dir" == "$app_dir" || ! -f "$source_dir/artisan" ]]; then
    printf 'Run sudo bash deploy.sh from a separate source checkout.\n' >&2
    exit 1
fi
command -v apt-get >/dev/null || { printf 'Debian or Ubuntu with apt is required.\n' >&2; exit 1; }

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y apache2 libapache2-mod-php php-cli php-curl php-mbstring php-sqlite3 php-xml php-zip composer rsync sqlite3 curl
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", "<") ? 1 : 0);' || {
    printf 'PHP 8.2 or newer is required.\n' >&2
    exit 1
}

install -d -m 0755 "$app_dir"
install -d -o www-data -g www-data -m 0750 "$data_dir" "$app_dir/vendor" "$app_dir/bootstrap/cache" /var/cache/grekhometr-composer
rsync -rlt --exclude='/.git' --include='/.env.example' --exclude='.env*' \
    --exclude='/vendor/' --exclude='/storage/' --exclude='/bootstrap/cache/' \
    --exclude='/database/*.sqlite*' --exclude='/node_modules/' \
    "$source_dir/" "$app_dir/"

install -d -o www-data -g www-data -m 0750 \
    "$app_dir/storage/app/private" "$app_dir/storage/framework/cache/data" \
    "$app_dir/storage/framework/sessions" "$app_dir/storage/framework/views" "$app_dir/storage/logs"
chown -R www-data:www-data "$app_dir/storage"

db_file="$data_dir/database.sqlite"
if [[ -s "$db_file" ]]; then
    umask 077
    backup_file="$data_dir/backup-$(date -u +%Y%m%dT%H%M%S%N).sqlite"
    sqlite3 "$db_file" ".backup '$backup_file'"
    umask 022
else
    touch "$db_file"
fi
chown www-data:www-data "$db_file"
chmod 0600 "$db_file"

cd "$app_dir"
if [[ ! -f .env ]]; then
    install -m 0640 -o root -g www-data .env.example .env
    sed -i \
        -e 's/^APP_ENV=.*/APP_ENV=production/' \
        -e 's/^APP_DEBUG=.*/APP_DEBUG=false/' \
        -e "s|^APP_URL=.*|APP_URL=http://$site_ip|" \
        -e "s|^# DB_DATABASE=.*|DB_DATABASE=$db_file|" \
        -e 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=false/' .env
fi

runuser -u www-data -- env COMPOSER_HOME=/var/cache/grekhometr-composer \
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
runuser -u www-data -- php artisan config:clear
if grep -qx 'APP_KEY=' .env; then
    chmod 0660 .env
    if ! runuser -u www-data -- php artisan key:generate --force; then
        chmod 0640 .env
        exit 1
    fi
    chmod 0640 .env
fi
runuser -u www-data -- php artisan migrate --force
runuser -u www-data -- php artisan optimize

cat > /etc/apache2/sites-available/grekhometr.conf <<EOF
<VirtualHost *:80>
    ServerName $site_ip
    DocumentRoot $app_dir/public
    <Directory "$app_dir/public">
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/grekhometr-error.log
    CustomLog \${APACHE_LOG_DIR}/grekhometr-access.log combined
</VirtualHost>
EOF
apache2ctl -t
a2ensite grekhometr.conf
apache2ctl -t
systemctl enable --now apache2
systemctl reload apache2
curl --fail --silent --show-error --max-time 10 --header "Host: $site_ip" http://127.0.0.1/login >/dev/null

printf '\nSite: http://%s/\n' "$site_ip"
printf 'Create each administrator: cd %s && sudo -u www-data php artisan dossier:user EMAIL --name="NAME"\n' "$app_dir"
