<?php

namespace App\Console\Commands;

use App\InfluxDBConnector;
use App\User;
use App\EcgStatistic;
use Illuminate\Console\Command;

class ecgDoStatistic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecg:statistic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tmax = strtotime(date("Y-m-d"));
        $tmin = $tmax - 86400;
        $database = InfluxDBConnector::getDatabase();
        foreach(User::all() as $user) {
            $result = $database->getQueryBuilder()
                ->select('count(distinct("tag"))')
                ->from("ecg")
                ->where(["user_id = '" . strval($user->id) . "' AND time < " . strval($tmax) . "000000000 AND time >= " . strval($tmin) . "000000000"])
                ->orderBy("time", "asc")
                ->getResultSet()
                ->getPoints();
            $count = 0;
            if (count($result) > 0) {
                $count = intval($result[0]["count"]);
            }
            echo "uid: " . strval($user->id) . " count: " . strval($count) . "\n";
            if ($count > 0) {
                $database = InfluxDBConnector::getDatabase();
                $result_last = $database->getQueryBuilder()
                    ->select('last("tag") as "tag"')
                    ->from("ecg")
                    ->where(["user_id = '" . strval($user->id) . "' AND time < " . strval($tmax) . "000000000 AND time >= " . strval($tmin) . "000000000"])
                    ->orderBy("time", "asc")
                    ->groupBy("tagtag")
                    ->getResultSet()
                    ->getPoints();
                $result_first = $database->getQueryBuilder()
                    ->select('first("tag") as "tag"')
                    ->from("ecg")
                    ->where(["user_id = '" . strval($user->id) . "' AND time < " . strval($tmax) . "000000000 AND time >= " . strval($tmin) . "000000000"])
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
                EcgStatistic::create([
                    'user_id' => $user->id,
                    'date' => date("Y-m-d", $tmax),
                    'count' => $count,
                    'duration' => $time,
                ]);
            }
        }
    }
}
