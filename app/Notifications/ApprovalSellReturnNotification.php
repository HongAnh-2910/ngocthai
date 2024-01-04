<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovalSellReturnNotification extends Notification
{
    use Queueable;

    protected $sell_return;

    /**
     * Create a new notification instance.
     * @param $sellReturn
     * @return void
     */
    public function __construct($sellReturn)
    {
        $this->sell_return = $sellReturn;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'id' => $this->sell_return->id,
            'invoice_no' => $this->sell_return->invoice_no,
            'approval_status' => $this->sell_return->status,
        ];
    }
}
