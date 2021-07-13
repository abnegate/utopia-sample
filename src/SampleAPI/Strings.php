<?php

namespace SampleAPI;

class Strings
{
    /**
     * Get a simple table name from a class.
     *
     * @param string $class
     * @return string
     */
    public static function classToTableName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }
}