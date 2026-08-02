<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $items = $this->order->orderItems->map(
            fn ($item) => "{$item->product_name} x{$item->quantity} — ".number_format($item->total_price, 2, '.', '').' ₽'
        )->implode("\n");

        return (new MailMessage)
            ->subject('Заказ №'.$this->order->id.' создан')
            ->greeting('Здравствуйте, '.($this->order->customer->name ?? '').'!')
            ->line('Ваш заказ №'.$this->order->id.' успешно создан.')
            ->line('')
            ->line('**Состав заказа:**')
            ->line($items)
            ->line('')
            ->line('**Сумма заказа:** '.number_format($this->order->total_amount, 2, '.', '').' ₽')
            ->line('**Статус оплаты:** '.$this->order->getPaymentStatusLabel())
            ->line('**Способ оплаты:** '.($this->order->paymentMethod?->name ?? 'Не указан'))
            ->line('')
            ->line('**Способ доставки:** '.($this->order->deliveryMethod?->getDisplayName() ?? 'Не указан'))
            ->when($this->order->delivery_city || $this->order->delivery_street, function (MailMessage $message) {
                return $message->line('**Адрес доставки:** '.implode(', ', array_filter([
                    $this->order->delivery_city,
                    $this->order->delivery_street,
                    $this->order->delivery_building,
                    $this->order->delivery_apartment,
                ])));
            })
            ->line('')
            ->line('Спасибо, что выбрали наш сервис!');
    }
}
