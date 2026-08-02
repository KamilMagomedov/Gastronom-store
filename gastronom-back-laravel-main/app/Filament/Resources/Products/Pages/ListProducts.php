<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        return __('enums.product.resource.plural_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('enums.product.resource.create')),
        ];
    }

    protected function getTableEmptyStateHeading(): string
    {
        return __('enums.product.resource.table.empty_state');
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return __('enums.product.resource.table.empty_state_description');
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('enums.product.resource.create'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
