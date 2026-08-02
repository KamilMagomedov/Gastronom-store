<?php

namespace App\Filament\Resources\ProductReviews\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class ProductReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('enums.product_review.resource.table.product'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('enums.product_review.resource.table.customer'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label(__('enums.product_review.resource.table.rating'))
                    ->formatStateUsing(fn (int $state): string => '⭐'.$state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('comment')
                    ->label(__('enums.product_review.resource.table.comment'))
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('is_approved')
                    ->label(__('enums.product_review.resource.table.approved'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('enums.product_review.resource.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options(__('enums.product_review.resource.filters.rating')),

                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label(__('enums.product_review.resource.filters.approval_status.all'))
                    ->placeholder(__('enums.product_review.resource.filters.approval_status.all'))
                    ->trueLabel(__('enums.product_review.resource.filters.approval_status.approved'))
                    ->falseLabel(__('enums.product_review.resource.filters.approval_status.pending')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\Action::make('approve')
                        ->label(__('enums.product_review.resource.table.approve_selected'))
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_approved' => true]))
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\Action::make('reject')
                        ->label(__('enums.product_review.resource.table.reject_selected'))
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_approved' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
