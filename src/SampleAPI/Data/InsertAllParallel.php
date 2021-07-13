<?php

namespace SampleAPI\Data;

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
        Co\run(function () use ($orm, $models) {
            foreach ($models as $model) {
                go(function () use ($orm, $model) {
                    $orm->insert($model);
                });
            }
        });
    }
}
