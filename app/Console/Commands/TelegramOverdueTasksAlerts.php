<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TelegramMessage;
use App\OverdueTask;
use Log;
use App\Task;
use Carbon\Carbon;

class TelegramOverdueTasksAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:overdueTasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send overdue tasks to telegram';
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
        $tasks = Task::where('created_at', '>', Carbon::now()->subDays(1))
        ->orderBy('id', 'desc')
        ->get();
        $trasholds = OverdueTask::all();
        $overdue_tasks = [];
        foreach ($tasks as $task) {
            foreach ($trasholds as $trashold) {
                if (!is_null($task->deadline_date) && !is_null($task->deadline_time)) {
                    $carbonTime = Carbon::createFromFormat('H:i', $task->deadline_time);
                    $taskDeadlineTime = Carbon::createFromFormat('d-m-Y', $task->deadline_date)
                        ->setTime((int) $carbonTime->hour, (int) $carbonTime->minute, 0, 0);
                    if ($taskDeadlineTime->getTimestamp() < Carbon::now()->getTimestamp()  && $task->overdueTasks()->where('trashold_id', $trashold->id)->exists() === false && $task->currentState()->is_closed !== 1) {
                        $overdue_tasks[] = $task;
                    }
                }
            }
        }
        if (count($overdue_tasks) >= $trashold->trashold) {
            $message = __('There are overdue tasks:') . "\n";
            foreach ($trasholds as $trashold) {
                foreach ($overdue_tasks as $overdue_task) {
                    if ($overdue_task->overdueTasks()->where('trashold_id', $trashold->id)->exists() === false) {
                        $overdue_task->overdueTasks()->attach($trashold->id);
                        $message .= __(
                            ':task',
                            [
                                'task' => route('tasks.edit', ['id' => $overdue_task->id]),
                            ]
                        ) . "\n";
                    }
                }
            }
            $this->sendTelegramMessage($message);
        }
    }
}
