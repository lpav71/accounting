<?php

namespace App\Console\Commands;

use App\Services\SecurityService\SecurityService;
use App\Store;
use Illuminate\Console\Command;

class CheckDailyLimitProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:limit';

    /**
     * @var SecurityService
     */
    private $service;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If count of products biggest daily limit send message telegram';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->service = new SecurityService();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $stores = Store::all();
        foreach ($stores as $store) {
            $this->service->checkDailyProductLimit($store);
        }
    }
}
