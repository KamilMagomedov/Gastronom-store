<?php

namespace App\Filament\Resources\ProductAttributes\RelationManagers;

use App\Enums\ProductAttributeType;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Опции атрибута';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Опции атрибута';
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === ProductAttributeType::SELECT->value;
    }

    public static function getModelLabel(): string
    {
        return 'Опция';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Опции';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Ключ')
                    ->required()
                    ->helperText('Уникальный ключ опции (например, "red", "blue")')
                    ->maxLength(255),
                Forms\Components\TextInput::make('value')
                    ->label('Значение')
                    ->required()
                    ->helperText('Отображаемое значение (например, "Красный", "Синий")')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Опции не найдены')
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Ключ')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Значение')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        '1' => 'Активна',
                        '0' => 'Неактивна',
                    ]),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Добавить опцию')
                    ->modalHeading('Добавление опции атрибута'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Редактировать')
                    ->modalHeading('Редактирование опции'),
                Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()
                    ->label('Удалить выбранные'),
            ]);
    }
}
