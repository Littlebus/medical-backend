<?php

namespace App;
use InfluxDB\Client as Another;

class InfluxDBConnector
{
    static function getDatabase() {
        $database = Another::fromDSN(sprintf('influxdb://%s:%s@%s:%s/%s', getenv('INFLUXDB_USERNAME'), getenv('INFLUXDB_PASSWORD'), getenv('INFLUXDB_HOST'), getenv('INFLUXDB_PORT'), getenv('INFLUXDB_DATABASE')));
        return $database;
    }
}