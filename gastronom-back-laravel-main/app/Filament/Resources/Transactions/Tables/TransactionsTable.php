<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('enums.transaction.resource.table.id'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('order.id')
                    ->label(__('enums.transaction.resource.table.order_id'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('enums.transaction.resource.table.customer'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('enums.transaction.resource.table.amount'))
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label(__('enums.transaction.resource.table.currency'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('enums.transaction.resource.table.payment_method'))
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->getLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('gateway')
                    ->label(__('enums.transaction.resource.table.gateway'))
                    ->formatStateUsing(fn (PaymentGateway $state): string => $state->getLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('enums.transaction.resource.table.status'))
                    ->badge()
                    ->color(fn (TransactionStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (TransactionStatus $state): string => $state->getLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label(__('enums.transaction.resource.table.processed_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('enums.transaction.resource.table.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('enums.transaction.resource.filters.status'))
                    ->options(fn (): array => collect(TransactionStatus::cases())->mapWithKeys(fn (TransactionStatus $status): array => [$status->value => $status->getLabel()])->toArray()),

                Tables\Filters\SelectFilter::make('gateway')
                    ->label(__('enums.transaction.resource.filters.gateway'))
                    ->options(PaymentGateway::class),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label(__('enums.transaction.resource.filters.payment_method'))
                    ->options(PaymentMethod::class),

                Tables\Filters\Filter::make('processed_at')
                    ->label(__('enums.transaction.resource.filters.processed_at'))
                    ->form([
                        Forms\Components\DatePicker::make('processed_from')
                            ->label(__('enums.transaction.resource.filters.processed_from')),
                        Forms\Components\DatePicker::make('processed_until')
                            ->label(__('enums.transaction.resource.filters.processed_until')),
                    ])
                    ->query(function (array $data): \Illuminate\Database\Eloquent\Builder {
                        return \App\Models\Transaction::query()
                            ->when(
                                $data['processed_from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('processed_at', '>=', $date),
                            )
                            ->when(
                                $data['processed_until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('processed_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
