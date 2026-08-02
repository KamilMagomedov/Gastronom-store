<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->label('Ключ'),

                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                    ->limit(50)
                    ->label('Значение'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'text' => 'primary',
                        'number' => 'success',
                        'boolean' => 'warning',
                        'json' => 'info',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'text' => 'Текст',
                        'number' => 'Число',
                        'boolean' => 'Да/Нет',
                        'json' => 'JSON',
                        default => $state,
                    })
                    ->label('Тип'),

                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'general' => 'Общие',
                        'app' => 'Приложение',
                        'delivery' => 'Доставка',
                        'social' => 'Соцсети',
                        default => $state,
                    })
                    ->label('Группа'),

                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->label('Описание'),

                Tables\Columns\ToggleColumn::make('is_public')
                    ->label('Публичное'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Текст',
                        'number' => 'Число',
                        'boolean' => 'Да/Нет',
                        'json' => 'JSON',
                    ])
                    ->label('Тип'),

                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'Общие',
                        'app' => 'Приложение',
                        'delivery' => 'Доставка',
                        'social' => 'Соцсети',
                    ])
                    ->label('Группа'),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Публичное'),
            ])
            ->actions([
                EditAction::make(),
                DeleteBulkAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
