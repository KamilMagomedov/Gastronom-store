<?php

namespace App\Filament\Resources\ProductSales;

use App\Filament\Resources\ProductSales\Pages\CreateProductSale;
use App\Filament\Resources\ProductSales\Pages\EditProductSale;
use App\Filament\Resources\ProductSales\Pages\ListProductSales;
use App\Filament\Resources\ProductSales\Schemas\ProductSaleForm;
use App\Filament\Resources\ProductSales\Tables\ProductSalesTable;
use App\Models\ProductSale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductSaleResource extends Resource
{
    protected static ?string $model = ProductSale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Newspaper;

    protected static ?string $navigationLabel = 'Статистика продаж';

    protected static ?string $modelLabel = 'Статистика продаж';

    protected static ?string $pluralModelLabel = 'Статистика продаж';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ProductSaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductSalesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductSales::route('/'),
            'create' => CreateProductSale::route('/create'),
            'edit' => EditProductSale::route('/{record}/edit'),
        ];
    }
}
