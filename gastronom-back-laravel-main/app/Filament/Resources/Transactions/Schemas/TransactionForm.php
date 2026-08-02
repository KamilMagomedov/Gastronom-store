<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\Currency;
use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('enums.transaction.resource.form.transaction_information'))
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label(__('enums.transaction.resource.form.order_id'))
                            ->disabled(fn ($record): bool => $record !== null),

                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label(__('enums.transaction.resource.form.customer_id'))
                            ->disabled(fn ($record): bool => $record !== null),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->step(0.01)
                            ->label(__('enums.transaction.resource.form.amount'))
                            ->disabled(fn ($record): bool => $record !== null),

                        Forms\Components\Select::make('currency')
                            ->options(Currency::class)
                            ->default(Currency::RUB)
                            ->required()
                            ->label(__('enums.transaction.resource.form.currency'))
                            ->disabled(fn ($record): bool => $record !== null),

                        Forms\Components\Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->required()
                            ->label(__('enums.transaction.resource.form.payment_method'))
                            ->disabled(fn ($record): bool => $record !== null),

                        Forms\Components\Select::make('gateway')
                            ->options(PaymentGateway::class)
                            ->required()
                            ->label(__('enums.transaction.resource.form.gateway'))
                            ->disabled(fn ($record): bool => $record !== null),

                        Forms\Components\TextInput::make('gateway_transaction_id')
                            ->label(__('enums.transaction.resource.form.gateway_transaction_id'))
                            ->unique()
                            ->disabled(fn ($record): bool => $record !== null),

                        Forms\Components\Select::make('status')
                            ->options(fn (): array => collect(TransactionStatus::cases())->mapWithKeys(fn (TransactionStatus $status): array => [$status->value => $status->getLabel()])->toArray())
                            ->default(TransactionStatus::PENDING)
                            ->required()
                            ->label(__('enums.transaction.resource.form.status')),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->label(__('enums.transaction.resource.form.notes')),
                    ])
                    ->columns(2),

                Section::make(__('enums.transaction.resource.form.gateway_response'))
                    ->schema([
                        Forms\Components\Placeholder::make('gateway_response_display')
                            ->label(__('enums.transaction.resource.form.gateway_response'))
                            ->content(fn ($record): string => $record && $record->gateway_response
                                ? json_encode($record->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : __('enums.transaction.resource.form.gateway_response_empty')),
                    ])
                    ->visible(fn ($record): bool => $record !== null),

                Section::make(__('enums.transaction.resource.form.gateway_response_edit'))
                    ->schema([
                        Forms\Components\Textarea::make('gateway_response')
                            ->label(__('enums.transaction.resource.form.gateway_response'))
                            ->rows(5)
                            ->helperText(__('enums.transaction.resource.form.gateway_response_helper'))
                            ->json(),
                    ])
                    ->visible(fn ($record): bool => $record === null),

                Section::make(__('enums.transaction.resource.form.timestamps'))
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label(__('enums.transaction.resource.form.created_at'))
                            ->content(fn ($record): ?string => $record?->created_at?->format('d M Y H:i')),

                        Forms\Components\Placeholder::make('processed_at')
                            ->label(__('enums.transaction.resource.form.processed_at'))
                            ->content(fn ($record): ?string => $record?->processed_at?->format('d M Y H:i')),
                    ])
                    ->columns(2)
                    ->visible(fn ($record): bool => $record !== null),
            ]);
    }
}
