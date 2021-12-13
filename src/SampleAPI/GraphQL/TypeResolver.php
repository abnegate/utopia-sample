<?php

namespace SampleAPI\GraphQL;

use GraphQL\Type\Definition\Type;

interface TypeResolver
{
    public function resolveObjectType($model) : Type;

    public function resolveInputType($validator, $required, $injections) : Type;
}