<?php

namespace SampleAPI\Data;

use Utopia\Database\Database;

interface SearchableDataSource
{
    public function find(
        Database $db,
        string $class,
        array $queries = [],
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    );

    public function findById(
        Database $db,
        string $class,
        object $id,
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    );
}

