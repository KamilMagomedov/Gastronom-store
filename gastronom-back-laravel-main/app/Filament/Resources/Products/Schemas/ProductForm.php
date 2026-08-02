<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ProductTabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('enums.product.resource.form.basic_information'))
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label(__('enums.product.resource.form.name'))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->required()
                                    ->label(__('enums.product.resource.form.slug'))
                                    ->unique(ignoreRecord: true),
                                Textarea::make('description')
                                    ->columnSpanFull()
                                    ->label(__('enums.product.resource.form.description')),
                            ])
                            ->columns(2),
                        Tab::make(__('enums.product.resource.form.pricing_inventory'))
                            ->schema([
                                TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->label(__('enums.product.resource.form.price')),
                                TextInput::make('old_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->label(__('enums.product.resource.form.old_price')),
                                TextInput::make('stock_quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->label(__('enums.product.resource.form.stock_quantity')),
                            ])
                            ->columns(2),
                        Tab::make(__('enums.product.resource.form.images'))
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('media')
                                    ->collection('images')
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->image()
                                    ->imageEditor()
                                    ->label(__('enums.product.resource.form.images'))
                                    ->helperText(__('enums.product.resource.form.images_helper')),
                            ]),
                        Tab::make(__('enums.product.resource.form.additional_settings'))
                            ->schema([
                                TextInput::make('sku')
                                    ->label(__('enums.product.resource.form.slug'))
                                    ->unique('products', 'sku'),
                                TextInput::make('external_id')
                                    ->label(__('enums.product.resource.form.external_id'))
                                    ->helperText(__('enums.product.resource.form.external_id_helper')),
                                TextInput::make('weight')
                                    ->numeric()
                                    ->label(__('enums.product.resource.form.weight')),
                                Select::make('unit')
                                    ->label(__('enums.product.resource.form.unit'))
                                    ->options([
                                        'шт' => 'шт (штуки)',
                                        'кг' => 'кг (килограммы)',
                                        'л' => 'л (литры)',
                                        'м' => 'м (метры)',
                                        'см' => 'см (сантиметры)',
                                        'м²' => 'м² (квадратные метры)',
                                        'м³' => 'м³ (кубические метры)',
                                        'т' => 'т (тонны)',
                                        'г' => 'г (граммы)',
                                        'мл' => 'мл (миллилитры)',
                                        'уп' => 'уп (упаковки)',
                                        'кор' => 'кор (коробки)',
                                        'пал' => 'пал (палеты)',
                                        'рул' => 'рул (рулоны)',
                                        'компл' => 'компл (комплекты)',
                                    ])
                                    ->default('шт')
                                    ->searchable()
                                    ->helperText('Выберите единицу измерения товара'),
                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->label(__('enums.product.resource.form.category_id')),
                                TextInput::make('sort_order')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->label(__('enums.product.resource.form.sort_order')),
                            ])
                            ->columns(2),
                        Tab::make(__('enums.product.resource.form.status'))
                            ->schema([
                                Toggle::make('in_stock')
                                    ->required()
                                    ->label(__('enums.product.resource.form.in_stock')),
                                Toggle::make('is_active')
                                    ->required()
                                    ->label(__('enums.product.resource.form.is_active')),
                            ]),
                    ]),
            ]);
    }
}
