<?php

namespace App\Console\Commands;

use App\Channel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\PrestaProduct;

class ChannelProductsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:import-products 
                                {--channel=* : coma is separator} 
                                {--reference=* : references to import} 
                                {--all=0 : true if need download all products}
                                {--update=0 : true if need update only channel product}
                                {--update_main=0 : true if need update channel and main product}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download ALL products and UPDATE their parameters from channels';

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
     * Вызывает статическую функцию App\PrestaProduct::downloadFromChannel с параметрами, заданными через консольную команду.
     *
     *
     * @return mixed
     */
    public function handle()
    {
        foreach($this->getChannels() as $channel_id){
            try{
                $channel = Channel::find($channel_id);
                if(empty($channel->upload_key)) continue;
                if(empty($channel->download_address)) continue;
                PrestaProduct::downloadFromChannel($channel, $this->getReferences(), $this->getIsAll(), $this->getIsUpdate(), $this->getIsUpdateMain());    
            }catch(\Exception $e){
                report($e);
            }
        }
    }

    /**
     * массив в id каналов из опции {--channel=* : coma is separator}
     *
     * @return array|string|null
     */
    private function getChannels(){
        return $this->option('channel');
    }

    /**
     * массив с артикулами товаров оз опции {--reference=* : references to import}
     *
     * @return array
     */
    private function getReferences(){
        return $this->option('reference');
    }

    /**
     * необходимо ли скачивать все товары {--all=0 : true if need download all products}
     *
     * @return int
     */
    private function getIsAll(){
        return $this->option('all');
    }

    /**
     * необходимо ли обновлять товары каналов {--update=0 : true if need update only channel product}
     *
     * @return int
     */
    private function getIsUpdate(){
        return $this->option('update');
    }

    /**
     * необходимо ли обновлять товары каналов и главный товар {--update_main=0 : true if need update channel and main product}
     *
     * @return int
     */
    private function getIsUpdateMain(){
        return $this->option('update_main');
    }
}
