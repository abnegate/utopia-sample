<?php

use Utopia\Database\Database;

Database::addFilter('json',
    encode: function ($value) {
        if (!is_array($value)) {
            return $value;
        }
        return json_encode($value);
    },
    decode: function ($value) {
        return json_decode($value, true);
    }
);