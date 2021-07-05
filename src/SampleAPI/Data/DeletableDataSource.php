<?php

namespace SampleAPI\Data;

use Utopia\Database\Database;

interface DeletableDataSource
{
    public function delete(
        Database $db,
        string $class,
        object $id
    );
}