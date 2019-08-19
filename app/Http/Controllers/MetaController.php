<?php

namespace App\Http\Controllers;

use App\InfluxDBConnector;
use Illuminate\Http\Request;
use App\Meta;

class MetaController extends Controller
{
    public function getAll() {
        $meta = Meta::all();
        return response()->json([
            'success' => true,
            'data' => $meta
        ]);
    }

    public function test() {
        return response()->json([
            "what" => InfluxDBConnector::getClient()
        ]);
    }
}
