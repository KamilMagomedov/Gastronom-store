<?php

namespace App\Filament\Resources\Supports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_id')
                    ->searchable()
                    ->sortable()
                    ->label(__('enums.resource.table.order_id')),
                TextColumn::make('theme')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SupportTheme ? $state->label() : $state)
                    ->badge()
                    ->label(__('enums.resource.table.theme')),
                TextColumn::make('status')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SupportStatus ? $state->label() : $state)
                    ->color(fn ($state) => match ($state) {
                        'new', \App\Enums\SupportStatus::NEW => 'gray',
                        'pending', \App\Enums\SupportStatus::PENDING => 'warning',
                        'in_progress', \App\Enums\SupportStatus::IN_PROGRESS => 'info',
                        'resolved', \App\Enums\SupportStatus::RESOLVED => 'success',
                        'closed', \App\Enums\SupportStatus::CLOSED => 'danger',
                        'reopened', \App\Enums\SupportStatus::REOPENED => 'warning',
                        default => 'gray',
                    })
                    ->badge()
                    ->label(__('enums.resource.table.status')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('enums.resource.table.created_at')),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('enums.resource.table.updated_at')),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
