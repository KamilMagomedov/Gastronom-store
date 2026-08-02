<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    protected static ?string $recordTitleAttribute = 'product_name';

    protected static ?string $title = 'Добавить товар';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Товар')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $product = Product::find($state);
                        if ($product) {
                            $set('product_name', $product->name);
                            $set('product_sku', $product->sku);
                            $set('unit_price', $product->price);
                            $set('total_price', 1 * $product->price);
                        }
                    }),

                Forms\Components\TextInput::make('product_name')
                    ->label('Название товара')
                    ->disabled(),

                Forms\Components\TextInput::make('product_sku')
                    ->label('Артикул')
                    ->disabled(),

                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->label('Количество')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $quantity = (float) $state;
                        $unitPrice = (float) $get('unit_price');
                        $set('total_price', $quantity * $unitPrice);
                    }),

                Forms\Components\TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('₽')
                    ->required()
                    ->label('Цена за единицу')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $unitPrice = (float) $state;
                        $quantity = (float) $get('quantity');
                        $set('total_price', $quantity * $unitPrice);
                    }),

                Forms\Components\TextInput::make('total_price')
                    ->numeric()
                    ->prefix('₽')
                    ->required()
                    ->label('Общая сумма')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('product_sku')
                    ->label('Артикул')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Количество')
                    ->numeric(),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Цена за единицу')
                    ->money('RUB'),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Общая сумма')
                    ->money('RUB')
                    ->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить товар')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['product_id'])) {
                            $product = Product::find($data['product_id']);
                            if ($product) {
                                $data['product_name'] = $product->name;
                                $data['product_sku'] = $product->sku;
                            }
                        }

                        // Расчет total_price
                        if (isset($data['quantity']) && isset($data['unit_price'])) {
                            $data['total_price'] = $data['quantity'] * $data['unit_price'];
                        }

                        return $data;
                    })
                    ->after(function () {
                        $order = $this->getOwnerRecord();
                        if ($order) {
                            $order->recalculateTotals();
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
