<?php

namespace App\Http\Controllers;

use App\Http\Requests\MapGeoCodeRequest;
use App\MapGeoCode;

class MapGeoCodeController extends Controller
{
    public function ajaxAddGeo(MapGeoCode $mapGeoCode, MapGeoCodeRequest $request)
    {
        $mapGeoCode->update(
            [
                'geoX' => $request->geoX,
                'geoY' => $request->geoY,
            ]
        );

        return response()->json(['OK']);
    }
}
