<?php

namespace App\Filament\Resources\Carts\Tables;

use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('enums.cart.resource.table.id'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.email')
                    ->label(__('enums.cart.resource.table.customer'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('Гость')
                    ->url(fn ($record) => $record->customer ? route('filament.admin.resources.customers.edit', $record->customer) : null),

                Tables\Columns\TextColumn::make('session_id')
                    ->label(__('enums.cart.resource.table.session_id'))
                    ->sortable()
                    ->searchable()
                    ->limit(20)
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('total_items')
                    ->label(__('enums.cart.resource.table.total_items'))
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('enums.cart.resource.table.total_amount'))
                    ->money('RUB')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('enums.cart.resource.table.expires_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('enums.cart.resource.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label(__('enums.cart.resource.filters.user_type'))
                    ->options([
                        'registered' => __('enums.cart.resource.filters.registered'),
                        'guest' => __('enums.cart.resource.filters.guest'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'registered',
                            fn (Builder $query) => $query->whereNotNull('customer_id')
                        )->when(
                            $data['value'] === 'guest',
                            fn (Builder $query) => $query->whereNull('customer_id')
                        );
                    }),

                Tables\Filters\Filter::make('active')
                    ->label(__('enums.cart.resource.filters.active'))
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '>', now()))
                    ->default(),

                Tables\Filters\Filter::make('expired')
                    ->label(__('enums.cart.resource.filters.expired'))
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<=', now())),

                Tables\Filters\Filter::make('has_items')
                    ->label(__('enums.cart.resource.filters.has_items'))
                    ->query(fn (Builder $query): Builder => $query->where('total_items', '>', 0)),

                Tables\Filters\Filter::make('empty')
                    ->label(__('enums.cart.resource.filters.empty'))
                    ->query(fn (Builder $query): Builder => $query->where('total_items', '=', 0)),
            ])
            ->actions([
                Action::make('view_items')
                    ->label('Просмотр товаров')
                    ->icon('heroicon-o-eye')
                    ->modalContent(function ($record) {
                        return view('filament.resources.carts.cart-items', [
                            'items' => $record->items()->with('product')->get(),
                            'cart' => $record,
                        ]);
                    })
                    ->modalHeading(fn ($record) => "Товары в корзине #{$record->id}")
                    ->modalWidth('2xl'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
