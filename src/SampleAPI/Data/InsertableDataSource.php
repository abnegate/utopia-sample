<?php

namespace SampleAPI\Data;

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