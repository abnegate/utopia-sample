<?php

namespace SampleAPI\Data;

interface UpdatableDataSource
{
    /**
     * Update a model in a data-source.
     *
     * @param Model $model
     * @return mixed
     */
    public function update(Model $model);
}