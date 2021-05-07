<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class TelegramWebhookSet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook-set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register telegram webhook uri';

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
        $uri = config('telegram.bots.accounting_bot.webhook_url') .'/'. config('telegram.bots.accounting_bot.token');
        dump($uri);
        $client = new Client(['base_uri' => 'https://api.telegram.org/bot' . config('telegram.bots.accounting_bot.token') . '/']);
        try {
            $response = $client->request('POST',
                'setwebhook'
                ,
                [
                    'query' => [
                        'url' => $uri
                    ]
                ]);
            $response = json_decode($response->getBody());
            $this->info($response->description);
            return $response->ok;
        } catch (GuzzleException $e) {
            $this->error($e->getMessage());
            return false;
        } catch (\Exception $e){
            $this->error($e->getMessage());
            return false;
        }
    }
}
