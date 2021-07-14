<?php

namespace SampleAPI\Data\ORM;

use SampleAPI\Data\Model;

interface InsertableDataSource
{
    /**
     * Insert a model into a data-source
     *
     * @param Model $model
     * @return mixed
     */
    public function insert(Model $model);
}