<?php

namespace App\Filament\Resources\StaticPages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaticPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label('Тип страницы')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'terms' => 'Условия использования',
                        'privacy' => 'Политика конфиденциальности',
                        default => $state
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => 'Создана: '.$record->created_at->format('d.m.Y H:i')),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        1 => 'Активные',
                        0 => 'Неактивные',
                    ]),
            ])
            ->actions([
                EditAction::make()->label('Редактировать'),
                DeleteAction::make('delete')
                    ->label('Удалить')
                    ->hidden(fn ($record) => $record->isProtected()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Удалить выбранные')
                        ->hidden(fn () => true),
                ]),
            ])
            ->emptyStateHeading('Статические страницы не найдены')
            ->emptyStateDescription('Создайте новую статическую страницу для начала.');
    }
}
