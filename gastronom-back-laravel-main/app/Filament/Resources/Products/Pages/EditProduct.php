<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('enums.product.resource.pages.edit.delete')),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();

        return __('enums.product.resource.pages.edit.title', ['name' => $record->name]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(__('enums.product.resource.pages.edit.save')),
            $this->getCancelFormAction()
                ->label(__('enums.product.resource.pages.edit.cancel')),
        ];
    }
}
