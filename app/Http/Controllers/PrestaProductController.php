<?php

namespace App\Http\Controllers;

use App\Category;
use App\Channel;
use App\Http\Resources\PrestaProduct as PrestaProductResource;
use App\Jobs\DownloadProducts;
use App\Jobs\UploadProducts;
use App\Manufacturer;
use App\PrestaProduct;
use App\Product;
use App\ReductionHistory;
use App\Services\Channel\UploadProductService;
use DB;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Readers\LaravelExcelReader;

/**
 * Class PrestaProductController
 * @package App\Http\Controllers
 */
class PrestaProductController extends Controller
{
    /**
     * PrestaProductController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:product-list', ['only' => ['index']]);
        $this->middleware('permission:product-edit', ['except' => ['index']]);
    }

    /**
     * отправляет PrestaProduct и атрибуты относящиеся к нему
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request){
        PrestaProductResource::withoutWrapping();
        $prestaProduct = PrestaProduct::where('product_id',$request->product_id)->where('channel_id',$request->channel_id)->first();
        if (!isset($prestaProduct)){
            $prestaProduct= new PrestaProduct();
            $prestaProduct->product_id=$request->product_id;
            $prestaProduct->channel_id=$request->channel_id;
        }
        return response()->json(
                [
                    'data'=>[
                        'product'=>new PrestaProductResource($prestaProduct)
                    ]
                ]
        );
    }

    /**
     * Создаёт новый PrestaProduct и записывает его атрибуты
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        $request->validate([
            'product.price' => 'required|numeric',
            'product.price_discount' => 'nullable|numeric',
        ]);
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();
        $prestaProduct = PrestaProduct::create($request->product);
        $collection = collect();
        $collection->push($prestaProduct);
        UploadProductService::upload($collection);
        DB::commit();
        return response()->json([
            'message' => __('Saved'),
            'data' => [
                'product' => new PrestaProductResource(PrestaProduct::find($prestaProduct->id))
            ]
        ], 200);

    }

    /**
     * Обновляет PrestaProduct и его атрибуты
     *
     * @param Request $request
     * @param PrestaProduct $prestaProduct
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PrestaProduct $prestaProduct)
    {

        $request->validate([
            'product.price' => 'required|numeric',
            'product.price_discount' => 'nullable|numeric',
        ]);
        DB::connection()->unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
        DB::beginTransaction();
        $prestaProduct->update($request->product);
        $collection = collect();
        $collection->push($prestaProduct);
        UploadProductService::upload($collection);
        DB::commit();
        return response()->json([
            'message' => __('Saved'),
            'data' => [
                'product' => new PrestaProductResource(PrestaProduct::find($prestaProduct->id))
            ]
        ], 200);
    }

    /**
     * Добавляет в очередь скачивание товаров с источника App\Jobs\UploadProducts
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function download(Request $request)
    {
        $references = explode(',', $request->references);
        $channel_ids = $request->channel_id;
        $channel_ids = is_array($channel_ids) ? $channel_ids : compact('channel_ids');
        foreach ($channel_ids as $channel_id) {
            $channel = Channel::find($channel_id);
            if (empty($channel->upload_key)) continue;
            if (empty($channel->download_address)) continue;
            $references = array_chunk($references, 20);
            foreach ($references as $referencesChunk) {
                DownloadProducts::dispatch($channel, $referencesChunk, $request->is_all ?? 0, $request->is_update ?? 0, $request->update_main ?? 0)->onQueue('download_products');
            }
        }
        return back();
    }

    /**
     * Добавляет в очередь загрузку товаров на источник App\Jobs\DownloadProducts
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function upload(Request $request)
    {
        $channel_ids = $request->channel_id;
        $channel_ids = is_array($channel_ids) ? $channel_ids : compact('channel_ids');
        $prestaProducts = DB::table('presta_products')
            ->whereIn('channel_id', $channel_ids)
            ->join('products', 'presta_products.product_id', '=', 'products.id');
        if (!empty($request->references)) {
            $references = explode(',', $request->references);
            $prestaProducts = $prestaProducts->whereIn('products.reference', $references);
        }
        if (!empty($request->manufacturers)) {
            $prestaProducts = $prestaProducts->whereIn('products.manufacturer_id', $request->manufacturers);
        }
        $prestaProducts = $prestaProducts->select('presta_products.id')->get();
        $prestaProducts = $prestaProducts->pluck('id')->toArray();
        $prestaProducts = array_chunk($prestaProducts, 20);
        foreach ($prestaProducts as $prestaProductChunk){
            UploadProducts::dispatch($prestaProductChunk)->onQueue('upload_products');
        }

        return back();
    }

    /**
     * Обновляет товар канала с источника
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws GuzzleException
     * @throws \ImagickException
     */
    public function updateFromChannel(Request $request)
    {

        PrestaProduct::downloadFromChannel(Channel::find($request->channel_id), Product::find($request->product_id)->reference, false, true, $request->update_main);
        $prestaProduct = PrestaProduct::where('channel_id',$request->channel_id)->where('product_id',$request->product_id)->first();
        if(!$prestaProduct){
            return response()->json([
                'errors'=>[
                    'product'=>[__('Not existed')],
                ],
            ],500);
        }
        return response()->json(
            [
                'data'=>[
                    'product'=>new PrestaProductResource($prestaProduct)
                ],
                'message'=> __('Updated')
            ]
    );
    }

    /**
     * Копирует товары с одного источника на другой
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function copyToChannel(Request $request)
    {
        $request->validate([
            'channels' => 'required',
        ]);
        $prestaProducts = DB::table('presta_products')
            ->where('channel_id', $request->channel_id)
            ->join('products', 'presta_products.product_id', '=', 'products.id');

        if (!empty($request->references)) {
            $references = explode(',', $request->references);
            $prestaProducts = $prestaProducts->whereIn('products.reference', $references);
        }
        if (!empty($request->manufacturers)) {
            $prestaProducts = $prestaProducts->whereIn('products.manufacturer_id', $request->manufacturers);
        }
        $prestaProducts = $prestaProducts->select('presta_products.id')->get();
        $prestaProducts = $prestaProducts->pluck('id')->toArray();
        $prestaProducts = PrestaProduct::find($prestaProducts);
        $prestaProductsIds = [];
        $prestaProducts->map(function ($item) use ($request, &$prestaProductsIds) {
            foreach ($request->channels as $channel) {
                $newItem = PrestaProduct::firstOrNew([
                    'product_id' => $item->product_id,
                    'channel_id' => $channel
                ]);
                if($newItem->exists() && !$request->override && $newItem->price > 0){
                    continue;
                }
                $newItem->fill($item->toArray());
                $newItem->channel_id = $channel;
                $newItem->save();
                $prestaProductsIds[] = $newItem->id;
            }
        });
        if($request->update_on_channel){
            $prestaProducts = array_chunk($prestaProductsIds, 20);
            foreach ($prestaProducts as $prestaProductChunk) {
                UploadProducts::dispatch($prestaProductChunk)->onQueue('upload_products');
            }
        }
        return back();
    }


    public function reductionPrice(Request $request)
    {
        $reductionValidation = 'required|numeric|min:0';
        if ($request->is_percent) {
            $reductionValidation = 'required|numeric|between:0,100';
        }
        $this->validate($request, [
            'reduction' => $reductionValidation,
            'channel_id' => 'required'
        ]);
        $errorMessages = collect();
        $prestaProducts = DB::table('presta_products')
            ->where('channel_id', $request->channel_id)
            ->join('products', 'presta_products.product_id', '=', 'products.id');
        
        if (!empty($request->references)) {
            $references = explode(',', $request->references);
            $prestaProducts = $prestaProducts->whereIn('products.reference', $references);
        }
        if (!empty($request->manufacturers)) {
            $prestaProducts = $prestaProducts->whereIn('products.manufacturer_id', $request->manufacturers);
        }
        if (!empty($request->categories)) {
            $prestaProducts = $prestaProducts->whereIn('products.category_id', $request->categories);
        }
        $prestaProducts = $prestaProducts->select('presta_products.id')->get()->pluck('id')->toArray();
        $prestaProducts = PrestaProduct::with('product','product.combination.products.prestaProducts')->find($prestaProducts);
        $prestaProductsUploads = [];
        $prestaProducts->map(function (PrestaProduct $prestaProduct) use ($request, &$prestaProductsUploads, &$errorMessages) {
            if($request->is_percent){
                $prestaProduct->price_discount = $prestaProduct->price * (1 - $request->reduction / 100);
            }else{
                $prestaProduct->price_discount = $prestaProduct->price - $request->reduction;
            }
            $prestaProduct->price_discount = floor($prestaProduct->price_discount / pow(10,$request->rounding)) * pow(10,$request->rounding);
            if($prestaProduct->price_discount == $prestaProduct->getOriginal('price_discount')){
                return;
            }
            if($prestaProduct->price_discount <= 0){
                $errorMessages->push(
                    __(
                        'Price below zero :reference.',
                        [
                            'reference' => $prestaProduct->product->reference,
                        ]
                    )
                );
                return;
            }
            $prestaProduct->save();
            $prestaProductsUploads[] = $prestaProduct;
        });
        if($request->update_reduction_on_channel && !empty($prestaProductsUploads)){
            UploadProductService::uploadPrices(collect($prestaProductsUploads));
        }
        $manufacturers = Manufacturer::find($request->manufacturers);
        $categories = Category::find($request->categories);
        $text = '';
        if ($request->is_percent) {
            $text .=  __('Percent');
        } else {
            $text .=  __('Ruble');
        }
        $text .= ' ' . strip_tags($request->reduction);
        $text .= '<br>' . __('References') . ' : ' . strip_tags($request->references);
        $text .= '<br>' . __('Manufacturers') . ':';
        if (!empty($manufacturers)) {
            foreach ($manufacturers as $manufacturer) {
                $text .= strip_tags($manufacturer->name) . ',';
            }
        }
        $text .= '<br>' . __('Categories') . ':';
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $text .= strip_tags($category->name) . ',';
            }
        }
        ReductionHistory::create([
            'channel_id' => $request->channel_id,
            'text' => $text
        ]);
        return back()->withErrors($errorMessages);
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function enableOnlyCsv(Request $request)
    {
        $file = $request->file('csv_file');
        if (empty($file) || $file->getClientOriginalExtension() != 'csv') {
            throw ValidationException::withMessages([__('Fill : ') . __('csv file')]);
        }
        $path = $file->getRealPath();
        /**
         * @var LaravelExcelReader $excel
         */
        $excel = Excel::load(
            $path,
            function ($reader) {
            }
        );
        $enabledReferences = $excel->get()
            ->filter(function ($item) {
                return $item->reference != null;
            })->pluck('reference')->toArray();
        $allPrestaProducts = PrestaProduct::where('channel_id', $request->input('channel_id'))
            ->with('product')
            ->get();
        $allPrestaProducts = $allPrestaProducts->map(function (PrestaProduct $prestaProduct) use ($enabledReferences) {            
            if(in_array($prestaProduct->product->reference, $enabledReferences)){
                $prestaProduct->is_active = true;
            }else{
                $prestaProduct->is_active = false;
            }
            $prestaProduct->save();
            return $prestaProduct;
        });
        UploadProductService::uploadAvailability($allPrestaProducts);
        return back();
    }
    
    public function newEnableOnlyCsv(Request $request){
        $file = $request->file('xlsx_file');
        if (empty($file) || $file->getClientOriginalExtension() != 'xlsx') {
            throw ValidationException::withMessages([__('Fill : ') . __('xlsx file')]);
        }
        $path = $file->getRealPath();
        $excel = Excel::load(
            $path,
            function ($reader) {
            }
        );
        $enabledReferences = $excel->get()
            ->filter(function ($item) {     
                return $item->reference != null;
            })->toArray();   
        $allPrestaProducts = PrestaProduct::where('channel_id', $request->input('channel_id'))
            ->with('product')
            ->get();
        $allPrestaProducts = $allPrestaProducts->map(function (PrestaProduct $prestaProduct) use ($enabledReferences) {
            $key = array_search($prestaProduct->product->reference, array_column($enabledReferences, 'reference'));
            if($key != false){
                if($enabledReferences[$key]['quantity']>0){
                    $prestaProduct->is_active = true;
                }else{
                    $prestaProduct->is_active = false;
                }
            }
            $prestaProduct->save();
            return $prestaProduct;
        });
        UploadProductService::uploadAvailability($allPrestaProducts);
        return back();
    }

    /**
     * @param Request $request
     */
    public function updatePricesXlsx(Request $request)
    {
        $file = $request->file('xlsx_file');
        if (empty($file) || $file->getClientOriginalExtension() != 'xlsx') {
            throw ValidationException::withMessages([__('Fill : ') . __('xlsx file')]);
        }
        $path = $file->getRealPath();
        /**
         * @var LaravelExcelReader $rows
         */
        $rows = Excel::load(
            $path,
            function ($reader) {
            }
        );
        $rows = $rows->get()
            ->filter(function ($item) {
                return $item->reference != null;
            });
        $rules = [];
        foreach ($rows as $row) {
            $rules[mb_strtolower($row->reference)] = (int)$row['price'];
        }
        $prestaProducts = PrestaProduct::where('channel_id', $request->channel_id)
            ->join('products', 'presta_products.product_id', '=', 'products.id')
            ->whereIn('products.reference', $rows->pluck('reference'))
            ->pluck('presta_products.id');
        $prestaProducts = PrestaProduct::with('product')->find($prestaProducts);
        $uploadProducts = collect();
        foreach ($prestaProducts as $prestaProduct) {
            if (!array_key_exists(mb_strtolower($prestaProduct->product->reference), $rules)) {
                continue;
            }
            $prestaProduct->price = $rules[mb_strtolower($prestaProduct->product->reference)];
            if($prestaProduct->price <= $prestaProduct->price_discount){
                $prestaProduct->price_discount = null;
            }
            $prestaProduct->save();
            $uploadProducts->push($prestaProduct);
        }
        UploadProductService::uploadPrices($uploadProducts);
        return back();
    }

    /**
     * @param Request $request
     */
    public function updateSalePricesXlsx(Request $request)
    {
        $file = $request->file('xlsx_file');
        if (empty($file) || $file->getClientOriginalExtension() != 'xlsx') {
            throw ValidationException::withMessages([__('Fill : ') . __('xlsx file')]);
        }
        $path = $file->getRealPath();
        /**
         * @var LaravelExcelReader $rows
         */
        $rows = Excel::load(
            $path,
            function ($reader) {
            }
        );
        $rows = $rows->get()
            ->filter(function ($item) {
                return $item->reference != null;
            });
        $rules = [];
        foreach ($rows as $row) {
            $rules[mb_strtolower($row->reference)] = (int)$row['sale_price'];
        }
        $prestaProducts = PrestaProduct::where('channel_id', $request->channel_id)
            ->join('products', 'presta_products.product_id', '=', 'products.id')
            ->whereIn('products.reference', $rows->pluck('reference'))
            ->pluck('presta_products.id');
        $prestaProducts = PrestaProduct::with('product')->find($prestaProducts);
        $uploadProducts = collect();
        foreach ($prestaProducts as $prestaProduct) {
            if (!array_key_exists(mb_strtolower($prestaProduct->product->reference), $rules)) {
                continue;
            }
            $prestaProduct->price_discount = $rules[mb_strtolower($prestaProduct->product->reference)];
            if($prestaProduct->price <= $prestaProduct->price_discount){
                $prestaProduct->price = $prestaProduct->price_discount;
                $prestaProduct->price_discount = null;
            }
            $prestaProduct->save();
            $uploadProducts->push($prestaProduct);
        }
        UploadProductService::uploadPrices($uploadProducts);
        return back();
    }

    /**
     * @param Request $request
     * @throws ValidationException
     */
    public function reductionPricesXlsx(Request $request)
    {
        $file = $request->file('xlsx_file');
        if (empty($file) || $file->getClientOriginalExtension() != 'xlsx') {
            throw ValidationException::withMessages([__('Fill : ') . __('xlsx file')]);
        }
        $path = $file->getRealPath();
        /**
         * @var LaravelExcelReader $rows
         */
        $rows = Excel::load(
            $path,
            function ($reader) {
            }
        );
        $rows = $rows->get()
            ->filter(function ($item) {
                return $item->reference != null;
            });
        $rules = [];
        foreach ($rows as $row) {
            $rules[mb_strtolower($row->reference)] = (int)$row['reduction'];
        }
        $prestaProducts = PrestaProduct::where('channel_id', $request->channel_id)
            ->join('products', 'presta_products.product_id', '=', 'products.id')
            ->whereIn('products.reference', $rows->pluck('reference'))
            ->pluck('presta_products.id');
        $prestaProducts = PrestaProduct::with('product')->find($prestaProducts);
        $uploadProducts = collect();
        foreach ($prestaProducts as $prestaProduct) {
            if (!array_key_exists(mb_strtolower($prestaProduct->product->reference), $rules)) {
                continue;
            }
            $prestaProduct->price_discount = $prestaProduct->price - $rules[mb_strtolower($prestaProduct->product->reference)];
            $prestaProduct->save();
            $uploadProducts->push($prestaProduct);
        }
        UploadProductService::uploadPrices($uploadProducts);
        return back();
    }

    public function uploadAvailability(Request $request)
    {
        $prestaProducts = PrestaProduct::where('channel_id', $request->channel_id)->get();
        UploadProductService::uploadAvailability($prestaProducts);
        return back();
    }
}
