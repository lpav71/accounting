<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductPicture;
use App\Http\Resources\ProductPictureCollection;
use App\Http\Resources\ProductPicture as ProductPictureResource;
use App\Product;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class ProductPictureController
 * @package App\Http\Controllers
 */
class ProductPictureController extends Controller
{


    /**
     * ProductPictureController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:product-list', ['only' => ['index','show']]);
        $this->middleware('permission:product-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:product-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:product-delete', ['only' => ['destroy']]);
    }


    /**
     * все изображения товара
     *
     * @param Request $request
     * @return ProductPictureCollection
     */
    public function index(Request $request)
    {
        $pictures = ProductPicture::where('product_id',$request->product_id)->get();
        return new ProductPictureCollection($pictures);
    }


    /**
     *
     */
    public function create()
    {
        //
    }


    /**
     * Сохраняет изображение с публичным доступом к нему
     *
     * @param Request $request
     * @return ProductPictureCollection|\Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        if($request->delete) {
        return $this->destroy($request, ProductPicture::find($request->id));
        }

        $this->validate($request, [
            'productPictures' => 'required',
        ]);
        $product = Product::whereId($request->product_id)->first();
        foreach($request->file('productPictures') as $filePicture){
            $extension=pathinfo($filePicture->getClientOriginalName(), PATHINFO_EXTENSION);
            if(strcasecmp($extension,'jpg') && strcasecmp($extension,'png') && strcasecmp($extension,'jpeg')){
                return response()->json(['error'=>'Only jpg or png acceptable'],415);
            }
    
            $path=str_split($request->product_id);
            $path=implode('/',$path);
            $productPicture = new ProductPicture();
            $productPicture->product_id=$request->product_id;
            $path = Storage::disk('local')->putFile('productPictures/'.$path, $filePicture);
            $productPicture->path=$path;
            $productPicture->ordering = $product->lastPictureOrdering() + 1;
            $productPicture->save();
            $productPicture->url = route('product-pictures.show',['id' => $productPicture->id], false);
            $productPicture->public_url = route('product-pictures.api.show',['id' => $productPicture->id], false);
            $productPicture->save();
        }
        $pictures = ProductPicture::where('product_id',$request->product_id)->get();
        return new ProductPictureCollection($pictures);
    }


    /**
     * @param $id
     */
    public function show(ProductPicture $productPicture)
    {
        $response = new BinaryFileResponse(Storage::disk('local')->path($productPicture->path));
        BinaryFileResponse::trustXSendfileTypeHeader();

        return $response;
    }


    /**
     * @param $id
     */
    public function edit($id)
    {
        //
    }


    /**
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        //
    }


    /**
     * даляет изображение
     *
     * @param Request $request
     * @param ProductPicture $productPicture
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request, ProductPicture $productPicture)
    {
        if($productPicture->delete()){
            return response()->json(['message'=>'deleted'], 200);
        }else{
            return response()->json(['message'=>'not deleted'], 500);
        }
    }

    /**
     * get product picture through api
     *
     * @param Request $request
     * @param ProductPicture $productPicture
     * @return BinaryFileResponse
     */
    public function getPicture(Request $request, ProductPicture $productPicture){
        if(config('product-picture.api_token') == $request->token){
            $response = new BinaryFileResponse(Storage::disk('local')->path($productPicture->path));
            BinaryFileResponse::trustXSendfileTypeHeader();
    
            return $response;
        }
        die();
    }
}
