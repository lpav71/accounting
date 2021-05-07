<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Mail;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 
     * Заказ
     * @var
     */
    protected $order;

    /**
     * HTML письма
     * @var
     */
    protected $finalHtml;

     /**
      * Число попыток
     * @var
     */
    public $tries=3;

    /**
     * объект шаблона уведомления
     * @var
     */
    public $emailTemplate;

    /**
     * SendNotificationEmail constructor.
     * @param $order
     * @param $finalHtml
     */
    public function __construct($order, $finalHtml,$emailTemplate)
    {
        $this->order=$order;
        $this->finalHtml=$finalHtml;
        $this->emailTemplate=$emailTemplate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order=$this->order;
        $channel=$order->channel;
        config(['mail.username' => $channel->smtp_username]);
        config(['mail.password' => $channel->smtp_password]);
        config(['mail.encryption' => $channel->smtp_encryption]);
        config(['mail.host' => $channel->smtp_host]);
        config(['mail.port' => $channel->smtp_port]);
        Mail::send('mails.default',['html'=>$this->finalHtml],function ($message) use ($order) {
            $message->from($order->channel->notifications_email, $order->channel->template_name);
            $message->to($order->customer->email, $order->customer->first_name)->subject($this->emailTemplate->email_subject);
        });
    }
}
