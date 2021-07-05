<?php

use Utopia\Database\Database;

Database::addFilter('json',
    function ($value) {
        if (!is_array($value)) {
            return $value;
        }
        return json_encode($value);
    },
    function ($value) {
        return json_decode($value, true);
    }
);