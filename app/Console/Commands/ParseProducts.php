<?php

namespace App\Console\Commands;

use App\Manufacturer;
use App\Parser;
use App\ParserProduct;
use App\ProductParser;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ParseProducts extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'products:parse {update?}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Parse products from external sources';

    /**
     * Запуск консольной команды.
     */
    public function handle()
    {
        /**
         * @var ProductParser $parser
         */
        foreach (ProductParser::all()->where('is_active', 'true') as $parser) {
            if ($parser) {
                $urls = $this->getProductsUrls($parser, $parser->link);
                dump($urls);
                foreach (array_keys($urls) as $url) {
                    if (! $parser->parserProducts()->where('link', $url)->count() || $this->hasArgument('update')) {
                            $productProperties = $this->getProductProperties($parser, $url);
                        if ($productProperties) {
                            $manufacturer = Manufacturer::firstOrCreate(['name' => $productProperties['manufacturer']]);
                            $parserProduct = ParserProduct::all()->where('reference', $productProperties['reference'])->first();
                            $productProperties['channelCategory'] = $parser->settings['channelCategory'];
                            if($parserProduct) {
                                $parserProduct->update([
                                    'name' => $productProperties['model'],
                                    'manufacturer_id' => $manufacturer->id,
                                    'properties' => $productProperties,
                                ]);
                            } else {
                                $parserProduct = ParserProduct::create([
                                    'name' => $productProperties['model'],
                                    'reference' => $productProperties['reference'],
                                    'manufacturer_id' => $manufacturer->id,
                                    'properties' => $productProperties,
                                ]);
                            }
                            dump($productProperties['model']);

                            $parser->parserProducts()->save($parserProduct, [
                                'price' => $productProperties['price'],
                                'link' => $url,
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Получение экземпляра Crawler.
     *
     * @param $link string
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getCrawler($link)
    {
        $html = file_get_contents($link);
        $crawler = new Crawler(null, $link);
        $crawler->addHtmlContent($html, 'windows-1251');

        return $crawler;
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
                return str_replace('?&', '?', $node->link()->getUri());
            }
        );
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
                'pages' => [],
            ];
        }

        $crawler = $this->getCrawler($link);

        $productsUrls = $this->getLinksUrls($crawler, $parser->settings['productSelectorInList']);
        foreach ($productsUrls as $productUrl) {
            if (! isset($accumulator['products'][$productUrl])) {
                $accumulator['products'][$productUrl] = 0;
            }
        }
       // $pagesUrls = $this->getLinksUrls($crawler, 'div.pagination-inline ul a');
        $pagesUrls = $this->getAlltimePages($link);
        foreach ($pagesUrls as $pageUrl) {
            if (! isset($accumulator['pages'][$pageUrl])) {
                $accumulator['pages'][$pageUrl] = 0;
            }
        }

        unset($crawler);
        foreach ($accumulator['pages'] as $pageUrl => $pageCrawled) {
            if (! $pageCrawled) {
                $accumulator['pages'][$pageUrl] = 1;
                $this->getProductsUrls($parser, $pageUrl, $accumulator);
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

        $crawler = $this->getCrawler($link);

        if ($crawler->filter($parser->settings['productManufacturerSelector'])->count()) {
            $productProperties['manufacturer'] = $crawler->filter($parser->settings['productManufacturerSelector'])->attr('content');
        } else {
            return [];
        }
        if ($crawler->filter($parser->settings['productModelSelector'])->count()) {
            $productProperties['model'] = $crawler->filter($parser->settings['productModelSelector']);
        } else {
            return [];
        }
        if ($crawler->filter($parser->settings['productReferenceSelector'])->count()) {
            $productProperties['reference'] = $crawler->filter($parser->settings['productReferenceSelector']);
            $matches = [];
            dump($productProperties['reference']);
            preg_match('/(\S+)$/i',$productProperties['reference'], $matches);
            $productProperties['reference'] = $matches[0];
        } else {
            return [];
        }
        if ($crawler->filter($parser->settings['productPriceSelector'])->count()) {
            $productProperties['price'] = (float)preg_replace('/[\sА-Яа-яA-Za-z]/','',$crawler->filter($parser->settings['productPriceSelector'])->attr('content'));
        } else {
            return [];
        }
        if ($crawler->filter($parser->settings['productOldPriceSelector'])->count()) {
            $productProperties['oldPrice'] = (float)preg_replace('/[\sА-Яа-яA-Za-z]/','',$crawler->filter(
                $parser->settings['productOldPriceSelector']
            )->text());
        }
        $productProperties['images'] = $crawler->filter($parser->settings['productImageSelector'])->each(
            function (Crawler $node, $i) {
                return 'https://www.alltime.ru'.$node->attr('data-full-src');
            }
        );
        $productProperties['attributes'] = $crawler->filter($parser->settings['productAttributeSelector'])->each(
            function (Crawler $node, $i) use ($parser) {
                return [
                    'name' => $node->filter($parser->settings['productAttributeSelector-Name'])->text(),
                    'value' => $node->filter($parser->settings['productAttributeSelector-Value'])->text(),
                ];
            }
        );

        return ($productProperties['images'] && $productProperties['attributes']) ? $productProperties : [];
    }

    protected function getAlltimePages(string $firstPage){
        if(stripos($firstPage , '?PAGEN_1=')){
            return [];
        }
        $pages = [];
        for ($i = 1; $i <= 7; $i++) {
            $pages[] = $firstPage.'?PAGEN_1='.$i;
        }
        return $pages;
    }
}
