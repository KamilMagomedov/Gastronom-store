<?php

namespace App\Filament\Resources\Carts;

use App\Filament\Resources\Carts\Pages\ListCarts;
use App\Filament\Resources\Carts\Tables\CartsTable;
use App\Models\Cart;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|UnitEnum|null $navigationGroup = 'Заказы';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return CartsTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['session_id', 'customer.email'];
    }

    public static function getNavigationLabel(): string
    {
        return __('enums.cart.resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('enums.cart.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('enums.cart.resource.plural_label');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCarts::route('/'),
        ];
    }
}
