<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('enums.product.resource.table.name'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('enums.product.resource.table.slug'))
                    ->searchable(),
                TextColumn::make('price')
                    ->label(__('enums.product.resource.table.price'))
                    ->money()
                    ->sortable(),
                TextColumn::make('old_price')
                    ->label(__('enums.product.resource.table.old_price'))
                    ->money()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label(__('enums.product.resource.table.sku'))
                    ->searchable(),
                TextColumn::make('stock_quantity')
                    ->label(__('enums.product.resource.table.stock_quantity'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('in_stock')
                    ->label(__('enums.product.resource.table.in_stock'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('enums.product.resource.table.is_active'))
                    ->boolean(),
                ImageColumn::make('image_url')
                    ->label(__('enums.product.resource.form.images')),
                TextColumn::make('weight')
                    ->label(__('enums.product.resource.table.weight'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label(__('enums.product.resource.table.unit'))
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('enums.product.resource.table.category_id'))
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label(__('enums.product.resource.form.sort_order'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('enums.product.resource.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('enums.product.resource.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label(__('enums.product.resource.table.category_id')),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('enums.product.resource.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('enums.product.resource.delete')),
                ]),
            ]);
    }
}
