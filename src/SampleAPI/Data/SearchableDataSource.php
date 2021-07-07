<?php

namespace SampleAPI\Data;

interface SearchableDataSource
{
    public function find(
        string $class,
        array $queries = [],
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    );

    public function findById(
        string $class,
        $id,
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    );
}

