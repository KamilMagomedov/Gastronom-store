<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label('Клиент')
                    ->sortable()
                    ->searchable()
                    ->limit(30),

                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (Model $record): string => $record->getStatusColor())
                    ->formatStateUsing(fn (Model $record): string => $record->getStatusLabel())
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->color(fn (Model $record): string => $record->getPaymentStatusColor())
                    ->formatStateUsing(fn (Model $record): string => $record->getPaymentStatusLabel())
                    ->sortable(),

                TextColumn::make('deliveryMethod.name')
                    ->label('Доставка')
                    ->formatStateUsing(fn ($record): string => $record->deliveryMethod?->getDisplayName() ?? '-')
                    ->sortable()
                    ->limit(20),

                TextColumn::make('delivery_city')
                    ->label('Город')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delivery_street')
                    ->label('Улица')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delivery_apartment')
                    ->label('Квартира')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delivery_phone')
                    ->label('Телефон')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paymentMethod.name')
                    ->label('Способ оплаты')
                    ->formatStateUsing(fn ($record): string => $record->paymentMethod?->getDisplayName() ?? '-')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => collect(\App\Enums\OrderStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray())
                    ->label('Статус заказа'),

                SelectFilter::make('payment_status')
                    ->options(fn (): array => collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])->toArray())
                    ->label('Статус оплаты'),

                SelectFilter::make('delivery_method_id')
                    ->options(fn (): array => \App\Models\DeliveryMethod::getOptions())
                    ->label('Способ доставки'),

                SelectFilter::make('payment_method_id')
                    ->options(fn (): array => \App\Models\PaymentMethod::getOptions())
                    ->label('Способ оплаты'),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->label('Клиент'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('mark_as_completed')
                        ->label('Отметить выполненным')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Model $record) {
                            $record->update(['status' => \App\Enums\OrderStatus::COMPLETED->value]);
                        })
                        ->visible(fn (Model $record): bool => $record->status !== \App\Enums\OrderStatus::COMPLETED->value),

                    Action::make('mark_as_cancelled')
                        ->label('Отменить')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Model $record) {
                            $record->update(['status' => \App\Enums\OrderStatus::CANCELLED->value]);
                        })
                        ->visible(fn (Model $record): bool => $record->status !== \App\Enums\OrderStatus::CANCELLED->value),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
