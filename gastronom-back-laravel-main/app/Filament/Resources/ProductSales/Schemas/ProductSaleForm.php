<?php

namespace App\Filament\Resources\ProductSales\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductSaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Товар')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled()
                    ->unique(ignoreRecord: true),

                TextInput::make('total_quantity')
                    ->label('Общее количество')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->minValue(0),

                TextInput::make('total_revenue')
                    ->label('Общая выручка')
                    ->disabled()
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->prefix('$')
                    ->step(0.01)
                    ->minValue(0),
            ]);
    }
}
