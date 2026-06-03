<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Zip;

class CityController extends Controller
{
    public function getCitiesByZip($zip)
    {
        $cities = Zip::where('zip_code', $zip)
            ->join('cities', 'zips.city_id', '=', 'cities.city_id')
            ->select('cities.city_id', 'cities.city_name', 'cities.state')
            ->get();

        return response()->json(['cities' => $cities]);
    }
}