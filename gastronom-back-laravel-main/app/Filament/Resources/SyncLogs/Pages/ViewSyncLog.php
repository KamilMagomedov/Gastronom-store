<?php

namespace App\Filament\Resources\SyncLogs\Pages;

use App\Filament\Resources\SyncLogs\SyncLogResource;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewSyncLog extends ViewRecord
{
    protected static string $resource = SyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label('Закрыть')
                ->url(route('filament.admin.resources.sync-logs.index'))
                ->icon('heroicon-o-x-mark'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        Placeholder::make('id')
                            ->label('ID')
                            ->content(fn ($record): string => $record->id),

                        Placeholder::make('source')
                            ->label('Источник')
                            ->content(fn ($record): string => $record->source ?? 'Не указан'),

                        Placeholder::make('entity_type')
                            ->label('Тип сущности')
                            ->content(fn ($record): string => $record->entity_type ?? 'Не указан'),

                        Placeholder::make('entity_id')
                            ->label('ID сущности')
                            ->content(fn ($record): string => $record->entity_id ?? 'Не указан'),

                        Placeholder::make('operation')
                            ->label('Операция')
                            ->content(fn ($record): string => $record->operation ?? 'Не указана'),

                        Placeholder::make('status')
                            ->label('Статус')
                            ->content(fn ($record): string => $record->status === 'success' ? '✅ Успешно' : '❌ Ошибка'),

                        Placeholder::make('synced_at')
                            ->label('Дата синхронизации')
                            ->content(fn ($record): string => $record->synced_at?->format('d.m.Y H:i:s')),
                    ])
                    ->columns(2),

                Section::make('Сообщение')
                    ->schema([
                        Textarea::make('message')
                            ->label('Сообщение')
                            ->rows(3)
                            ->disabled(),
                    ]),

                Section::make('Данные синхронизации')
                    ->schema([
                        Placeholder::make('data_preview')
                            ->label('Данные')
                            ->content(fn ($record): string => $record->data ? json_encode($record->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'Нет данных')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
