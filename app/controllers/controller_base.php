<?php

use SampleAPI\Data\Model;
use SampleAPI\Data\ORM\ORM;
use Utopia\CLI\Console;
use Utopia\Database\Document;
use Utopia\Response;


/**
 * Get all records of a model type from an ORM and respond with the JSONAPI representation of each.
 *
 * @throws Exception
 */
function get(ORM $orm, string $class, Response $response)
{
    try {
        $notes = $orm->find($class);
    } catch (Throwable) {
        $response->json(['data' => []]);
        return;
    }

    if (!$notes) {
        $response->json(['data' => []]);
        return;
    }

    $jsonNotes = array_map(function (Model $item) {
        return $item->getJSONAPI();
    }, $notes);

    $response->json([
        'data' => $jsonNotes
    ]);
}

/**
 * Get a records by ID from an ORM and respond with the JSONAPI representation.
 *
 * @param ORM $orm
 * @param string $class
 * @param $id
 * @param Response $response
 * @throws Exception
 */
function getById(ORM $orm, string $class, $id, Response $response)
{
    try {
        $model = $orm->findById($class, $id);
    } catch (Throwable $ex) {
        handleError($ex, $response);
        return;
    }

    if (!$model) {
        handleError(
            new Exception($class . ' with ID ' . $id . ' does not exist.'),
            $response
        );
        return;
    }

    $response->json([
        'data' => [$model->getJSONAPI()]
    ]);
}

/**
 * Update or create a model type with an ORM and respond with the JSONAPI representation.
 *
 * @throws Exception
 */
function put(ORM $orm, string $class, $id, array $attributes, Response $response)
{
    $model = $orm->findById($class, $id);

    if (!$model) {
        $model = new $class($id, ...$attributes);
        $orm->insert($model);
    } else {
        foreach ($attributes as $attr => $value) {
            $setter = 'set' . ucfirst($attr);
            if (!method_exists($model, $setter)) {
                throw new Exception("Failed patching note.");
            }
            $model->$setter($value);
        }
        $orm->update($model);
    }

    $response->json([
        'data' => [$model->getJSONAPI()]
    ]);
}

/**
 * Create a model type from an ORM and respond with the JSONAPI representation.
 *
 * @throws Exception
 */
function post(ORM $orm, string $class, array $attributes, Response $response)
{
    $note = new $class('', ...$attributes);

    try {
        /** @var Document $modelDocument */

        $modelDocument = $orm->insert($note);
    } catch (Throwable $ex) {
        handleError($ex, $response);
        return;
    }

    $response->json([
        'data' => [$note->getJSONAPIWithId($modelDocument->getId())]
    ]);
}

/**
 * Update a model type attribute from an ORM and respond with the JSONAPI representation.
 *
 * @throws Exception
 */
function patch(ORM $orm, string $class, $id, string $key, string $value, Response $response)
{
    /** @var Model $note */

    try {
        $note = $orm->findById($class, $id);
    } catch (Exception $ex) {
        handleError($ex, $response);
        return;
    }

    if (!$note) {
        handleError(
            new Exception('Note with ID ' . $id . ' does not exist.'),
            $response
        );
        return;
    }

    try {
        $setter = 'set' . ucfirst($key);
        if (!method_exists($note, $setter)) {
            throw new Exception("Failed patching note.");
        }
        $note->$setter($value);
        $orm->update($note);
    } catch (Exception $ex) {
        handleError($ex, $response);
        return;
    }

    $response->json(['data' => [$note->getJSONAPI()]]);
}

/**
 * Delete a record of a model type from an ORM.
 *
 * @throws Exception
 */
function delete(ORM $orm, string $class, $id, Response $response)
{
    try {
        $orm->delete($class, $id);
    } catch (Exception $ex) {
        handleError(
            new Exception("Delete failed.", previous: $ex),
            $response
        );
        return;
    }

    $response->noContent();
}

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