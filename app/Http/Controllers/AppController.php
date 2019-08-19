<?php

namespace App\Http\Controllers;

use App\EcgStatistic;
use App\InfluxDBConnector;
use App\Jobs\RunModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;
use App\MongoConnector;
use App\Meta;
use MongoDB\BSON\ObjectId;

class AppController extends Controller
{
    public function ecgPredict(Request $request) {
        $database = InfluxDBConnector::getDatabase();
        $tag = $request->input('tag', '');
        if ($tag == '') {
            return response()->json([
                'success' => false,
                'data' => "400"
            ]);
        }
        $result = $database->getQueryBuilder()
            ->select('value, time')
            ->from('ecg')
            ->where(["user_id = '" . strval(Auth::id()) . "' AND meta_id = '" . strval(3) . "' AND tagtag='" . $tag . "'"])
            ->orderBy("time", "asc")
            ->getResultSet()
            ->getPoints();

        if(count($result) == 0) {
            return response()->json([
                'success' => false,
                'data' => "404"
            ]);
        }

        $name = Uuid::uuid4()->toString();

        $input = storage_path("app/input/" . $name);

        $file = fopen($input, "w");
        if (!$file)
            return response()->json([
                'success' => false,
                'data' => "500"
            ]);
        fwrite($file, json_encode($result));
        fclose($file);

        $this->dispatchNow(new RunModel(Auth::id(), 3, $tag, "echo \"done\""));

        return response()->json([
            'success' => true,
            'data' => $tag
        ]);
    }

    public function getEcgPredict(Request $request) {
        $name = $request->input('tag', '');
        if ($name == '') {
            return response()->json([
                'success' => false,
                'data' => "400"
            ]);
        }
        $result = $this->getResult(Auth::id(), 3, $name);
        if ($result === False) {
            return response()->json([
                'success' => false,
                'data' => "400"
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    private function getResult($uid, $meta_id, $record_id) {
        $database = MongoConnector::getDatabase();
        $collection = $database->selectCollection("_result");
        $cursor = $collection->find(
            [
                "user_id" => $uid,
                "meta_id" => $meta_id,
                "record_id" => $record_id
            ],
            [
                'limit'=> 1,
            ]
        );
        $result = False;
        foreach ($cursor->toArray() as $o) {
            $result =  strval($o["result"]);
            break;
        }
        return $result;
    }

    function getEcgHistory(Request $request) {
        $tmax = intval($request->input('tmax', time()));
        $tmin = intval($request->input('tmin', $tmax-15*86400));
        $ecgResults = EcgStatistic::where('user_id', Auth::id())
            ->where('date', '<=' , date("Y-m-d", $tmax))
            ->where('date', '>=' , date("Y-m-d", $tmin))
            ->orderBy('date', 'asc')
            ->get()->toArray();

        $tmin = strtotime(date("Y-m-d"));
        $tmax = $tmin + 86400;
        $database = InfluxDBConnector::getDatabase();
        $result = $database->getQueryBuilder()
            ->select('count(distinct("tag"))')
            ->from("ecg")
            ->where(["user_id = '" . strval(Auth::id()) . "' AND time < " . strval($tmax) . "000000000 AND time >= " . strval($tmin) . "000000000"])
            ->orderBy("time", "asc")
            ->getResultSet()
            ->getPoints();
        $count = 0;
        if (count($result) > 0) {
            $count = intval($result[0]["count"]);
        }
        if ($count > 0) {
            $database = InfluxDBConnector::getDatabase();
            $result_last = $database->getQueryBuilder()
                ->select('last("tag") as "tag"')
                ->from("ecg")
                ->where(["user_id = '" . strval(Auth::id()) . "' AND time < " . strval($tmax) . "000000000 AND time >= " . strval($tmin) . "000000000"])
                ->orderBy("time", "asc")
                ->groupBy("tagtag")
                ->getResultSet()
                ->getPoints();
            $result_first = $database->getQueryBuilder()
                ->select('first("tag") as "tag"')
                ->from("ecg")
                ->where(["user_id = '" . strval(Auth::id()) . "' AND time < " . strval($tmax) . "000000000 AND time >= " . strval($tmin) . "000000000"])
                ->orderBy("time", "asc")
                ->groupBy("tagtag")
                ->getResultSet()
                ->getPoints();
            $results = array();
            foreach($result_last as $r) {
                $results[$r["tag"]] = array("last" => strtotime($r["time"]));
            }
            foreach($result_first as $r) {
                if (isset($results[$r["tag"]])) {
                    $results[$r["tag"]]["first"] = strtotime($r["time"]);
                }
            }
            $time = 0;
            foreach($results as $tag => $v) {
                if (isset($v["last"])) {
                    $time += $v["last"] - $v["first"];
                }
            }
            array_push($ecgResults, [
                'user_id' => Auth::id(),
                'date' => date("Y-m-d", $tmin),
                'count' => $count,
                'duration' => $time,
            ]);
        }
        return response()->json(['success' => true, 'data' => $ecgResults]);
    }

    public function pePredict(Request $request) {
        $meta = Meta::find(4);
        $database = MongoConnector::getDatabase();
        $collection = $database->selectCollection($meta->name);
        $id = $request->input('id', '');
        $cursor = $collection->find(
            [
                "_user_id" => Auth::id(),
                "_meta_id" => intval($meta->id),
                "_id" => new ObjectId($id)
            ],
            [
                'projection' => [
                    '_user_id' => 0,
                    '_meta_id' => 0,
                    '_id' => 0,
                    '_time' => 0
                ],
            ]
        );
        $result = NULL;
        foreach ($cursor->toArray() as $o) {
            $result = $o;
        }

        if($result === NULL) {
            return response()->json([
                'success' => false,
                'data' => "404"
            ]);
        }

        $name = Uuid::uuid4()->toString();

        $input = storage_path("app/input/" . $name);

        $file = fopen($input, "w");
        if (!$file)
            return response()->json([
                'success' => false,
                'data' => "500"
            ]);
        fwrite($file, json_encode($result));
        fclose($file);

        $this->dispatchNow(new RunModel(Auth::id(), 4, $id, "/usr/local/bin/python3 /Users/netlab/Server/medical-platform/app/Models/pe-data-mining/codes/predict.py " . $input));

        return response()->json([
            'success' => true,
            'data' => $id
        ]);
    }

    public function getPePredict(Request $request) {
        $name = $request->input('id', '');
        if ($name == '') {
            return response()->json([
                'success' => false,
                'data' => "400"
            ]);
        }
        $result = $this->getResult(Auth::id(), 4, $name);
        if ($result === False) {
            return response()->json([
                'success' => false,
                'data' => "400"
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

}
