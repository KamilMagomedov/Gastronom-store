<?php

namespace App\Filament\Resources\AcquirerResource;

use App\Models\Acquirer;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AcquirerResource extends Resource
{
    protected static ?string $model = Acquirer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Эквайер';

    protected static ?string $pluralModelLabel = 'Эквайеры';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $codes = [
            'sberbank' => 'Сбербанк',
            'tinkoff' => 'Тинькофф',
            'vtb' => 'ВТБ',
            'alfa' => 'Альфа-Банк',
            'psb' => 'ПСБ',
            'gazprom' => 'Газпромбанк',
        ];

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                Select::make('code')
                    ->label('Банк')
                    ->options($codes)
                    ->required()
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->label('Описание')
                    ->rows(3)
                    ->maxLength(65535),

                KeyValue::make('config')
                    ->label('Настройки API')
                    ->keyLabel('Параметр')
                    ->valueLabel('Значение')
                    ->addActionLabel('Добавить параметр')
                    ->reorderable()
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),

                TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Банк')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::getBankName($state))
                    ->color('gray'),

                TextColumn::make('description')
                    ->label('Описание')
                    ->limit(40)
                    ->searchable(),

                ToggleColumn::make('is_active')
                    ->label('Активность'),

                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('code')
                    ->label('Банк')
                    ->options([
                        'sberbank' => 'Сбербанк',
                        'tinkoff' => 'Тинькофф',
                        'vtb' => 'ВТБ',
                        'alfa' => 'Альфа-Банк',
                        'psb' => 'ПСБ',
                        'gazprom' => 'Газпромбанк',
                    ]),

                SelectFilter::make('is_active')
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
            'index' => Pages\ListAcquirers::route('/'),
            'create' => Pages\CreateAcquirer::route('/create'),
            'edit' => Pages\EditAcquirer::route('/{record}/edit'),
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

    public static function getBankName(string $code): string
    {
        return match ($code) {
            'sberbank' => 'Сбербанк',
            'tinkoff' => 'Тинькофф',
            'vtb' => 'ВТБ',
            'alfa' => 'Альфа-Банк',
            'psb' => 'ПСБ',
            'gazprom' => 'Газпромбанк',
            default => $code,
        };
    }
}
