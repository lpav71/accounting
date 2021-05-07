<?php

namespace App\Http\Controllers;

use App\Manufacturer;
use App\Product;
use App\Services\SalesService\SalesService;
use App\Store;
use App\StoreAutotransferSetting;
use App\TransferIteration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAutotransferSettingController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:store-autotransfer-setting-list');
        $this->middleware('permission:store-autotransfer-setting-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:store-autotransfer-setting-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:store-autotransfer-setting-delete', ['only' => ['delete']]);
        $this->middleware('permission:store-autotransfer-setting-process', ['only' => ['show']]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index()
    {
        return view('store-autotransfer-settings.index', ['storeAutotransferSettings' => StoreAutotransferSetting::orderBy('id', 'ASC')->paginate(15)]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function create()
    {
        $stores = Store::pluck('name', 'id');
        $manufacturers = Manufacturer::pluck('name', 'id');
        $settings = [];
        foreach ($manufacturers as $key => $manufacturer) {
            $settings['min'][$key]['name'] = $manufacturer;
            $settings['min'][$key]['value'] = null;
            $settings['limit'][$key]['name'] = $manufacturer;
            $settings['limit'][$key]['value'] = null;
        }
        return view('store-autotransfer-settings.create', compact('stores', 'settings'));
    }

    /**
     * @param Request $request
     * @return string
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:store_autotransfer_settings,name',
            'main_store_id' => 'required|integer',
            'reserve_store_id' => 'required|integer',
            'max_amount' => 'required|integer',
            'max_day' => 'required|integer',
            'min_day' => 'required|integer',
            'latest_sales_days' => 'required|integer',
            'settings' => 'array',
            'settings.min.*' => 'nullable|integer',
            'settings.limit.*' => 'nullable|integer',
        ]);
        if ($request->input('main_store_id') == $request->input('reserve_store_id')) {
            throw ValidationException::withMessages([__('Choose different stores')]);
        }
        StoreAutotransferSetting::create([
            'name' => $request->input('name'),
            'main_store_id' => $request->input('main_store_id'),
            'reserve_store_id' => $request->input('reserve_store_id'),
            'max_amount' => $request->input('max_amount'),
            'max_day' => $request->input('max_day'),
            'min_day' => $request->input('min_day'),
            'latest_sales_days' => $request->input('latest_sales_days'),
            'settings' => serialize($request->input('settings'))
        ]);

        return redirect()->route('store-autotransfer-settings.index')->with('success', 'Added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function show($id)
    {
        $setting = StoreAutotransferSetting::find($id);
        $mainStore = Store::find($setting->main_store_id);
        $reserveStore = Store::find($setting->reserve_store_id);
        $processingIds = TransferIteration::getInProcessProductIds($mainStore,$reserveStore);
        $products = Product::whereNotIn('id',$processingIds)->get();
        $settings = unserialize($setting->settings);
        $transferProducts = [];
        $amount = 0;
        $manufacturerAmount = [];
        foreach ($products as $product) {
            if (is_null($settings['min'][$product->manufacturer_id])) {
                continue;
            }
            $sales = SalesService::getCurrentAveragePacked($product, $setting->max_day, $setting->latest_sales_days);
            $current = $product->getCombinedQuantity($mainStore);
            $currentReserve = $product->getCombinedQuantity($reserveStore);
            $settingsMin = !empty($settings['min'][$product->manufacturer_id]) ? $settings['min'][$product->manufacturer_id] : 0;
            $max = max($settingsMin, $sales);
            $transfer = $current - $max;
            if ($transfer > 0) {
                if ($amount > $setting->max_amount) {
                    continue;
                }
                if (!isset($manufacturerAmount[$product->manufacturer_id])) {
                    $manufacturerAmount[$product->manufacturer_id] = 0;
                }
                if ($manufacturerAmount[$product->manufacturer_id] > $settings['limit'][$product->manufacturer_id]) {
                    continue;
                }
                $transferProduct['reference'] = $product->reference;
                $transferProduct['current'] = $current;
                $transferProduct['sales'] = $sales;
                $transferProduct['settingsMin'] = $settingsMin;
                $transferProduct['transfer'] = $transfer;
                $transferProduct['currentReserve'] = $currentReserve;
                $transferProducts[$product->id] = $transferProduct;
                $amount += $transfer;
                $manufacturerAmount[$product->manufacturer_id] += $transfer;
            }

            if ($amount > $setting->max_amount) {
                continue;
            }
        }
        return view('store-autotransfer-settings.show', compact('transferProducts', 'mainStore', 'setting', 'reserveStore','amount'));
    }

    public function showFromReserve($id)
    {
        $setting = StoreAutotransferSetting::find($id);
        $mainStore = Store::find($setting->main_store_id);
        $reserveStore = Store::find($setting->reserve_store_id);
        $processingIds = TransferIteration::getInProcessProductIds($mainStore,$reserveStore);
        $products = Product::whereNotIn('id',$processingIds)->get();
        $settings = unserialize($setting->settings);
        $transferBackProducts = [];
        $amountBack = 0;
        $manufacturerAmountBack = [];
        foreach ($products as $product) {
            if (is_null($settings['min'][$product->manufacturer_id])) {
                continue;
            }
            $sales = SalesService::getCurrentAveragePacked($product, $setting->min_day, $setting->latest_sales_days);
            $current = $product->getCombinedQuantity($mainStore);
            $currentReserve = $product->getCombinedQuantity($reserveStore);
            $settingsMin = !empty($settings['min'][$product->manufacturer_id]) ? $settings['min'][$product->manufacturer_id] : 0;
            $max = max($settingsMin, $sales);
            $transfer = $current - $max;
            if ($transfer < 0) {
                $transfer = min(abs($transfer), $currentReserve);
                if($transfer == 0){
                    continue;
                }
                if ($amountBack > $setting->max_amount) {
                    continue;
                }
                if (!isset($manufacturerAmountBack[$product->manufacturer_id])) {
                    $manufacturerAmountBack[$product->manufacturer_id] = 0;
                }
                if ($manufacturerAmountBack[$product->manufacturer_id] > $settings['limit'][$product->manufacturer_id]) {
                    continue;
                }
                $transferProduct['reference'] = $product->reference;
                $transferProduct['current'] = $current;
                $transferProduct['sales'] = $sales;
                $transferProduct['settingsMin'] = $settingsMin;
                $transferProduct['transfer'] = $transfer;
                $transferProduct['currentReserve'] = $currentReserve;
                $transferBackProducts[$product->id] = $transferProduct;
                $amountBack += $transfer;
                $manufacturerAmountBack[$product->manufacturer_id] += $transfer;
            }

            if ($amountBack > $setting->max_amount) {
                continue;
            }
        }
        return view('store-autotransfer-settings.showFromReserve', compact('amountBack', 'mainStore', 'setting', 'reserveStore','transferBackProducts'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function edit($id)
    {
        $stores = Store::pluck('name', 'id');
        $manufacturers = Manufacturer::pluck('name', 'id');
        $setting = StoreAutotransferSetting::find($id);
        $currentSettings = unserialize($setting->settings);
        $settings = [];
        foreach ($manufacturers as $key => $manufacturer) {
            $settings['min'][$key]['name'] = $manufacturer;
            $settings['min'][$key]['value'] = isset($currentSettings['min'][$key]) ? $currentSettings['min'][$key] : null;
            $settings['limit'][$key]['name'] = $manufacturer;
            $settings['limit'][$key]['value'] = isset($currentSettings['limit'][$key]) ? $currentSettings['limit'][$key] : null;
        }
        return view('store-autotransfer-settings.edit', compact('stores', 'settings', 'setting'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id)
    {
        $setting = StoreAutotransferSetting::find($id);

        $this->validate($request, [
            'name' => Rule::unique('store_autotransfer_settings', 'name')->ignore($id),
            'main_store_id' => 'required|integer',
            'reserve_store_id' => 'required|integer',
            'max_amount' => 'required|integer',
            'max_day' => 'required|integer',
            'min_day' => 'required|integer',
            'latest_sales_days' => 'required|integer',
            'settings' => 'array',
            'settings.min.*' => 'nullable|integer',
            'settings.limit.*' => 'nullable|integer',
        ]);
        if ($request->input('main_store_id') == $request->input('reserve_store_id')) {
            throw ValidationException::withMessages([__('Choose different stores')]);
        }
        $setting->update([
            'name' => $request->input('name'),
            'main_store_id' => $request->input('main_store_id'),
            'reserve_store_id' => $request->input('reserve_store_id'),
            'max_amount' => $request->input('max_amount'),
            'max_day' => $request->input('max_day'),
            'min_day' => $request->input('min_day'),
            'latest_sales_days' => $request->input('latest_sales_days'),
            'settings' => serialize($request->input('settings'))
        ]);
        $setting->save();

        return redirect()->route('store-autotransfer-settings.index')->with('success', 'Updated successfully');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        StoreAutotransferSetting::find($id)->delete();
        return back();
    }
}
