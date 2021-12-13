<?php

namespace SampleAPI\GraphQL;

interface DataResolver
{
    public function resolveData($type, $args, $context, $info);
}