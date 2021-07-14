<?php

namespace SampleAPI\Data\ORM;

use SampleAPI\Data\Model;

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