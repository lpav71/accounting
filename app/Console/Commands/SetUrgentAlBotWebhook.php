<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetUrgentAlBotWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setUrgentCall:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set webhook to urgent Al telegram bot';

    /**
     * @var false|\Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */
    private $token;

    /**
     * @var false|\Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */
    private $webhookUrl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->token = config('telegram.bots.urgentAl_bot.token');
        $this->webhookUrl = config('telegram.bots.urgentAl_bot.webhook_url');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = "https://api.telegram.org/bot$this->token/setWebhook?url=$this->webhookUrl/$this->token";
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $out = curl_exec($curl);
            curl_close($curl);
        }
    }
}
