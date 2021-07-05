<?php

use SampleAPI\Data\Model;
use SampleAPI\Data\ORM;
use SampleAPI\Model\Note;
use Utopia\App;
use Utopia\Database\Database;
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
    ->inject('db')
    ->action(function (
        Request $request,
        Response $response,
        ORM $orm,
        Database $db
    ) {
        try {
            $notes = $orm->find($db, Note::class);
        } catch (Throwable $e) {
            handleError($e, $response);
            return;
        }

        $jsonNotes = array_map(function (Model $item) {
            return $item->getJSONAPI();
        }, $notes);

        $response->json([
            'data' => $jsonNotes
        ]);
    });

App::get('/v1/note/:noteId')
    ->desc('Get a note by ID.')
    ->groups([TABLE])
    ->param('noteId', '', new UID(), 'Note unique ID.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->inject('db')
    ->action(function (
        object $noteId,
        array $fields,
        Request $request,
        Response $response,
        ORM $orm,
        Database $db
    ) {
        /** @var Note $note */

        try {
            $note = $orm->findById($db, Note::class, $noteId);
        } catch (Throwable $ex) {
            handleError($ex, $response);
            return;
        }

        if (null === $note) {
            throw new InvalidArgumentException('Note with ID' . $noteId . ' does not exist.');
        }

        $response->json(json_encode([
            'data' => [$note->getJSONAPI()]
        ]));
    });

App::put('/v1/note')
    ->desc('Update a note.')
    ->groups([TABLE])
    ->param('noteId', '', new UID(), 'Note unique ID.')
    ->param('title', '', new Text(128), 'Note title. Max length: 128 chars.')
    ->param('body', '', new Text(MB_AS_BYTES), 'Note body. Max length: 1048576 chars.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->inject('db')
    ->action(function (
        object $noteId,
        string $title,
        string $body,
        Request $request,
        Response $response,
        ORM $orm,
        Database $db
    ) {
        /** @var Note $note */

        $note = $orm->findById($db, Note::class, $noteId);

        if (null === $note) {
            $note = new Note($noteId, $title, $body);
        } else {
            $note->setTitle($title);
            $note->setBody($body);
        }

        $orm->update($db, Note::class, $note);

        $response->json(json_encode([
            'data' => [$note->getJSONAPI()]
        ]));
    });

App::post('/v1/note')
    ->desc('Add a new note.')
    ->groups([TABLE])
    ->param('title', '', new Text(128), 'Note title. Max length: 128 chars.')
    ->param('body', '', new Text(MB_AS_BYTES), 'Note body. Max length: 1048576 chars.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->inject('db')
    ->action(function (
        string $title,
        string $body,
        Request $request,
        Response $response,
        ORM $orm,
        Database $db
    ) {
        $note = new Note(new UID(), $title, $body);

        try {
            $orm->insert($db, Note::class, $note);
        } catch (Throwable $ex) {
            handleError($ex, $response);
            return;
        }

        $response->json(json_encode([
            'data' => [$note->getJSONAPI()]
        ]));
    });

App::patch('/v1/note/:noteId')
    ->desc('Update a note attribute.')
    ->groups([TABLE])
    ->param('noteId', new UID(), new UID(), 'Note unique ID.')
    ->param('key', '', new UID(), 'Note title.')
    ->param('value', '', new UID(), 'Note title.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->inject('db')
    ->action(function (
        object $noteId,
        string $key,
        string $value,
        Request $request,
        Response $response,
        ORM $orm,
        Database $db
    ) {
        /** @var Model $note */
        try {
            $note = $orm->findById($db, Note::class, $noteId);
        } catch (Exception $ex) {
            handleError($ex, $response);
            return;
        }

        if (null === $note) {
            throw new InvalidArgumentException('Note with ID' . $noteId . ' does not exist.');
        }

        $note[$key] = $value;

        try {
            $orm->update($db, Note::class, $note);
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
    ->inject('db')
    ->action(function (
        object $noteId,
        Request $request,
        Response $response,
        ORM $orm,
        Database $db
    ) {
        try {
            $success = $orm->delete($db, Note::class, $noteId);
        } catch (Exception $ex) {
            handleError($ex, $response);
            return;
        }

        $response
            ->setStatusCode($success ? 200 : 400)
            ->send();
    });
