<?php

namespace SampleAPI;

class Strings
{
    public static function classToTableName(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }
}