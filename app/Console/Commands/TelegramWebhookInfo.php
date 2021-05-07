<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class TelegramWebhookInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook-info';

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
        $client = new Client(['base_uri' => 'https://api.telegram.org/bot' . config('telegram.bots.accounting_bot.token') . '/']);
        try {
            $response = $client->request('POST', 'getWebhookInfo');
            $response = json_decode($response->getBody());
            dump($response);
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
