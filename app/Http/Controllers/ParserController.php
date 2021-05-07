<?php

namespace App\Http\Controllers;

use App\Manufacturer;
use App\Parser;
use App\ParserProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Excel;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use App\PriceParserFile;
use Illuminate\Support\Facades\Storage;
use App\Jobs\PagesPriceParser;


class ParserController extends Controller
{
    /**
     * Тип парсера.
     *
     * @var null|string
     */
    protected $parserType = null;

    /**
     * Конструктор ParserController.
     */
    public function __construct()
    {
        $this->parserType = $this->getParserType();
        $this->middleware('permission:parsers-list',['except'=>['xmlProducts']]);
        $this->middleware('permission:parsers-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:parsers-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:parsers-delete', ['only' => ['destroy']]);
    }

    /**
     * Отображение списка парсеров.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /**
         * @var $parserType Parser
         */
        $parserType = $this->parserType;
        $parsers = $parserType::paginate(50);

        return view('product-parsers.index', compact('parsers'));
    }

    /**
     * Отображение формы создания парсера.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        /**
         * @var $parserType Parser
         */
        $parserType = $this->parserType;
        $parserValidatedParams = (new $parserType())->validatedParams;
        $settings = isset($parserValidatedParams['settings']) ? $parserValidatedParams['settings'] : [];

        return view('product-parsers.create', compact('settings'));
    }

    /**
     * Сохранение вновь созданного парсера в базе данных.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'interval' => 'nullable|integer',
        ]);
        /**
         * @var $parserType Parser
         */
        $parserType = $this->parserType;
        $parserType::create($request->input());

        return redirect()->route(
            preg_replace('/\.[\.A-Za-z]*/', '', Route::currentRouteName()).'.index'
        )->with('success', __('Parser created successfully'));
    }

    public function show($id)
    {
        $parser = Parser::find($id);
        return view('product-parsers.show', compact('parser'));
    }
    /**
     * Отображение формы редактирования парсера.
     *
     * @param  \App\Parser $parser
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $parser = Parser::find($id);
        $settings = isset($parser->validatedParams['settings']) ? $parser->validatedParams['settings'] : [];

        return view('product-parsers.edit', compact('parser', 'settings'));
    }

    /**
     * Обновление существующего парсера в базе данных.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Parser $parser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'interval' => 'required|integer',
        ]);
        $parser = Parser::find($id);
        $data = $request->input();
        $data['is_active'] = isset($data['is_active']);
        $parser->update($data);

        return redirect()->route(
            preg_replace('/\.[\.A-Za-z]*/', '', Route::currentRouteName()).'.index'
        )->with('success', __('Parser updated successfully'));
    }

    /**
     * getParserType
     *
     * @return void
     */
    protected function getParserType()
    {
        return 'App\\'.str_replace(
                '-',
                '',
                ucwords(preg_replace('/s\.[\.A-Za-z]*/', '', Route::currentRouteName()), '-')
            );
    }

    /**
     * xmlProducts 
     *
     * @param  mixed $manufacturer
     *
     * @return void
     */
    public function xmlProducts(Manufacturer $manufacturer)
    {
        app('debugbar')->disable();
        $products = ParserProduct::all()->where('manufacturer_id', $manufacturer->id);
        return response()->view('xml.products', compact('products', 'manufacturer'))->header('Content-type', 'text/xml');
    }

    /**
     * csvPrices method for parsing prices
     *
     * @param  mixed $request
     * @param  mixed $parser
     *
     * @return void
     */
    public function csvPrices(Request $request, Parser $parser){
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        if (!$file->getClientOriginalExtension() == 'csv' && !$file->getClientOriginalExtension() == 'CSV') {
            throw ValidationException::withMessages([__('Need a CSV file.')]);
        }
        $data = Excel::load(
            $path,
            function ($reader) {
            }
        )->toArray();
        $data = collect($data);
        $data = $data->map(function($item){
            return collect([
                'reference' => $item['reference'],
                'price' => '',
                'old price' => ''
            ]);
        })->filter(function($item){
            if($item['reference'] == ''){
                return false;
            }else{
                return true;
            }
        });
        $name = 'prices_'.$parser->name.'_'.Carbon::now()->format('d_m_Y_H_i_s');
        $fileInfo = Excel::create(
            $name,
            function (LaravelExcelWriter $excel) use ($data) {
                $excel->sheet(
                    'Sheet',
                    function (LaravelExcelWorksheet $sheet) use ($data) {
                        $sheet->fromArray($data,null,'A1',true);
                    }
                );
            }
        )->store('csv', storage_path('app/public/parser/prices'));
        $newFile = new PriceParserFile();
        $newFile->name = $name;
        $newFile->path = str_replace(storage_path('app/'),'',$fileInfo->storagePath.'/'.$fileInfo->filename.'.'.strtolower($fileInfo->format));
        $newFile->parser_id = $parser->id;
        $newFile->url = Storage::url($newFile->path);
        $newFile->save();
        $parserLinks = preg_split('/\r\n|\r|\n/', $parser->link);
        foreach($parserLinks as $parserLink){
            dispatch((new PagesPriceParser($newFile, $parserLink, md5(Carbon::now()->format('d_m_Y_H_i_s_uP'))))->onQueue('price_parser'));
        }
        return back();
    }

}
