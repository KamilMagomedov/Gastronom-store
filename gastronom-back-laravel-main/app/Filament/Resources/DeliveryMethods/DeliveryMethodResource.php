<?php

namespace App\Filament\Resources\DeliveryMethods;

use App\Filament\Resources\DeliveryMethods\DeliveryMethodResource\Pages;
use App\Models\DeliveryMethod;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryMethodResource extends Resource
{
    protected static ?string $model = DeliveryMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Способ доставки';

    protected static ?string $pluralModelLabel = 'Способы доставки';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->disabled(),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(3)
                    ->maxLength(65535),

                \Filament\Forms\Components\TextInput::make('cost')
                    ->label('Стоимость')
                    ->numeric()
                    ->step(0.01)
                    ->default(0)
                    ->required(),

                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),

                \Filament\Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('cost')
                    ->label('Стоимость')
                    ->money('RUB')
                    ->sortable(),

                \Filament\Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Активность'),

                \Filament\Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        true => 'Активные',
                        false => 'Неактивные',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryMethods::route('/'),
            'create' => Pages\CreateDeliveryMethod::route('/create'),
            'edit' => Pages\EditDeliveryMethod::route('/{record}/edit'),
        ];
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
