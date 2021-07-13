<?php

use SampleAPI\Data\ORM;
use SampleAPI\Model\Note;
use SampleAPI\Strings;
use Utopia\App;
use Utopia\Database\Validator\UID;
use Utopia\Request;
use Utopia\Response;
use Utopia\Validator\Text;

include __DIR__ . '/../controller_base.php';

const MB_AS_BYTES = 1048576;

const CLAZZ = Note::class;

define(
    "TABLE",
    Strings::classToTableName(CLAZZ)
);

App::get('/v1/' . TABLE)
    ->desc('Get all resource entities.')
    ->groups([TABLE])
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        get($orm, CLAZZ, $response);
    });

App::get('/v1/' . TABLE . '/:id')
    ->desc('Get a resource entity by ID.')
    ->groups([TABLE])
    ->param('id', null, new UID(), 'Unique ID.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $id,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        getById($orm, CLAZZ, $id, $response);
    });

App::put('/v1/' . TABLE . '/:id')
    ->desc('Update a resource.')
    ->groups([TABLE])
    ->param('id', null, new UID(), 'Note unique ID.')
    // TODO: Get params from CLAZZ
    ->param('title', '', new Text(5000), 'Note title. Max length: 5000 chars.')
    ->param('body', '', new Text(MB_AS_BYTES), 'Note body. Max length: 1048576 chars.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $id,
        string $title,
        string $body,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        put($orm, CLAZZ, $id, [
            'title' => $title,
            'body' => $body,
        ], $response);
    });

App::post('/v1/' . TABLE)
    ->desc('Add a new resource entity.')
    ->groups([TABLE])
    // TODO: Get params from CLAZZ
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
        post($orm, CLAZZ, [$title, $body], $response);
    });

App::patch('/v1/' . TABLE . '/:id')
    ->desc('Update a resource entity attribute.')
    ->groups([TABLE])
    ->param('id', null, new UID(), 'Unique ID.')
    // TODO: Get params from CLAZZ
    ->param('key', '', new Text(0), 'Attribute key.')
    ->param('value', '', new Text(0), 'Attribute value.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $id,
        string $key,
        string $value,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        patch($orm, CLAZZ, $id, $key, $value, $response);
    });

App::delete('/v1/' . TABLE . '/:id')
    ->groups([TABLE])
    ->param('id', '', new UID(), 'Unique ID.')
    ->inject('request')
    ->inject('response')
    ->inject('orm')
    ->action(function (
        $id,
        Request $request,
        Response $response,
        ORM $orm,
    ) {
        delete($orm, CLAZZ, $id, $response);
    });
