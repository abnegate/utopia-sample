<?php

use Utopia\CLI\Console;
use Utopia\Response;

/**
 * Handle a request exception
 *
 * @throws Exception
 */
function handleError(Throwable $ex, Response $response)
{
    Console::error($ex);

    $response->json(['errors' => [[
        'title' => $ex->getMessage(),
        'detail' => $ex->getTraceAsString()
    ]]]);
}