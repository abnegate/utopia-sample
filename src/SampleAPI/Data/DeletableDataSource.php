<?php

namespace SampleAPI\Data;

interface DeletableDataSource
{
    public function delete(string $class, $id);
}