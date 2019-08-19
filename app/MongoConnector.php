<?php

namespace App;
use MongoDB\Client;

class MongoConnector
{
    static function getDatabase() {
        $client = new Client(sprintf('mongodb://%s:%s/', getenv('MONGO_HOST'), getenv('MONGO_PORT')),
            [
                'username' => getenv('MONGO_USERNAME'),
                'password' => getenv('MONGO_PASSWORD'),
                'authSource' => 'admin',
            ]);
        return $client->selectDatabase(getenv('MONGO_DATABASE'));
    }
}