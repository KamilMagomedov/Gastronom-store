<?php

namespace App\Enums;

enum ProductAttributeType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';
    case SELECT = 'select';

    public function getLabel(): string
    {
        return match ($this) {
            self::TEXT => 'Текст',
            self::NUMBER => 'Число',
            self::BOOLEAN => 'Да/Нет',
            self::SELECT => 'Выбор из списка',
        };
    }

    public static function getOptions(): array
    {
        return [
            self::TEXT->value => self::TEXT->getLabel(),
            self::NUMBER->value => self::NUMBER->getLabel(),
            self::BOOLEAN->value => self::BOOLEAN->getLabel(),
            self::SELECT->value => self::SELECT->getLabel(),
        ];
    }

    public static function getFilterOptions(): array
    {
        return [
            'text' => 'Текст',
            'number' => 'Число',
            'boolean' => 'Да/Нет',
            'select' => 'Выбор из списка',
        ];
    }
}
