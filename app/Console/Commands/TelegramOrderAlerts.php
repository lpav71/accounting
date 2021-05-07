<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TelegramMessage;
use App\Order;
use Log;
use App\OrderAlert;
use Carbon\Carbon;

class TelegramOrderAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:orderAlerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send orders alerts in TG';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    /**
     * @var string
     */
    private $botName = 'bot';


    /**
     * Отправка сообщения в телеграм
     */
    protected function sendTelegramMessage($message)
    {
        Log::channel('orders_alerts_log')->info($message);
        TelegramMessage::dispatch(
            [
                'chat_id' => config('telegram.bots.bot.chat'),
                'text' => $message . PHP_EOL,
                'parse_mode' => 'Markdown',
            ],
            $this->botName
        )->onQueue('telegram_message');
    }
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Функция отправки неотзвоннеых заказов
     * Если заказ висит без отзвона > $trashold (минуты), бот шлёт в чат алерт
     *
     * @return mixed
     */
    public function handle()
    {
        $all_trasholds = OrderAlert::all();
        $orders = Order::where('is_hidden', 0)
            ->where('created_at', '>', Carbon::now()->subDays(1))
            ->orderBy('id', 'desc')
            ->get();
        foreach ($orders as $order) {
            $subtraction =  Carbon::now()->diffInMinutes($order->created_at);
            foreach ($all_trasholds as $single_trashold) {
                if ($order->currentState()->is_new === 1 && $order->tasks->count() === 0 && $subtraction > $single_trashold->trashold && $order->orderAlerts()->where('trashold_id', $single_trashold->id)->exists() === false) {

                    $message = __(
                        'The new order:order has no tasks for more than :trashold minutes',
                        [
                            'order' => route('orders.edit', ['id' => $order->id]),
                            'trashold' => $single_trashold->trashold
                        ]
                    );
                    $order->OrderAlerts()->attach($single_trashold->id);
                    $this->sendTelegramMessage($message);
                }
            }
        }
    }
}
