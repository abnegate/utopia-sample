<?php

namespace SampleAPI\Data;

interface UpdatableDataSource
{
    public function update(Model $model);
}