<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Log;

class TelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * instance of message settings.
     *
     * @var array
     */
    private $message = [];

    /**
     * name of bot which use
     *
     * @var string
     */
    private $botName;

    /**
     * __construct
     *
     * @param array $message
     * @param string $botName
     *
     * @return void
     */
    public function __construct(array $message, string $botName = 'bot')
    {
        $this->message = $message;
        $this->botName = $botName;
    }

    /**
     * Execute the job.
     *
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function handle()
    {
        try {
        \Telegram::bot($this->botName)->sendMessage($this->message);
        }catch(Exception $e) {
            if ($this->attempts() < $this->tries) {
                $this->release(5);
            } else {
                throw $e;
            }
        }
    }


    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $e)
    {
        report($e);
    }

}
