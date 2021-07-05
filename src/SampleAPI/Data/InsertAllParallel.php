<?php

namespace SampleAPI\Data;

use Utopia\Database\Database;

trait InsertAllParallel
{
    public function __construct(
        ORM $orm,
        Database $db,
        string $class,
        Model ...$models
    )
    {
        Co\run(function () use ($db, $orm, $class, $models) {
            foreach ($models as $model) {
                go(function () use ($db, $orm, $class, $model) {
                    $orm->insert($db, $class, $model);
                });
            }
        });
    }
}
