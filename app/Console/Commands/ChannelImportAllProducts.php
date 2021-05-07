<?php

namespace App\Console\Commands;

use App\Channel;
use App\Jobs\DownloadProducts;
use Illuminate\Console\Command;

class ChannelImportAllProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:import-all-products';

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
        $channels = Channel::all();
        foreach ($channels as $channel) {
            if(empty($channel->upload_key)) continue;
            if(empty($channel->download_address)) continue;
            DownloadProducts::dispatch($channel, null, true, true, false)->onQueue('download_products');
        }
    }
}
