<?php

namespace App\Http\Controllers;

use App\Carrier;
use App\PickupPoint;
use Appwilio\CdekSDK\CdekClient;
use Appwilio\CdekSDK\Common\Pvz;
use Appwilio\CdekSDK\Requests\PvzListRequest;
use Illuminate\Http\Request;
use Doctrine\Common\Annotations\AnnotationRegistry;

class PickupPointController extends Controller
{
    /**
     * PickupPointController constructor.
     */
    public function __construct()
    {
        $this->middleware('permission:order-edit');
        AnnotationRegistry::registerLoader('class_exists');
    }

    /**
     * get PickupPoint list
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|array
     */
    public function list(Request $request)
    {
        $result = [];
        if ($request->postalCode && $request->carrierId && strlen($request->postalCode = (string) $request->postalCode) == 6) {
            $carrier = Carrier::find($request->carrierId);
            if ($carrier && $carrierConfig = $carrier->getConfigVars()) {
                if ($carrierConfig->has(['type', 'operator', 'operator_account', 'operator_secure'])) {
                    if ($carrierConfig->get('type') == 'pickup' && $carrierConfig->get('operator') == 'cdek') {
                        $cdekClient = new CdekClient((string) $carrierConfig->get('operator_account'), (string) $carrierConfig->get('operator_secure'));
                        $cdekRequest = (new PvzListRequest())->setPostCode($request->postalCode);
                        $cdekResponse = $cdekClient->sendPvzListRequest($cdekRequest);
                        $result = collect($cdekResponse->getItems())->filter(function (Pvz $pvz) use ($request) {
                            return mb_stripos($pvz->Name, $request->q) !== false || mb_stripos($pvz->Address, $request->q) !== false; //TODO
                        })->reduce(function ($acc, Pvz $pvz) {
                            $acc[] = [
                                'value' => $pvz->Code,
                                'text' => $pvz->Name,
                                'data' => [
                                    'subtext' => $pvz->Address,
                                ],
                            ];

                            return $acc;
                        }, []);
                    }
                }
            }
        }

        return $request->ajax() ? response()->json($result) : $result;
    }
}
