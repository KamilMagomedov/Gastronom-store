<?php

namespace App\Filament\Resources\StaticPages\Schemas;

use App\Enums\StaticPageType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class StaticPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('slug')
                    ->label('Тип страницы')
                    ->options(StaticPageType::class)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('title', StaticPageType::tryFrom($set('slug'))?->getTitle()))
                    ->disabled(fn ($record) => $record !== null || in_array(request('record'), ['terms', 'privacy'])) // Запрещаем менять slug при редактировании и для системных страниц
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Заголовок')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Repeater::make('sections')
                    ->label('Секции страницы')
                    ->relationship()
                    ->schema([
                        TextInput::make('number')
                            ->label('Номер')
                            ->required()
                            ->numeric()
                            ->default(1),

                        TextInput::make('title')
                            ->label('Заголовок секции')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('content')
                            ->label('Содержимое секции')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('important_note')
                            ->label('Важная заметка')
                            ->helperText('Дополнительная важная информация для секции')
                            ->columnSpanFull(),

                        Repeater::make('requirements')
                            ->relationship()
                            ->label('Требования')
                            ->schema([
                                TextInput::make('requirement')
                                    ->label('Требование')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->collapsed()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 'Требование')
                            ->addActionLabel('Добавить требование')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsed()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => "Секция {$state['number']}")
                    ->addActionLabel('Добавить секцию')
                    ->reorderableWithButtons()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true)
                    ->helperText('Страница будет доступна на сайте, если включена'),
            ]);
    }
}
