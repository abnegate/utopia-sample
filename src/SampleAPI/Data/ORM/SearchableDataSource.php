<?php

namespace SampleAPI\Data\ORM;

use SampleAPI\Data\SortOrder;

interface SearchableDataSource
{

    /**
     * Find a model in a data-source
     *
     * @param string $class
     * @param array $queries
     * @param int $count
     * @param int $offset
     * @param int $order
     * @return mixed
     */
    public function find(
        string $class,
        array $queries = [],
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    );

    /**
     * Find a model in a data-source by ID.
     *
     * @param string $class
     * @param $id
     * @param int $count
     * @param int $offset
     * @param int $order
     * @return mixed
     */
    public function findById(
        string $class,
        $id,
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    );
}

