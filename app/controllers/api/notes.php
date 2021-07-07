<?php

use SampleAPI\Data\Model;
use SampleAPI\Data\ORM;
use SampleAPI\Model\Note;
use Utopia\App;
use Utopia\CLI\Console;
use Utopia\Database\Document;
use Utopia\Database\Validator\UID;
use Utopia\Request;
use Utopia\Response;
use Utopia\Validator\Text;

include __DIR__ . '/../controller_base.php';

const MB_AS_BYTES = 1048576;
const TABLE = 'notes';

App::get('/v1/note')
    ->desc('Get all notes.')
    ->groups([TABLE])
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        try {
            $notes = $orm->find(Note::class);
        } catch (Throwable $e) {
            handleError($e, $response);
            return;
        }

        try {
            $jsonNotes = array_map(function (Note $item) {
                return $item->getJSONAPI();
            }, $notes);

            $response->json([
                'data' => $jsonNotes
            ]);
        } catch (Throwable $ex) {
            Console::error($ex);
        }
    });

App::get('/v1/note/:noteId')
    ->desc('Get a note by ID.')
    ->groups([TABLE])
    ->param('noteId', null, new UID(), 'Note unique ID.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $noteId,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        /** @var Note $note */

        try {
            $note = $orm->findById(Note::class, $noteId);
        } catch (Throwable $ex) {
            handleError($ex, $response);
            return;
        }

        $response->json([
            'data' => [$note->getJSONAPI()]
        ]);
    });

App::put('/v1/note/:noteId')
    ->desc('Update a note.')
    ->groups([TABLE])
    ->param('noteId', '', new UID(), 'Note unique ID.')
    ->param('title', '', new Text(5000), 'Note title. Max length: 5000 chars.')
    ->param('body', '', new Text(MB_AS_BYTES), 'Note body. Max length: 1048576 chars.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $noteId,
        string $title,
        string $body,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        /** @var Note $note */

        $note = $orm->findById(Note::class, $noteId);

        if (null === $note) {
            $note = new Note($noteId, $title, $body);
        } else {
            $note->setTitle($title);
            $note->setBody($body);
        }

        $orm->update($note);

        $response->json([
            'data' => [$note->getJSONAPI()]
        ]);
    });

App::post('/v1/note')
    ->desc('Add a new note.')
    ->groups([TABLE])
    ->param('title', '', new Text(1024), 'Note title. Max length: 128 chars.')
    ->param('body', '', new Text(0), 'Note body.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        string $title,
        string $body,
        Request $request,
        Response $response,
        ORM $orm
    ) {
        $note = new Note('', $title, $body);

        try {
            /** @var Document $noteDocument */
            $noteDocument = $orm->insert($note);
        } catch (Throwable $ex) {
            handleError($ex, $response);
            return;
        }

        try {
            $response->json([
                'data' => [
                    $note->getJSONAPIWithId($noteDocument->getId())
                ]
            ]);
        } catch (Throwable $ex) {
            Console::log($ex);
        }
    });

App::patch('/v1/note/:noteId')
    ->desc('Update a note attribute.')
    ->groups([TABLE])
    ->param('noteId', new UID(), new UID(), 'Note unique ID.')
    ->param('key', '', new Text(0), 'Note title.')
    ->param('value', '', new Text(0), 'Note title.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $noteId,
        string $key,
        string $value,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        /** @var Model $note */
        try {
            $note = $orm->findById(Note::class, $noteId);
        } catch (Exception $ex) {
            handleError($ex, $response);
            return;
        }

        if (null === $note) {
            throw new InvalidArgumentException('Note with ID' . $noteId . ' does not exist.');
        }

        $note[$key] = $value;

        try {
            $orm->update($note);
        } catch (Exception $ex) {
            handleError($ex, $response);
            return;
        }

        $response->json(json_encode([
            'data' => [$note->getJSONAPI()]
        ]));
    });

App::delete('/v1/note/:noteId')
    ->groups([TABLE])
    ->param('noteId', '', new UID(), 'Note unique ID.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $noteId,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        try {
            $orm->delete(Note::class, $noteId);
        } catch (Exception $ex) {
            handleError($ex, $response);
            return;
        }

        $response->noContent();
    });
