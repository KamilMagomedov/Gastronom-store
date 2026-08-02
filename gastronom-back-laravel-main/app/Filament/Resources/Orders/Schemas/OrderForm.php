<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Информация о заказе')
                            ->schema([
                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Клиент'),

                                Select::make('status')
                                    ->options(fn (): array => collect(\App\Enums\OrderStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray())
                                    ->required()
                                    ->default(OrderStatus::PENDING->value)
                                    ->label('Статус заказа'),

                                Select::make('payment_status')
                                    ->options(fn (): array => collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray())
                                    ->required()
                                    ->default(PaymentStatus::PENDING->value)
                                    ->label('Статус оплаты'),

                                Select::make('payment_method_id')
                                    ->options(fn (): array => \App\Models\PaymentMethod::getOptions())
                                    ->label('Способ оплаты'),
                            ])
                            ->columns(1),

                        Section::make('Сумма заказа')
                            ->schema([
                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->label('Подытог'),

                                TextInput::make('shipping_amount')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->default(0)
                                    ->label('Стоимость доставки'),

                                TextInput::make('total_amount')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->required()
                                    ->label('Итого'),
                            ])
                            ->columns(1),

                        Section::make('Доставка')
                            ->schema([
                                Select::make('delivery_method_id')
                                    ->options(fn (): array => \App\Models\DeliveryMethod::getOptions())
                                    ->label('Способ доставки'),

                                TextInput::make('delivery_cost')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->label('Стоимость доставки'),

                                TextInput::make('delivery_phone')
                                    ->tel()
                                    ->label('Телефон доставки'),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('delivery_city')
                                            ->label('Город'),

                                        TextInput::make('delivery_street')
                                            ->label('Улица'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('delivery_building')
                                            ->label('Корпус'),

                                        TextInput::make('delivery_apartment')
                                            ->label('Квартира/офис'),

                                        TextInput::make('delivery_entrance')
                                            ->label('Подъезд'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('delivery_postal_code')
                                            ->label('Почтовый индекс'),

                                        TextInput::make('delivery_floor')
                                            ->label('Этаж'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('delivery_latitude')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->label('Широта'),

                                        TextInput::make('delivery_longitude')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->label('Долгота'),
                                    ]),

                                Textarea::make('delivery_notes')
                                    ->label('Заметки к доставке'),
                            ])
                            ->columns(1),
                    ]),

                Section::make('Дополнительная информация')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Заметки'),

                        Placeholder::make('created_at')
                            ->label('Создан')
                            ->content(fn (?Model $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Обновлен')
                            ->content(fn (?Model $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

                        Placeholder::make('delivered_at')
                            ->label('Доставлен')
                            ->content(fn (?Model $record): string => $record?->delivered_at?->diffForHumans() ?? '-'),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (?Model $record): bool => $record !== null),
            ]);
    }
}
