<?php

namespace SampleAPI\Data\ORM;

use SampleAPI\Data\Model;
use function Co\run;

trait InsertAllParallel
{

    /**
     * Insert all
     *
     * @param ORM $orm
     * @param Model ...$models
     */
    public function insertAll(
        ORM $orm,
        Model ...$models
    )
    {
        run(function () use ($orm, $models) {
            foreach ($models as $model) {
                go(function () use ($orm, $model) {
                    $orm->insert($model);
                });
            }
        });
    }
}
