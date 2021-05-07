<?php

namespace App\Console\Commands;

use App\Manufacturer;
use App\Product;
use Illuminate\Console\Command;
use \FileParser;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import {file} {need_guarantee_item=categoryId} {need_guarantee_value?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import of products from external sources';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = FileParser::xml(file_get_contents($this->argument('file')));
        if (isset($data['shop']['offers']['offer'])) {
            foreach ($data['shop']['offers']['offer'] as $offer) {
                $offer = collect($offer)->map(function ($item, $key) {
                    return is_string($item) ? trim($item) : $item;
                });
                if (strlen($offer->get('vendorCode'))) {
                    $product = Product::where('reference', $offer->get('vendorCode'))->first();
                    if (!$product) {
                        $product = Product::create([
                            'name' => $offer->get('name').' ('.$offer->get('vendorCode').')',
                            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => $offer->get('vendor')])->id,
                            'reference' => $offer->get('vendorCode'),
                        ]);
                    }

                    if ($this->hasArgument('need_guarantee_value') && $offer->has($this->argument('need_guarantee_item')) && (string) $offer->get($this->argument('need_guarantee_item')) == (string) $this->argument('need_guarantee_value')) {
                        $product->update([
                            'name' => $offer->get('name').' ('.$offer->get('vendorCode').')',
                            'need_guarantee' => 1
                        ]);
                    } else {
                        $product->update([
                            'name' => $offer->get('name').' ('.$offer->get('vendorCode').')',
                            'need_guarantee' => 0
                        ]);
                    }
                }
            }
        }
    }
}
