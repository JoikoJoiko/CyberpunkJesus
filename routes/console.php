<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rules\Password;

Artisan::command('dossier:user {email} {--name=Оператор}', function () {
    $email = mb_strtolower(trim($this->argument('email')));
    $account = [
        'email' => $email,
        'name' => trim($this->option('name')),
        'password' => $this->secret('Введите пароль (от 12 символов)'),
        'password_confirmation' => $this->secret('Повторите пароль'),
    ];

    $validator = validator($account, [
        'email' => ['required', 'email', 'max:254', 'unique:users,email'],
        'name' => ['required', 'string', 'max:100'],
        'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(12)],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    User::create($validator->validated());
    $this->info("Аккаунт {$email} создан.");

    return 0;
})->purpose('Создать аккаунт для закрытого архива');
