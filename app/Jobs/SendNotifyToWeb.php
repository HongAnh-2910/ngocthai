<?php

namespace App\Jobs;

use App\DeviceToken;
use App\Notifications\TransactionPaymentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\User;
use App\TransactionPayment;

class SendNotifyToWeb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $payment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, TransactionPayment $payment)
    {
        $this->user = $user;
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->user->notify(new TransactionPaymentNotification($this->payment));
        $device_token = new DeviceToken();
        $device_token->sendNotifyWeb($this->user->id, $this->payment);
    }
}
