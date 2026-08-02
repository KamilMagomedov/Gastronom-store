<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\ProductAttributeType;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductAttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'productAttributes';

    protected static ?string $title = 'Атрибуты товара';

    public static function getEloquentQuery(Model $ownerRecord): Builder
    {
        return parent::getEloquentQuery($ownerRecord)
            ->with(['productAttribute', 'activeOptions']);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Атрибуты товара';
    }

    public static function getModelLabel(): string
    {
        return 'Атрибут товара';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Атрибуты товаров';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('product_attribute_id')
                    ->label('Название атрибута')
                    ->options(ProductAttribute::active()->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('pivot.value', null)),

                Forms\Components\TextInput::make('pivot.value')
                    ->label('Значение')
                    ->required()
                    ->reactive()
                    ->visible(fn (callable $get) => $get('product_attribute_id') && ProductAttribute::find($get('product_attribute_id'))->type !== ProductAttributeType::SELECT->value),

                Forms\Components\Select::make('pivot.value')
                    ->label('Значение')
                    ->required()
                    ->reactive()
                    ->visible(fn (callable $get) => $get('product_attribute_id') && ProductAttribute::find($get('product_attribute_id'))->type === ProductAttributeType::SELECT->value)
                    ->options(function (callable $get) {
                        $attributeId = $get('product_attribute_id');
                        if (! $attributeId) {
                            return [];
                        }

                        return ProductAttributeOption::where('product_attribute_id', $attributeId)
                            ->active()
                            ->ordered()
                            ->pluck('value', 'key');
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Нет привязанных атрибутов товара.')
            ->columns([
                Tables\Columns\TextColumn::make('productAttribute.name')
                    ->label('Название')
                    ->getStateUsing(fn ($record) => $record->name ?? 'N/A'),
                Tables\Columns\TextColumn::make('pivot.value')
                    ->label('Значение')
                    ->getStateUsing(function ($record) {
                        $option = ProductAttributeOption::query()->where('key', $record->value)->first();

                        return $option ? $option->value : $record->value;
                    }),
                Tables\Columns\TextColumn::make('productAttribute.type')
                    ->label('Тип')
                    ->getStateUsing(fn ($record) => ProductAttributeType::tryFrom($record->type)?->getLabel() ?? 'N/A'),
                Tables\Columns\IconColumn::make('productAttribute.is_active')
                    ->label('Активен')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->is_active ?? false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options(ProductAttributeType::getFilterOptions()),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Привязать атрибут')
                    ->modalHeading('Привязать атрибута товара')
                    ->form([
                        Forms\Components\Select::make('product_attribute_id')
                            ->label('Название атрибута')
                            ->options(ProductAttribute::active()->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('pivot.value', null)),

                        Forms\Components\TextInput::make('pivot.value')
                            ->label('Значение')
                            ->required()
                            ->reactive()
                            ->visible(fn (callable $get) => $get('product_attribute_id') && ProductAttribute::find($get('product_attribute_id'))->type !== ProductAttributeType::SELECT->value),

                        Forms\Components\Select::make('pivot.value')
                            ->label('Значение')
                            ->required()
                            ->reactive()
                            ->visible(fn (callable $get) => $get('product_attribute_id') && ProductAttribute::find($get('product_attribute_id'))->type === ProductAttributeType::SELECT->value)
                            ->options(function (callable $get) {
                                $attributeId = $get('product_attribute_id');
                                if (! $attributeId) {
                                    return [];
                                }

                                return ProductAttributeOption::where('product_attribute_id', $attributeId)
                                    ->active()
                                    ->ordered()
                                    ->pluck('value', 'key');
                            }),
                    ])
                    ->action(function (array $data) {
                        $this->getOwnerRecord()
                            ->productAttributes()
                            ->syncWithoutDetaching([
                                $data['product_attribute_id'] => [
                                    'value' => $data['pivot']['value'],
                                ],
                            ]);
                    }),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Редактировать значение')
                    ->modalHeading('Редактировать значение атрибута')
                    ->form(function (Model $record) {
                        $isSelect = $record->type === ProductAttributeType::SELECT->value;

                        return [
                            Forms\Components\Select::make('product_attribute_id')
                                ->label('Название атрибута')
                                ->default($record->product_attribute_id)
                                ->disabled()
                                ->options(
                                    ProductAttribute::query()
                                        ->where('id', $record->product_attribute_id)
                                        ->active()
                                        ->pluck('name', 'id')
                                )
                                ->dehydrated(false),

                            Forms\Components\Hidden::make('attribute_type')
                                ->default($record->type),

                            Forms\Components\TextInput::make('pivot.value')
                                ->label('Значение')
                                ->required()
                                ->formatStateUsing(fn ($record) => $record->pivot_value)
                                ->visible(! $isSelect),

                            Forms\Components\Select::make('pivot.value')
                                ->label('Значение')
                                ->required()
                                ->default($record->pivot_value)
                                ->visible($isSelect)
                                ->options(function () use ($record, $isSelect) {
                                    if (! $isSelect) {
                                        return [];
                                    }

                                    return ProductAttributeOption::where('product_attribute_id', $record->id)
                                        ->active()
                                        ->ordered()
                                        ->pluck('value', 'key');
                                }),
                        ];
                    })
                    ->using(function (array $data, Model $record) {
                        $record->pivot->value = $data['pivot']['value'];
                        $record->pivot->save();

                        return $record;
                    }),
                Actions\DetachAction::make()
                    ->label('Отвязать'),
            ])
            ->bulkActions([
                Actions\DetachBulkAction::make()
                    ->label('Отвязать выбранные'),
            ]);
    }
}
