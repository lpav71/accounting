<?php

namespace App\Console\Commands;

use App\CampaignId;
use App\UtmCampaign;
use Illuminate\Console\Command;
use App\Vendors\ClickHouse\Client;

class UpdateUtmIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'utmids:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update campaign_ids ids and utm_campaign';

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
        /**
         * Массив настроек БД
         * @var array $dbConfig
         */
        $dbConfig = [
            'host' => config('clickhouse.host'),
            'port' => config('clickhouse.port'),
            'username' => config('clickhouse.username'),
            'password' => config('clickhouse.password'),
        ];

        /**
         * Клиент БД
         * @var Client $db
         */
        $database = new Client($dbConfig);
        $database->settings()->max_execution_time(200);
        $databases = $database->showDatabases();
        foreach($databases as $keyDb => $db){
            $dbName = $db['name'];
            dump($dbName);

            if (!$database->isExists($dbName, 'watches')){
                    continue;
                }
            $hits = $database->select("SELECT uri as uri FROM {$dbName}.watches WHERE match(uri,'campaign_id=.*utm_campaign=|utm_campaign=.*campaign_id=')")->rows();
            foreach($hits as $key => $hit){
                
                $utm_campaign = null;
                preg_match('/utm_campaign=([a-zA-Z0-9\-\_\.]+)/i',$hit['uri'], $utm_campaign);
                if(!isset($utm_campaign[1])){
                    continue;
                }
                $utm_campaign = $utm_campaign[1];

                $utm_campaign_id = null;
                preg_match('/campaign_id=([a-zA-Z0-9\-\_\.]+)/i',$hit['uri'], $utm_campaign_id);
                if(!isset($utm_campaign_id[1])){
                    continue;
                }
                $utm_campaign_id = $utm_campaign_id[1];
                if(!empty($utm_campaign) && !empty($utm_campaign_id)){
                    dump($utm_campaign_id.' '.$utm_campaign);
                    $utmCampaign = UtmCampaign::firstOrCreate(['name'=> $utm_campaign]);
                    $campaignId = CampaignId::firstOrCreate(['campaign_id' => $utm_campaign_id]);
                    if($campaignId->utm_campaign_id != $utmCampaign->id){
                        $campaignId->update(['utm_campaign_id' => $utmCampaign->id]);
                    }
                }
                unset($hits[$key]);
            }
            unset($databases[$keyDb]);
        }
    }
}
