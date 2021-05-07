<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\PriceParserFile;
use App\Parser;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class PagesPriceParser implements ShouldQueue
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
     * @var
     */
    public $tries=3;

    /**
     * страница с товарами
     * @var
     */
    protected $parserLink;

    /**
     * страница с товарами
     * @var
     */
    protected $identifier;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file, $parserLink, $identifier)
    {
        $this->file = $file;
        $this->parserLink = $parserLink;
        $this->identifier = $identifier;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pagesUrls = $this->getPages($this->file->parser, $this->parserLink);
            foreach($pagesUrls as $pageUrl => $val){
                dispatch((new PriceParser($this->file, $pageUrl, $this->identifier))->onQueue('price_parser'));
            }
    }

    /**
     * get pages with product lists
     *
     * @param  mixed $parser
     * @param  mixed $link
     * @param  mixed $accumulator
     *
     * @return void
     */
    public function getPages(Parser $parser, $link, &$accumulator = null){
        if (is_null($accumulator)) {
            $accumulator = [
                'pages' => [],
            ];
            $accumulator['pages'][$link] = 1;
        }
        $crawler = $this->getCrawler($link, $parser);

        $pagesUrls = $this->getPagesLinksUrls($crawler, $parser->settings['pageSelector']);
        unset($crawler);
        foreach ($pagesUrls as $pageUrl) {
            if (! isset($accumulator['pages'][$pageUrl])) {
                $accumulator['pages'][$pageUrl] = 0;
            }
        }

        foreach ($accumulator['pages'] as $pageUrl => $pageCrawled) {
            if (! $pageCrawled) {
                $accumulator['pages'][$pageUrl] = 1;
                $this->getPages($parser, $pageUrl, $accumulator);
            }
        }

        return $accumulator['pages'];
    }

    /**
     * Получение экземпляра Crawler.
     *
     * @param $link string
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function getCrawler($link,$parser)
    {
        $html = file_get_contents($link);
        usleep($parser->interval);
        $crawler = new Crawler(null, $link);
        $crawler->addHtmlContent($html, 'UTF-8');
        return $crawler;
    }

    /**
     * Получение адресов ссылок по селектору.
     *
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @param $selector string
     * @return array
     */
    protected function getPagesLinksUrls(Crawler $crawler, $selector)
    {
        return $crawler->filter($selector)->each(
            function (Crawler $node, $i) {
                $link = str_replace('?&', '?', $node->link()->getUri());
                $matches = null;
                if(!preg_match_all('/^(ftp|http|https):\/\/[^ "]+$/',$link) && preg_match_all('/page=(\d+)/', $node->getUri(), $matches)){
                    $nextPage = $matches[1][0] + 1;
                    $link = preg_replace('/page=(\d+)/','page='.$nextPage, $node->getUri());
                }
                return $link;
            }
        ); 
    }
}
