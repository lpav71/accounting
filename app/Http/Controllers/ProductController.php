<?php

namespace App\Http\Controllers;

use App\Filters\ProductFilter;
use App\Manufacturer;
use App\Product;
use App\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Category;
use Excel;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\ProductAttribute;
use App\ProductCharacteristic;
use App\PrestaProduct;
use App\Channel;
use Config;

/**
 * Class ProductController
 * @package App\Http\Controllers
 */
class ProductController extends Controller
{
    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:product-list');
        $this->middleware('permission:product-create', ['only' => ['create', 'store','getCSV','postCsv','copyProduct','postCsvAvailability']]);
        $this->middleware('permission:product-edit', ['only' => ['edit', 'update','massProcess']]);
        $this->middleware('permission:product-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @var \App\Product $product
     * @var \App\Filters\ProductFilter $filters
     * @var \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Product $product, ProductFilter $filters, Request $request)
    {
        $products = $product->filter($filters)->sortable()->paginate(25)->appends($request->query());
        $stores = Store::all();

        return view('products.index', compact('products', 'stores'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $manufacturers = Manufacturer::all()->pluck('name', 'id');
        $products = Product::all()->pluck('name', 'id');
        $categories = Category::all()->pluck('name', 'id');
        return view('products.create', compact('manufacturers', 'products','categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:products,name',
            'reference' => 'required|unique:products,reference',
            'ean' => 'max:13',
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'category_id' => 'required|exists:categories,id',
            'products' => 'required_with:is_composite',
            'title' => 'required',
        ]);

        $product = Product::create($request->input());
        $product->assignProduct($request->input('products'));
        return redirect()->route('products.edit',['id' => $product->id])->with('success', 'Product created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $attributes=$product->allAttributes();
        $characteristics=$product->allCharacteristics();
        $manufacturers = Manufacturer::all()->pluck('name', 'id');
        $categories = Category::all()->pluck('name', 'id');
        $products = Product::all()->where('id', '<>', $product->id)->pluck('name', 'id');

        return view('products.edit', compact('product', 'manufacturers', 'products','categories','attributes','characteristics'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('products', 'name')->ignore($product->id),
            ],
            'reference' => [
                'required',
                Rule::unique('products', 'reference')->ignore($product->id),
            ],
            'ean' => 'max:13',
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'category_id' => 'required|exists:categories,id',
            'products' => 'required_with:is_composite',
            'need_guarantee' => 'integer|min:0|max:1|nullable',
            'title' => 'required',
        ]);

        $data = $request->input();
        if (! $product->isUsedInOperations() && ! $product->isUsedInOrders()) {
            $data['is_composite'] = isset($data['is_composite']);
        }

        if (!$request->need_guarantee) {
            $data['need_guarantee'] = 0;
        }
        if (isset($request['attributes'])) {
            $attributes = [];
            foreach ($request['attributes'] as $id => $attr_value) {
                if (!empty($attr_value)) $attributes[$id] = ['attr_value' => $attr_value];
            }
            $product->attributes()->sync($attributes);
        }

        if (isset($request['characteristic'])) {
            $characteristics=[];
            foreach ($request['characteristic'] as $id => $attr_value) {
                if (!empty($attr_value)) $characteristics[$id] = ['attr_value' => $attr_value];
            }
            $product->characteristics()->sync($characteristics);
        }

        if($request->product_combination !=0)
        {
            if($request->product_combination == -1)
            {
                $product->deleteFromCombination();
            }else{
                $product->addToCombination($request->product_combination);
            }
        }

        $product->update($data);
        if (! $product->isUsedInOperations() && ! $product->isUsedInOrders()) {
            $product->products()->detach();
            $product->assignProduct($request->input('products'));
        }
        $product->uploadToChannels();
        return redirect()->back()->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product $product
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Product $product)
    {
        if ($product->parent()->count()) {
            return redirect()->route('products.index')->with('warning', 'This Product can not be deleted, because it\'s part of composite products: '.implode(', ', $product->parent()->pluck('name')->toArray()));
        }
        if ($product->orderDetails()->count()) {
            return redirect()->route('products.index')->with('warning', 'This Product can not be deleted, because it\'s part of orders: '.implode(', ', $product->orderDetails()->get()->pluck('order_id')->toArray()));
        }
        if ($product->isUsedInOperations()) {
            return redirect()->route('products.index')->with('warning', 'This Product can not be deleted, because it\'s part of operations.');
        }

        $product->delete();

        return response('success');
    }


    /**
     * отобразить форму загрузки CSV с товарами
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getCSV(){
        return view('products.form-csv');
    }

    /**
     * Импорт товаров с помощью CSV файла
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCsv(Request $request){
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        if ($file->getClientOriginalExtension() != 'csv') {
            throw ValidationException::withMessages([__('Need a CSV file.')]);
        }
        Config::set('excel.csv.delimiter', ';');
        $data = Excel::load(
            $path,
            function ($reader) {
            }
        )->get();
        $dataProducts = $data->where('reference', '!=', null)->where('name', '!=', null);
        $channelProducts = $data->where('reference', '!=', null)->where('channel', '!=', null);
        if ($data->count() < 1) {
            throw ValidationException::withMessages([__('CSV file is Empty.')]);
        }
        $productCache = Product::all();
        $manufacturerCache = Manufacturer::all();
        $attributeCache = ProductAttribute::all();
        $characteristicCache = ProductCharacteristic::all();
        $categoryCache = Category::all();
        $channelProductsCache = PrestaProduct::all();
        $channelCache = Channel::all();
        $errorMessages = collect();
        

        foreach ($dataProducts as $importedProduct){
            $errors = false;
            $product = $productCache->where('reference',$importedProduct->reference)->first();
            if ($product && !$request->update_products){ 
                continue;
            }elseif(!$product){
                if(!$request->add_products){
                    continue;
                }else{
                    $product = new Product();
                    $product->reference = $importedProduct->reference;
                }
            }
            
                $product->name = $importedProduct->name;
                $product->title = $importedProduct->title;
                $product->need_guarantee = (bool) $importedProduct->need_guarantee;
                $manufacturer = $manufacturerCache->where('name',$importedProduct->manufacturer)->first();
                if($manufacturer){
                    $product->manufacturer_id = $manufacturer->id;
                }else{
                    $errorMessages->push(
                        __(
                            'Manufacturer not found for :product',
                            [
                                'product' => $importedProduct['reference'],
                            ]
                        )
                    );
                    continue;
                }
                
                $category = $categoryCache->where('name',$importedProduct->category)->first();
                if($category){
                    $product->category_id = $category->id;
                }else{
                    $errorMessages->push(
                        __(
                            'Category not found for :product',
                            [
                                'product' => $importedProduct['reference'],
                            ]
                        )
                    );
                    continue;
                }
                $product->save();
                if(!empty($importedProduct['attributes'])){
                    $attributesStrings = explode(';',$importedProduct['attributes']);
                    $attributes = [];
                    foreach ($attributesStrings as $attribute){
                        $exploded= explode('=',$attribute);
                        try{
                            if(count($exploded) != 2) throw new \Exception('wrong attribute string');
                            $attributes[$exploded[0]]=$exploded[1];
                            
                        }catch(\Exception $e){
                            $errors = true;
                            $errorMessages->push(
                                __(
                                    'Wrong attribute string :attribute for :product',
                                    [
                                        'attribute' => $attribute,
                                        'product' => $importedProduct['reference'],
                                    ]
                                )
                            );
                        }
                        
                    }
                    $attributeForSync = [];
                    foreach ($attributes as $name => $attr_value) {
                        $attribute = $attributeCache->where('name', $name)->first();
                        if (!$attribute) {
                            $errors = true;
                            $errorMessages->push(
                                __(
                                    'Attribute :attribute not found for :product',
                                    [
                                        'attribute' => $name,
                                        'product' => $importedProduct['reference'],
                                    ]
                                )
                            );
                        } else {
                            if (!empty($attr_value)) $attributeForSync[$attribute->id] = ['attr_value' => $attr_value];
                        }
                    }
                    if (!$errors) $product->attributes()->sync($attributeForSync);
                }
                if(!empty($importedProduct['characteristics'])){
                    $characteristicsStrings = explode(';',$importedProduct['characteristics']);
                    $characteristics = [];
                    foreach ($characteristicsStrings as $characteristic){
                        
                        $exploded= explode('=',$characteristic);
                        try{
                            if(count($exploded) != 2) throw new \Exception('wrong characteristic string');
                            $characteristics[$exploded[0]]=$exploded[1];
                            
                        }catch(\Exception $e){
                            $errors = true;
                            $errorMessages->push(
                                __(
                                    'Wrong characteristic string :characteristic for :product',
                                    [
                                        'characteristic' => $characteristic,
                                        'product' => $importedProduct['reference'],
                                    ]
                                )
                            );
                        }
                        
                    }
                    $characteristicForSync = [];
                    foreach ($characteristics as $name => $attr_value) {
                        $characteristic = $characteristicCache->where('name', $name)->first();
                        if (!$characteristic) {
                            $errors = true;
                            $errorMessages->push(
                                __(
                                    'Characteristic :characteristic not found for :product',
                                    [
                                        'characteristic' => $name,
                                        'product' => $importedProduct['reference'],
                                    ]
                                )
                            );
                        } else {
                            if (!empty($attr_value)) $characteristicForSync[$characteristic->id] = ['attr_value' => $attr_value];
                        }
                    }
                    if (!$errors) $product->characteristics()->sync($characteristicForSync);
                }
            foreach ($channelProducts->where('reference', $product->reference) as $importedChannelProduct) {
                $channel = $channelCache->where('name', $importedChannelProduct['channel'])->first();
                if (!$channel) {
                    $errorMessages->push(
                        __(
                            'Channel :channel not found for :product',
                            [
                                'channel' => $importedChannelProduct['channel'],
                                'product' => $product->reference,
                            ]
                        )
                    );
                    continue;
                }
                $channelProduct = $channelProductsCache->where('channel_id',$channel->id)->where('product_id',$product->id)->first();
                if(!$channelProduct){
                    $channelProduct = new PrestaProduct();
                    $channelProduct->channel_id = $channel->id;
                    $channelProduct->product_id = $product->id;
                }
                $channelProduct->is_active = (int) $importedChannelProduct['active'];
                if ($importedChannelProduct['price'] == null) {
                    $errorMessages->push(
                        __(
                            'Price isn`t set for :channel for :product',
                            [
                                'channel' => $importedChannelProduct['channel'],
                                'product' => $product->reference,
                            ]
                        )
                    );
                    continue;
                }
                $channelProduct->price = $importedChannelProduct['price'];
                $channelProduct->price_discount = $importedChannelProduct['price_discount'];
                $channelProduct->description = $importedChannelProduct['description'];
                $channelProduct->save();
            }
        }
        $productCache = Product::all();

        $existingProducts = $dataProducts->filter(function($product) use ($productCache) {
            if($productCache->where('reference',$product->reference)->first()){
                return true;
            }else{
                return false;
            }
        });

        $existingProducts->where('combination', '!=', null)->map(function ($product) {
            if ('double' == gettype($product['combination'])){
                $product['combination'] = (int)$product->combination;
            }
            return $product;
        })->groupBy('combination')->filter(function ($combination) use ($productCache) {
            if ($combination->count() > 1) {
                return true;
            } else {
                $combination->map(function ($product) use ($productCache) {
                    $productCache->where('reference', $product->reference)->first()->deleteFromCombination();
                });
                return false;
            }
        })->map(function ($combination) use ($productCache){
            $combination = $combination->map(function ($product) use ($productCache) {
                return $productCache->where('reference', $product->reference)->first();
            });
            foreach ($combination as $key => $product) {
                if ($key == 0) {
                    if (isset($product->combination)) {
                        $product->combination->onlyProducts->map(function ($product) use ($combination) {
                            if ($combination->where('id',$product->id)->count()==0) {
                               $product->deleteFromCombination();
                            }
                        });
                    }
                }else{
                    $product->addToCombination($combination[0]->id);
                }
            }
        });
        $dataProducts->where('combination',null)->map(function($product) use ($productCache){
            $productCache->where('reference',$product->reference)->first()->deleteFromCombination();
        });

        return back()->with('success', __('Import successful'))->withErrors($errorMessages);
    }


    /**
     * Скачать CSV со всеми товарами товарами
     *
     */
    public function downloadCSV(){
        $data = Product::all()->map(
            function (Product $product) {

                $attributes = [];
                $attributes = $product->attributes->flatMap(function ($attribute) {
                    return  [$attribute->name => $attribute->pivot->attr_value];
                })->toArray();
                $attributesString = '';
                foreach ($attributes as $key => $value) {
                    $attributesString .= $key . '=' . $value . ';';
                }

                $characteristics = [];
                $characteristics = $product->characteristics->flatMap(function ($characteristic) {
                    return  [$characteristic->name => $characteristic->pivot->attr_value];
                })->toArray();
                $characteristicsString = '';
                foreach ($characteristics as $key => $value) {
                    $characteristicsString .= $key . '=' . $value . ';';
                }

                $prestaProducts = $product->prestaProducts->map(function ($presta_product) {
                    return
                        [
                            'channel' => $presta_product->channel->name,
                            'active' => $presta_product->is_active,
                            'price' => $presta_product->price,
                            'price_discount' => $presta_product->price_discount,
                            'description' => $presta_product->description
                        ];
                });
                $collection = new Collection();

                $key = 0;
                do {
                    $tempcollection  = null;
                    if ($key == 0) {
                        $tempcollection = collect([
                            'name' => $product->name,
                            'title' => $product->title,
                            'wholesale_price' => $product->getPrice() ?: '0',
                            'need_guarantee' => $product->need_guarantee,
                            'manufacturer' => isset($product->manufacturer) ? $product->manufacturer->name : '',
                            'category' => isset($product->category) ? $product->category->name : '',
                            'combination' => isset($product->combination) ? $product->combination->id : '',
                            'attributes' => chop($attributesString, ';'),
                            'characteristics' => chop($characteristicsString, ';'),
                            'reference' => $product->reference,
                        ]);
                    }else {
                        $tempcollection = collect([
                            'name' =>  '',
                            'title' => '',
                            'wholesale_price' => '' ?: '',
                            'need_guarantee' => '',
                            'manufacturer' => '',
                            'category'=>'',
                            'combination' => '',
                            'attributes' => '',
                            'characteristics' => '',
                            'reference' => $product->reference,
                        ]);
                    }
                    if(isset($prestaProducts[$key])){
                        $tempcollection->put('channel',$prestaProducts[$key]['channel']);
                        $tempcollection->put('active',$prestaProducts[$key]['active']);
                        $tempcollection->put('price', (float)$prestaProducts[$key]['price']);
                        $tempcollection->put('price_discount',$prestaProducts[$key]['price_discount']);
                        $tempcollection->put('description',$prestaProducts[$key]['description']);
                    }
                    $collection->push($tempcollection);
                    $key++;
                } while ($key < $prestaProducts->count());

                return $collection;
            }
        );
        $data = $data->flatMap(function($value){
            return $value;
        });
        $data = $data->toArray();
          Config::set('excel.csv.delimiter', ';');
          Excel::create(
            'products_'.Carbon::now()->format('d_m_Y_H_i'),
            function (LaravelExcelWriter $excel) use ($data) {
                $excel->sheet(
                    'Sheet',
                    function (LaravelExcelWorksheet $sheet) use ($data) {
                        $sheet->fromArray($data,null,'A1',true);
                    }
                );
            }
        )->download('csv');
    }


    /**
     * Массовая обработка товаров
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function massProcess(Request $request){
        $prestaProducts = PrestaProduct::whereIn('product_id',$request->products)->whereIn('channel_id',$request->channels)->get();
        if('disable-products' == $request->action){
            $prestaProducts->map(function($item){
                $item->disableItem();
            });
        }elseif('enable-products' == $request->action){
            $prestaProducts->map(function($item){
                $item->enableItem();
            });
        }       
        return back()->with('success', __('Products updated successful'));
    }


    /**
     *
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function copyProduct(Request $request, Product $product){
        $new = $product->makeCopy();
        return redirect()->route('products.edit',['id' => $new->id])->with('success', 'Product copied successfully');
    }


    /**
     *
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public  function postCsvAvailability(Request $request){
        $request->validate([
            'channels' => 'required',
        ]);
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        if ($file->getClientOriginalExtension() != 'csv') {
            throw ValidationException::withMessages([__('Need a CSV file.')]);
        }
        Config::set('excel.csv.delimiter', ';');
        $data = Excel::load(
            $path,
            function ($reader) {
            }
        )->get();

        //pluck all empty references
        $references = $data->where('quantity',0)->pluck('reference');
        $products = Product::whereIn('reference',$references);
        if(!empty($request->manufacturers)){
            $products->whereIn('manufacturer_id',$request->manufacturers);
        }
        $products = $products->pluck('id');
        $prestaProducts = PrestaProduct::whereIn('product_id',$products)->whereIn('channel_id',$request->channels)->get();
        $prestaProducts->map(function($item){
            $item->disableItem();
        });

        //pluck all not empty references
        $references = $data->where('quantity','>',0)->pluck('reference');
        $products = Product::whereIn('reference',$references);
        if(!empty($request->manufacturers)){
            $products->whereIn('manufacturer_id',$request->manufacturers);
        }
        $products = $products->pluck('id');
        $prestaProducts = PrestaProduct::whereIn('product_id',$products)->whereIn('channel_id',$request->channels)->get();
        $prestaProducts->map(function($item){
            $item->enableItem();
        });
        return back()->with('success', __('Products updated successful'));
    }

    /**
     * ban product
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function postCsvBanned(Request $request)
    {
        $request->validate([
            'channels' => 'required',
        ]);
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        if ($file->getClientOriginalExtension() != 'csv') {
            throw ValidationException::withMessages([__('Need a CSV file.')]);
        }
        Config::set('excel.csv.delimiter', ';');
        $data = Excel::load(
            $path,
            function ($reader) {
            }
        )->get();

        //pluck all empty references
        $references = $data->where('quantity',0)->pluck('reference');
        $products = Product::whereIn('reference',$references);
        if(!empty($request->manufacturers)){
            $products->whereIn('manufacturer_id',$request->manufacturers);
        }
        $products = $products->pluck('id');
        $prestaProducts = PrestaProduct::whereIn('product_id',$products)->whereIn('channel_id',$request->channels)->get();
        $prestaProducts->map(function($item){
            $item->blockItem();
        });

        //pluck all not empty references
        $references = $data->where('quantity','>',0)->pluck('reference');
        $products = Product::whereIn('reference',$references);
        if(!empty($request->manufacturers)){
            $products->whereIn('manufacturer_id',$request->manufacturers);
        }
        $products = $products->pluck('id');
        $prestaProducts = PrestaProduct::whereIn('product_id',$products)->whereIn('channel_id',$request->channels)->get();
        $prestaProducts->map(function($item){
            $item->unblockItem();
        });
        return back()->with('success', __('Products updated successful'));
    }
}
