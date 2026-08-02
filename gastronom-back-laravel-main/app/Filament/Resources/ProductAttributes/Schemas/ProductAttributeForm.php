<?php

namespace App\Filament\Resources\ProductAttributes\Schemas;

use App\Enums\ProductAttributeType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductAttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Тип')
                    ->required()
                    ->options(ProductAttributeType::getOptions())
                    ->default(ProductAttributeType::TEXT->value)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('show_options_hint', $state === ProductAttributeType::SELECT->value)),
                Toggle::make('is_required')
                    ->label('Обязательный')
                    ->default(false),
                Toggle::make('is_filterable')
                    ->label('Доступен для фильтрации')
                    ->default(false),
                TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ])
            ->columns(2);
    }
}
