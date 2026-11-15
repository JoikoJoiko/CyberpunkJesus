<?php

namespace App\Models;

use Illuminate\Validation\Rule;

enum QuestionType: string
{
    case Text = 'text';
    case Paragraph = 'paragraph';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';
    case Choice = 'choice';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Короткий текст',
            self::Paragraph => 'Длинный текст',
            self::Number => 'Число',
            self::Date => 'Дата',
            self::Boolean => 'Да / Нет',
            self::Choice => 'Выбор из списка',
        };
    }

    public function rules(array $options = []): array
    {
        return match ($this) {
            self::Text => ['string', 'max:255'],
            self::Paragraph => ['string', 'max:5000'],
            self::Number => ['string', 'regex:/\A[+-]?\d{1,9}(?:\.\d{1,6})?\z/'],
            self::Date => ['string', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            self::Boolean => ['string', Rule::in(['1', '0'])],
            self::Choice => ['string', Rule::in($options)],
        };
    }

    public function normalize(string $value): string
    {
        if ($this !== self::Number) {
            return $value;
        }

        $value = str_replace(',', '.', $value);
        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = rtrim($fraction, '0');
        $number = (string) abs((int) $integer).($fraction === '' ? '' : '.'.$fraction);

        return str_starts_with($value, '-') && $number !== '0' ? '-'.$number : $number;
    }

    public function answerAttributes(string $value): array
    {
        $value = $this->normalize($value);

        return [
            'value' => $value,
            'numeric_value' => $this === self::Number ? $value : null,
        ];
    }
}
