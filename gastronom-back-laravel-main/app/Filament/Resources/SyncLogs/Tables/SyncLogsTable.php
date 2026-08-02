<?php

namespace App\Filament\Resources\SyncLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SyncLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source')
                    ->label('Источник')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1C' => 'primary',
                        'API' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Тип сущности')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'products' => 'Товары',
                        'orders' => 'Заказы',
                        'customers' => 'Клиенты',
                        'categories' => 'Категории',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('entity_id')
                    ->label('ID сущности')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('operation')
                    ->label('Операция')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'sync' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'create' => 'Создание',
                        'update' => 'Обновление',
                        'delete' => 'Удаление',
                        'sync' => 'Синхронизация',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Успешно',
                        'error' => 'Ошибка',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('message')
                    ->label('Сообщение')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Дата синхронизации')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->synced_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->label('Источник')
                    ->options([
                        '1C' => '1С',
                        'API' => 'API',
                    ]),

                Tables\Filters\SelectFilter::make('entity_type')
                    ->label('Тип сущности')
                    ->options([
                        'products' => 'Товары',
                        'orders' => 'Заказы',
                        'customers' => 'Клиенты',
                        'categories' => 'Категории',
                    ]),

                Tables\Filters\SelectFilter::make('operation')
                    ->label('Операция')
                    ->options([
                        'create' => 'Создание',
                        'update' => 'Обновление',
                        'delete' => 'Удаление',
                        'sync' => 'Синхронизация',
                    ]),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Успешные')
                    ->falseLabel('Ошибки')
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 'success'),
                        false: fn (Builder $query) => $query->where('status', 'error'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('synced_at', 'desc')
            ->emptyStateHeading('Логи синхронизации не найдены')
            ->emptyStateDescription('Нет записей о синхронизации')
            ->emptyStateActions([
                //
            ]);
    }
}
