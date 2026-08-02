<?php

namespace App\Filament\Resources\ProductAttributes\Pages;

use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductAttributes extends ListRecords
{
    protected static string $resource = ProductAttributeResource::class;

    public function getTitle(): string
    {
        return 'Атрибуты товаров';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать атрибут'),
        ];
    }

    protected function getTableEmptyStateHeading(): string
    {
        return 'Атрибуты не найдены';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Создайте свой первый атрибут товара, чтобы начать';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать атрибут')
                ->icon('heroicon-o-plus'),
        ];
    }
}
