<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OtpsRelationManager extends RelationManager
{
    protected static string $relationship = 'otps';

    protected static ?string $modelLabel = 'OTP';

    protected static ?string $pluralModelLabel = 'OTP коды';

    protected static ?string $title = 'Активные OTP коды';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // OTP codes are generated automatically, no manual editing
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('token')
            ->columns([
                Tables\Columns\TextColumn::make('token')
                    ->label('Код')
                    ->searchable(),
                Tables\Columns\TextColumn::make('validity')
                    ->label('Действителен до')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return null;
                        }
                        // If it's already a timestamp in seconds, use it directly
                        if (is_numeric($state) && $state > 1000000000) {
                            return date('Y-m-d H:i:s', $state);
                        }
                        // If it's a small number, it might be minutes from now or something else
                        if (is_numeric($state)) {
                            return date('Y-m-d H:i:s', time() + ($state * 60)); // assume minutes
                        }

                        return $state;
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('valid')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->where('valid', true));
    }
}
