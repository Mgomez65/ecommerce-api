<?php

namespace App\Notifications;

use App\Models\Orders;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmed extends Notification
{
    public function __construct(private readonly Orders $order)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = number_format((float) $this->order->total, 2, ',', '.');

        return (new MailMessage)
            ->subject("Confirmamos tu pedido #{$this->order->id}")
            ->greeting("¡Gracias por tu compra, {$notifiable->name}!")
            ->line("Tu pedido #{$this->order->id} fue confirmado y ya lo estamos preparando.")
            ->line("Total: \${$total}")
            ->action('Ver mi pedido', rtrim(config('app.url'), '/') . "/mis-pedidos/{$this->order->id}")
            ->line('¡Gracias por elegirnos!');
    }
}
