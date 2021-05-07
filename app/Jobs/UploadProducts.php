<?php

namespace App\Jobs;

use App\Services\Channel\UploadProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\PrestaProduct;

/**
 * Загружает товары на источник
 *
 * Class UploadProducts
 * @package App\Jobs
 *
 */
class UploadProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;


    /**
     * array of PrestaProduct ids.
     *
     * @var array
     */
    private $prestaProducts = [];

    /**
     * UploadProducts constructor.
     *
     * @param $prestaProducts
     */
    public function __construct($prestaProducts)
    {
        if(!is_array($prestaProducts)){
            $this->prestaProducts = [$prestaProducts];
        }else{
            $this->prestaProducts = $prestaProducts;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
            $prestaProducts = PrestaProduct::with(
                'product.combination.products.prestaProducts'
                , 'product.attributes'
                , 'product.characteristics'
                , 'product.manufacturer'
                , 'product.category'
                , 'product.pictures')->find($this->prestaProducts);

            UploadProductService::upload($prestaProducts);
    }



    /**
     * The job failed to process.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed(\Exception $e)
    {
        report($e);
    }
}
