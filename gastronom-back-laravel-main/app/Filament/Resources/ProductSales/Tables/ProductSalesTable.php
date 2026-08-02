<?php

namespace App\Filament\Resources\ProductSales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductSalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_quantity')
                    ->label('Количество')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, '.', ' ')),

                TextColumn::make('total_revenue')
                    ->label('Выручка')
                    ->numeric()
                    ->sortable()
                    ->money('USD')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state, 2, '.', ' ')),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record?->updated_at?->format('d.m.Y H:i')),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
