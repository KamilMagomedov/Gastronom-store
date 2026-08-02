<?php

namespace App\DTO\Order;

readonly class CreateOrderData
{
    public function __construct(
        public int $deliveryMethod,
        public int $paymentMethod,
        public ?string $deliveryPhone = null,
        public ?string $deliveryNotes = null,
        public ?string $deliveryStreet = null,
        public ?string $deliveryCity = null,
        public ?string $deliveryApartment = null,
        public ?string $deliveryPostalCode = null,
        public ?float $deliveryLatitude = null,
        public ?float $deliveryLongitude = null,
        public ?string $deliveryBuilding = null,
        public ?string $deliveryEntrance = null,
        public ?string $deliveryFloor = null,
        public ?string $notes = null
    ) {}
}
