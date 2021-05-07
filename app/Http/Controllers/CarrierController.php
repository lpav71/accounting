<?php

namespace App\Http\Controllers;

use App\Carrier;
use Appwilio\CdekSDK\CdekClient;
use Appwilio\CdekSDK\Requests\CalculationRequest;
use Doctrine\Common\Annotations\AnnotationRegistry;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CarrierController extends Controller
{
    /**
     * CarrierController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:carrier-list', ['except' => ['calculateTariff']]);
        $this->middleware('permission:carrier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:carrier-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:carrier-delete', ['only' => ['destroy']]);
        $this->middleware('permission:order-edit', ['only' => ['calculateTariff']]);
        AnnotationRegistry::registerLoader('class_exists');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('carriers.index', ['carriers' => Carrier::orderBy('id', 'DESC')->paginate(30)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('carriers.create');
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
            'name' => 'required|unique:carriers,name',
            'is_internal' => 'integer|nullable',
        ]);

        Carrier::create($request->input());

        return redirect()->route('carriers.index')->with('success', __('Carrier created successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Carrier $carrier
     * @return \Illuminate\Http\Response
     */
    public function show(Carrier $carrier)
    {
        return view('carriers.show', compact('carrier'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Carrier $carrier
     * @return \Illuminate\Http\Response
     */
    public function edit(Carrier $carrier)
    {
        return view('carriers.edit', compact('carrier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Carrier $carrier
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Carrier $carrier)
    {
        $this->validate($request, [
            'name' => [
                'required',
                Rule::unique('carriers', 'name')->ignore($carrier->id),
            ],
            'is_internal' => 'integer|nullable',
        ]);

        $data = $request->input();

        $data['is_internal'] = isset($data['is_internal']) ? $data['is_internal'] : 0;
        $data['close_order_task'] = isset($data['close_order_task']) ? $data['close_order_task'] : 0;
        $data['self_shipping'] = isset($data['self_shipping']) ? $data['self_shipping'] : 0;

        $carrier->update($data);

        return redirect()->route('carriers.index')->with('success', __('Carrier updated successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Carrier $carrier
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Carrier $carrier)
    {
        $carrier->delete();

        return redirect()->route('carriers.index')->with('success', 'Carrier deleted successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateTariff(Request $request)
    {
        $carrier = Carrier::find($request->carrier_id);
        $carrierConfig = $carrier ? $carrier->getConfigVars() : false;
        $tariffValue = 0;
        $tariffTime = '';
        if ($carrierConfig && $carrierConfig->get('operator') == 'cdek') {
            $cdekClient = new CdekClient((string)$carrierConfig->get('operator_account'),
                (string)$carrierConfig->get('operator_secure'));
            $cdekRequest = (new CalculationRequest())::withAuthorization()
                ->setSenderCityPostCode('101000')
                ->setReceiverCityPostCode((string)$request->postal_code)
                ->setTariffId((int)$carrierConfig->get('tariff'))
                ->addGood([
                'weight' => (int)$carrierConfig->get('parcel_weight') / 1000,
                'length' => 10,
                'width' => 10,
                'height' => 10,
            ])->setModeId($carrierConfig->get('type') == 'pickup' ? 4 : 3);
            $response = $cdekClient->sendCalculationRequest($cdekRequest);
            if (!$response->hasErrors() && $response->getResult()) {
                $tariffValue = $response->getResult()->getPrice().' '.__('rub.');
                $tariffTime = (string)$response->getResult()->getDeliveryPeriodMin().($response->getResult()->getDeliveryPeriodMax() > $response->getResult()->getDeliveryPeriodMin() ? '-'.(string)$response->getResult()->getDeliveryPeriodMax() : '').' '.__('days');
            }
            //проверка на возможность наложного платежа
            $client = new Client();
            $res = $client->request('GET', 'http://integration.cdek.ru/v1/location/cities/json', [
                'query' => [
                    'postcode' => $request->postal_code
                ]]);
            $response = json_decode($res->getBody()->getContents(),true);
            foreach ($response as $city) {
                if ($carrierConfig->get('parcel_cashodelivery') == 'true' && $city['paymentLimit'] == 0 && mb_stripos($request->city, $city['cityName'])) {
                    $tariffValue = 0;
                    $tariffTime = '';
                }
            }
        }

        $result = ['value' => $tariffValue, 'time' => $tariffTime];

        return $request->ajax() ? response()->json($result, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],JSON_UNESCAPED_UNICODE) : $result;
    }
}
