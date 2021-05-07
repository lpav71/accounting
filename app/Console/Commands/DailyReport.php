<?php

namespace App\Console\Commands;

use App\Jobs\TelegramMessage;
use App\TelegramReportSetting;
use Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach (TelegramReportSetting::all() as $setting){
            $users = $setting->users->pluck('id');
            $timeFrom = Carbon::yesterday()->format('Y-m-d');
            $timeTo = Carbon::yesterday()->format('Y-m-d');
            $confirmTime = $setting->confirm_time;
            $orderStates = $setting->orderStates->pluck('id');
            $taskStates = $setting->taskStates->pluck('id');
            $orderDetailStates = $setting->orderDetailStates->pluck('id');
            $exitCode = Artisan::call('day:report', [
                '--users' => $users,
                '--timeFrom' => $timeFrom,
                '--timeTo' => $timeTo,
                '--confirmTime' => $confirmTime,
                '--orderStates' => $orderStates,
                '--taskStates' => $taskStates,
                '--orderDetailStates' => $orderDetailStates
            ]);
            $message = Artisan::output();
            $message = [
                'chat_id' => $setting->chat_id,
                'text' => "$message",
                'parse_mode' => 'Markdown',
            ];

            TelegramMessage::dispatch($message)->onQueue('telegram_message');
        }
    }
}
