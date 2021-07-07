<?php

namespace SampleAPI\Data;

interface InsertableDataSource
{
    public function insert(Model $model);
}