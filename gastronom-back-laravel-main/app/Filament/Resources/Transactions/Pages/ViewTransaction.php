<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label('Закрыть')
                ->url(route('filament.admin.resources.transactions.index'))
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Основная информация')
                ->schema([
                    Placeholder::make('id')
                        ->label('ID')
                        ->content(fn ($record): string => $record->id),

                    Placeholder::make('order_id')
                        ->label('ID заказа')
                        ->content(fn ($record): string => $record->order_id ?? 'Не указан'),

                    Placeholder::make('customer_id')
                        ->label('ID клиента')
                        ->content(fn ($record): string => $record->customer_id ?? 'Не указан'),

                    Placeholder::make('amount')
                        ->label('Сумма')
                        ->content(fn ($record): string => $record->amount ? number_format($record->amount, 2).' '.($record->currency?->value ?? '') : 'Не указана'),

                    Placeholder::make('currency')
                        ->label('Валюта')
                        ->content(fn ($record): string => $record->currency?->getLabel() ?? 'Не указана'),

                    Placeholder::make('payment_method')
                        ->label('Метод оплаты')
                        ->content(fn ($record): string => $record->payment_method?->getLabel() ?? 'Не указан'),

                    Placeholder::make('gateway')
                        ->label('Платежный шлюз')
                        ->content(fn ($record): string => $record->gateway?->getLabel() ?? 'Не указан'),

                    Placeholder::make('status')
                        ->label('Статус')
                        ->content(fn ($record): string => $record->status?->getLabel() ?? 'Не указан'),

                    Placeholder::make('gateway_transaction_id')
                        ->label('ID транзакции в шлюзе')
                        ->content(fn ($record): string => $record->gateway_transaction_id ?? 'Не указан'),

                    Placeholder::make('processed_at')
                        ->label('Дата обработки')
                        ->content(fn ($record): string => $record->processed_at?->format('d.m.Y H:i:s') ?? 'Не указана'),

                    Placeholder::make('created_at')
                        ->label('Дата создания')
                        ->content(fn ($record): string => $record->created_at?->format('d.m.Y H:i:s')),
                ])
                ->columns(2),

            Section::make('Дополнительная информация')
                ->schema([
                    Textarea::make('notes')
                        ->label('Примечания')
                        ->rows(3)
                        ->disabled(),

                    Textarea::make('gateway_response')
                        ->label('Ответ шлюза')
                        ->rows(5)
                        ->disabled(),
                ]),
        ];
    }
}
