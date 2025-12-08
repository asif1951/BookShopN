<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    protected $order;
    protected $oldStatus;
    protected $newStatus;

    public function __construct(Order $order, $oldStatus, $newStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusColors = [
            'pending' => '#f59e0b',
            'confirmed' => '#3b82f6',
            'processing' => '#8b5cf6',
            'shipped' => '#10b981',
            'delivered' => '#14b8a6',
            'cancelled' => '#ef4444'
        ];

        $statusLabels = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled'
        ];

        return (new MailMessage)
            ->subject('Your Order Status Has Been Updated - Order #' . $this->order->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your order status has been updated.')
            ->line('**Order ID:** #' . $this->order->id)
            ->line('**Previous Status:** ' . $statusLabels[$this->oldStatus])
            ->line('**New Status:** ' . $statusLabels[$this->newStatus])
            ->line('**Order Date:** ' . $this->order->created_at->format('F d, Y'))
            ->line('**Total Amount:** $' . number_format($this->order->total_amount, 2))
            ->action('View Order Details', url('/order-list'))
            ->line('Thank you for shopping with us!');
    }
}
