<?php

namespace App\Http\Controllers;

use App\InfluxDBConnector;
use App\MongoConnector;
use Illuminate\Http\Request;
use App\Meta;
use App\ObjectX;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use InfluxDB\Point;
use InfluxDB\Database;

class UploadController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, $id)
    {
        $meta = Meta::find($id);
        if (!$meta) {
            return response()->json([
                'success' => false,
                'data' => "404"
            ]);
        }
        $time = $request->input("time");
        if ($time == '') {
            $time = Carbon::now()->toDateTimeString();
        } else {
            $time = Carbon::createFromTimestampUTC(intval($time))->toDateTimeString();
        }
        switch($meta->type) {
            case "object":
                if ($request->hasFile('file') && $request->file('file')->isValid()) {
                    $path = $request->file->store('object');
                    $object = ObjectX::create([
                        'name' => $path,
                        'user_id' => Auth::id(),
                        'meta_id' => $meta->id,
                        'time' => $time
                    ]);
                    return response()->json(['success' => true, 'data' => $object]);
                } else {
                    return response()->json(['success' => false, 'data' => "no file"]);
                }
                break;
            case "timeseries":
                $input = Input::json()->all();
                $points = array_map(function($e) use($meta) {
                    return new Point(
                        $meta->name, // name of the measurement
                        $e["value"], // the measurement value
                        ['user_id' => Auth::id(), "meta_id" => $meta->id, "tagtag" => $e["tag"]], // optional tags
                        ["tag" => $e["tag"]], // optional additional fields
                        sprintf('%d%06d', $e["sec"], $e["usec"])
                    );
                }, array_filter($input, function($e) {
                    return isset($e["value"]) && isset($e["sec"]) && isset($e["usec"]) && isset($e["tag"]);
                }));
                if (count($points) == 0)
                    return response()->json(['success' => false, 'data' => 'no valid data']);
                $database = InfluxDBConnector::getDatabase();
                $result = $database->writePoints($points, Database::PRECISION_MICROSECONDS);
                return response()->json(['success' => $result, 'data' => '']);
                break;
            case "record":
                $input = Input::json()->all();
                $input["_user_id"] = Auth::id();
                $input["_meta_id"] = $meta->id;
                $input["_time"] = time();
                $database = MongoConnector::getDatabase();
                $collection = $database->selectCollection($meta->name);
                $result = $collection->insertOne($input);
                return response()->json(['success' => true, 'data' => '']);
                break;
            default:
                return response()->json(['success' => false, 'data' => "not supported"]);
        }
    }
}
