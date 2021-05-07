<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Channel;
use Illuminate\Support\Facades\Log;
use App\PrestaProduct;
class DownloadProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Источник для выгрузки товаров
     *
     * @var Channel
     */
    private $channel;

    /**
     * Артикулы товаров
     *
     * @var array|string
     */
    private $references;

    /**
     * Необходимо ли скачивать все товары
     *
     * @var bool
     */
    private $send_all;

    /**
     * Необходимо ли обновлять товары каналов
     *
     * @var bool
     */
    private $is_update;

    /**
     * Необходимо ли обновлять товары каналов и главный товар
     *
     * @var bool
     */
    private $update_main;

    /**
     * DownloadProducts constructor.
     * @param Channel $channel
     * @param array|string $references
     * @param bool $send_all
     * @param bool $is_update
     * @param bool $update_main
     */
    public function __construct(Channel $channel, $references = null, $send_all = false, $is_update = false, $update_main = false)
    {
        $this->channel = $channel;
        $this->references = $references;
        $this->send_all = $send_all;
        $this->is_update = $is_update;
        $this->update_main = $update_main;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            PrestaProduct::downloadFromChannel($this->channel, $this->references, $this->send_all, $this->is_update, $this->update_main);
        } catch (\Exception $e) {
            if ($this->attempts() < $this->tries) {
                $this->release(15);
            } else {
                throw $e;
            }
        }
    }



    /**
     * The job failed to process.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed(\Exception $e)
    {
        Log::error('Bottom error is for '.$this->references);
        report($e);
    }
}
