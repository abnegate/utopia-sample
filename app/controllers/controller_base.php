<?php

use Utopia\Response;

/**
 * @throws Exception
 */
function handleError(Throwable $ex, Response &$response)
{
    $response->json(['errors' => [[
        'title' => $ex->getMessage(),
        'detail' => $ex->getTraceAsString()
    ]]]);
}