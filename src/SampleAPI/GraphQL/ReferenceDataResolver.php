<?php

namespace SampleAPI\GraphQL;

use Utopia\Database\Database;
use Utopia\Registry\Registry;
use function var_dump;

class ReferenceDataResolver implements DataResolver
{
    public function __construct(protected TypeResolver $typeResolver)
    {
    }

    public function resolveData($type, $args, $context, $info)
    {
        /** @var Registry $register */
        /** @var Database $db */

        $register = $context['register'];
        $db = $register->get('db');


        var_dump($type, $args, $context, $info);
    }
}