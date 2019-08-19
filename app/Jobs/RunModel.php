<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\MongoConnector;

class RunModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cmd;
    protected $uid;
    protected $meta_id;
    protected $record_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid, $meta_id, $record_id, $cmd)
    {
        $this->uid = $uid;
        $this->meta_id = $meta_id;
        $this->record_id = $record_id;
        $this->cmd = $cmd;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        exec($this->cmd, $res, $rc);
        if ($rc != 0 || count($res) == 0)
            return;

        $database = MongoConnector::getDatabase();
        $collection = $database->selectCollection("_result");
        $array = array("user_id" => $this->uid, "meta_id" => $this->meta_id, "record_id" => $this->record_id, "result" => $res[count($res) - 1]);
        $collection->insertOne($array);
    }
}
