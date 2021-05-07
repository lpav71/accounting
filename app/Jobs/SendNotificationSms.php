<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use TheSeer\Tokenizer\Exception;

class SendNotificationSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Финальный запрос для СМС 
     * @var
     */
    protected $smsQuery;



    /**
      * Число попыток
     * @var
     */
    public $tries=3;



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($smsQuery)
    {
        $this->smsQuery=$smsQuery;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new \GuzzleHttp\Client();
        $client->get($this->smsQuery);
        if($client->getStatusCode()!=200){
            throw new Exception('SMS didn`t send');
        }
    }
}
