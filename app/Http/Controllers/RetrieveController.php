<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meta;
use App\ObjectX;
use Illuminate\Support\Facades\Auth;
use App\InfluxDBConnector;
use App\MongoConnector;
use MongoDB\BSON\ObjectId;

class RetrieveController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function detail(Request $request, $type_id, $id)
    {
        $meta = Meta::find($type_id);
        if (!$meta) {
            return response()->json([
                'success' => false,
                'data' => "404"
            ]);
        }
        switch($meta->type) {
            case "object":
                $object = ObjectX::find($id);
                var_dump($object->type_id);
                if (!$object || $object->user_id != Auth::id() || $object->meta_id != $type_id) {
                    return response()->json([
                        'success' => false,
                        'data' => "404"
                    ]);
                }
                return response()->download(storage_path('app/' . $object->name));
                break;
            case "timeseries":
                $database = InfluxDBConnector::getDatabase();
                $result = $database->getQueryBuilder()
                    ->select('value, time')
                    ->from($meta->name)
                    ->where(["user_id = '" . strval(Auth::id()) . "' AND tagtag = '" . $id . "' AND meta_id = '" . strval($meta->id) . "'"])
                    ->getResultSet()
                    ->getPoints();
                return response()->json(['success' => true, 'data' => $result]);
            case "record":
                $database = MongoConnector::getDatabase();
                $collection = $database->selectCollection($meta->name);
                $cursor = $collection->find(
                    [
                        "_user_id" => Auth::id(),
                        "_meta_id" => intval($type_id),
                        "_id" => new ObjectId($id)
                    ],
                    [
                        'projection' => [
                            '_user_id' => 0,
                            '_meta_id' => 0,
                        ],
                    ]
                );
                $result = NULL;
                foreach ($cursor->toArray() as $o) {
                    $o["id"] = strval($o["_id"]);
                    unset($o["_id"]);
                    $result = $o;
                }
                return response()->json(['success' => $result != NULL, 'data' => $result]);
            default:
                return response()->json(['success' => false, 'data' => "not supported"]);
        }
    }

    public function getList(Request $request, $id)
    {
        $meta = Meta::find($id);
        if (!$meta) {
            return response()->json([
                'success' => false,
                'data' => "404"
            ]);
        }
        $tmin = intval($request->input('tmin', 0));
        $tmax = intval($request->input('tmax', time()));
        $limit = intval($request->input('limit', 20));
        $offset = intval($request->input('offset', 0));

        switch($meta->type) {
            case "object":
                $objects = ObjectX::where('user_id', Auth::id())
                    ->where('meta_id', $id)
                    ->where('time', '<', date("Y-m-d h:i:sa", $tmax))
                    ->where('time', '>=', date("Y-m-d h:i:sa", $tmin))
                    ->orderBy('time', 'desc')
                    ->skip($offset)->take($limit)->get();
                return response()->json(['success' => true, 'data' => $objects]);
                break;
            case "timeseries":
                $database = InfluxDBConnector::getDatabase();
                $result = $database->getQueryBuilder()
                    ->select('first("tag") as "tag"')
                    ->from($meta->name)
                    ->where(["user_id = '" . strval(Auth::id()) . "' AND meta_id = '" . strval($meta->id) . "' AND time < " . strval($tmax) . "000000000 AND time >= ". strval($tmin) . "000000000"])
                    ->orderBy("time", "asc")
                    ->groupBy("tagtag")
                    ->limit($limit)
                    ->offset($offset)
                    ->getResultSet()
                    ->getPoints();
                return response()->json(['success' => true, 'data' => $result]);
            case "record":
                $database = MongoConnector::getDatabase();
                $collection = $database->selectCollection($meta->name);
                $cursor = $collection->find(
                    [
                        "_user_id" => Auth::id(),
                        "_meta_id" => intval($id),
                        "_time" => ['$lt' => $tmax, '$gte' => $tmin]
                    ],
                    [
                        'projection' => [
                            '_user_id' => 0,
                            '_meta_id' => 0,
                        ],
                        'sort' => ['_time' => -1],
                        'limit'=> $limit,
                        'skip' => $offset
                    ]
                );
                $results = [];
                foreach ($cursor->toArray() as $o) {
                    $o["id"] = strval($o["_id"]);
                    unset($o["_id"]);
                    array_push($results, $o);
                }
                return response()->json(['success' => true, 'data' => $results]);
            default:
                return response()->json(['success' => false, 'data' => "not supported"]);
        }
    }
}
