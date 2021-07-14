<?php

namespace SampleAPI\Data\ORM;

interface DeletableDataSource
{
    /**
     * Delete an item from a data-source by ID.
     *
     * @param string $class
     * @param $id
     * @return mixed
     */
    public function delete(string $class, $id);
}