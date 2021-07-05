<?php

namespace SampleAPI\Data;

use Utopia\Database\Database;

interface UpdatableDataSource
{
    public function update(
        Database $db,
        string $class,
        Model $model
    );
}