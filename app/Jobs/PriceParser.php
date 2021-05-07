<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\PriceParserFile;
use App\Parser;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Symfony\Component\DomCrawler\Crawler;
use Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PriceParser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * 
     * Файл 
     * @var PriceParserFile
     */
    protected $file;

    /**
      * Число попыток
     * @var int
     */
    public $tries=3;

    /**
     * страница с товарами
     * @var string
     */
    protected $pageUrl;

    /**
     * страница с товарами
     * @var string
     */
    protected $identifier;

    
    /**
     * __construct
     *
     * @param  PriceParserFile $file
     * @param  string $pageUrl
     * @param  string $identifier
     *
     * @return void
     */
    public function __construct(PriceParserFile $file, $pageUrl, $identifier)
    {
        $this->file = $file;
        $this->pageUrl = $pageUrl;
        $this->identifier = $identifier;


    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $inFile = collect(Excel::load(
            storage_path('app/').$this->file->path,
            function ($reader) {
            }
        )->toArray());
        $inFile = collect($inFile);
        $references = $inFile->filter(function($item){
            if($item['price'] == ''){
                return true;
            }
            return false;
        });
        if(!$references->count()){
            $this->file->is_ready = 1;
            $this->file->save();
            return true;
        }
        $productUrls = $this->getProductsUrls($this->file->parser, $this->pageUrl);
        $productsProperties =[];
        foreach ($productUrls as $url=>$value){
            $productProperties = $this->getProductProperties($this->file->parser,$url);
            $productsProperties[]= $productProperties;
        }
        $productsProperties = collect($productsProperties);
        $newFounded = $references->map(function($item) use ($productsProperties){
            $price = '';
            $oldprice = '';
            foreach ($productsProperties as $lot){
                if(strpos((string) $lot['reference'],(string) $item['reference'])) {
                    $price = $lot['price'];
                    $oldprice = $lot['oldPrice'];
                    break;
                };
            }
            $returned['reference'] = $item['reference'];
            $returned['price'] = $price;
            $returned['old price'] = $oldprice;
            return $returned;
        })->filter(function($item){
            if($item['price'] == ''){
                return false;
            }else{
                return true;
            }
        });
        $inFile = $inFile->map(function($item) use ($newFounded){
            if($item['price'] == '' && $newFounded->where('reference', (string) $item['reference'])->count() && $newFounded->where('reference', (string) $item['reference'])->first()['price'] != ''){
                return $newFounded->where('reference', (string) $item['reference'])->first();
            }
            return $item;
        });
        Excel::create(
            $this->file->name,
            function (LaravelExcelWriter $excel) use ($inFile){
                $excel->sheet(
                    'Sheet',
                    function (LaravelExcelWorksheet $sheet) use ($inFile){
                        $sheet->fromArray($inFile,null,'A1',true);
                    }
                );
            }
        )->store('csv', storage_path('app/public/parser/prices'));   
        if(DB::table('jobs')->where('payload','like','%'.$this->identifier.'%')->count() == 1){
            $this->file->is_ready = 1;
            $this->file->save();
        }
    }


    /**
     * Получение адресов ссылок на карточки товаров.
     *
     * @param \App\Parser $parser
     * @param string $link
     * @param array $accumulator
     * @return array
     */
    protected function getProductsUrls(Parser $parser, $link, &$accumulator = null)
    {
        if (is_null($accumulator)) {
            $accumulator = [
                'products' => [],
            ];
        }

        $crawler = $this->getCrawler($link,$parser);
        $productsUrls = $this->getLinksUrls($crawler, $parser->settings['productSelectorInList']);
        unset($crawler);
        foreach ($productsUrls as $productUrl) {
            if (! isset($accumulator['products'][$productUrl])) {
                $accumulator['products'][$productUrl] = 0;
            }
        }

        return $accumulator['products'];
    }


    /**
     * Получение свойств товара с карточки товара.
     *
     * @param \App\Parser $parser
     * @param string $link
     * @return array
     */
    protected function getProductProperties(Parser $parser, $link)
    {
        $productProperties = [];

        $crawler = $this->getCrawler($link,$parser);
   
        if ($crawler->filter($parser->settings['productReferenceSelector'])->count()) {
            $productProperties['reference'] = $crawler->filter($parser->settings['productReferenceSelector'])->text();
        } else {
            $productProperties['reference'] = null;
        }
        if ($crawler->filter($parser->settings['productPriceSelector'])->count()) {
            $matches = array();
            $price = null;
            $text = $this->strip_tags_content($crawler->filter($parser->settings['productPriceSelector'])->html());
            if(preg_match_all('/[+-]?([0-9]*[.])?[0-9]+/',$text, $matches)){
                foreach($matches[0] as $match){
                    $price.=$match;
                }
            }
            $productProperties['price'] = $price;
        } else {
            $productProperties['price'] = null;
        }
        if ($crawler->filter($parser->settings['productOldPriceSelector'])->count()) {
            $matches = array();
            $oldprice = null;
            if(preg_match_all('/[+-]?([0-9]*[.])?[0-9]+/',$crawler->filter($parser->settings['productOldPriceSelector'])->text(), $matches)){
                foreach($matches[0] as $match){
                    $oldprice.=$match;
                }
            }
            $productProperties['oldPrice'] = $oldprice;

        }else {
            $productProperties['oldPrice'] = null;
        }
        return $productProperties;
    }


     /**
     * Получение адресов ссылок по селектору.
     *
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @param $selector string
     * @return array
     */
    protected function getLinksUrls(Crawler $crawler, $selector)
    {
        return $crawler->filter($selector)->each(
            function (Crawler $node, $i) {
                $link = str_replace('?&', '?', $node->link()->getUri());
                return $link;
            }
        ); 
    }

    /**
     * Получение экземпляра Crawler.
     *
     * @param $link string
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getCrawler($link, $parser)
    {
        $html = file_get_contents($link);
        usleep($parser->interval);
        $crawler = new Crawler(null, $link);
        $crawler->addHtmlContent($html, 'UTF-8');
        return $crawler;
    }

    /**
     * strip tags with content
     *
     * @param $text
     * @return string|string[]|null
     */
    function strip_tags_content($text) {
        $text = preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        $text = preg_replace('@</(\w+)\b.*?>@si', '', $text);
            return preg_replace('@<(\w+)\b.*?>@si', '', $text);
    }
}
