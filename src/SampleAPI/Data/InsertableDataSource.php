<?php

namespace SampleAPI\Data;

use Utopia\Database\Database;

interface InsertableDataSource
{
    public function insert(
        Database $db,
        string $class,
        Model $model
    );
}