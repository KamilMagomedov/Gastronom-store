<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Ключ'),

                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Текст',
                        'number' => 'Число',
                        'boolean' => 'Да/Нет',
                        'json' => 'JSON',
                    ])
                    ->required()
                    ->default('text')
                    ->label('Тип')
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('value', null)),

                Forms\Components\Select::make('group')
                    ->options([
                        'general' => 'Общие',
                        'app' => 'Приложение',
                        'delivery' => 'Доставка',
                        'social' => 'Соцсети',
                    ])
                    ->required()
                    ->default('general')
                    ->label('Группа'),

                Forms\Components\Hidden::make('value'),

                Forms\Components\TextInput::make('text_value')
                    ->label('Значение')
                    ->required()
                    ->visible(fn (callable $get) => $get('type') === 'text')
                    ->helperText('Введите текстовое значение')
                    ->dehydrateStateUsing(fn ($state) => $state)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('value', $state)),

                Forms\Components\TextInput::make('number_value')
                    ->label('Значение')
                    ->numeric()
                    ->visible(fn (callable $get) => $get('type') === 'number')
                    ->helperText('Введите числовое значение')
                    ->dehydrateStateUsing(fn ($state) => $state)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('value', $state)),

                Forms\Components\Textarea::make('json_value')
                    ->label('Значение')
                    ->required()
                    ->visible(fn (callable $get) => $get('type') === 'json')
                    ->helperText('Введите валидный JSON')
                    ->dehydrateStateUsing(fn ($state) => $state)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('value', $state)),

                Forms\Components\Toggle::make('boolean_value')
                    ->label('Значение')
                    ->visible(fn (callable $get) => $get('type') === 'boolean')
                    ->helperText('Включено/выключено')
                    ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0')
                    ->afterStateUpdated(fn ($state, callable $set) => $set('value', $state ? '1' : '0')),

                Forms\Components\TextInput::make('description')
                    ->label('Описание')
                    ->nullable(),

                Forms\Components\Toggle::make('is_public')
                    ->label('Публичное')
                    ->default(true),
            ]);
    }
}
