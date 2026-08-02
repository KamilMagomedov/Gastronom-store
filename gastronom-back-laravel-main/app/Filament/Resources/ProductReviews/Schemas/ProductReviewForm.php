<?php

namespace App\Filament\Resources\ProductReviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('enums.product_review.resource.form.product_id')),

                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label(__('enums.product_review.resource.form.customer_id')),

                Select::make('rating')
                    ->options([
                        1 => __('enums.product_review.resource.filters.rating.1'),
                        2 => __('enums.product_review.resource.filters.rating.2'),
                        3 => __('enums.product_review.resource.filters.rating.3'),
                        4 => __('enums.product_review.resource.filters.rating.4'),
                        5 => __('enums.product_review.resource.filters.rating.5'),
                    ])
                    ->required()
                    ->label(__('enums.product_review.resource.form.rating')),

                Textarea::make('comment')
                    ->rows(3)
                    ->label(__('enums.product_review.resource.form.comment')),

                Toggle::make('is_approved')
                    ->label(__('enums.product_review.resource.form.is_approved'))
                    ->default(false),
            ])
            ->columns(2);
    }
}
