<?php

use SampleAPI\Data\ORM\ORM;
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

try {
    App::get('/v1/' . TABLE)
        ->desc('Get all resource entities.')
        ->groups([TABLE])
        ->label('response.model', CLAZZ)
        ->label('method', 'getAll' . CLAZZ)
        ->inject('request')
        ->inject('response')
        ->inject('orm')
        ->action(function (
            Request  $request,
            Response $response,
            ORM      $orm,
        ) {
            get($orm, CLAZZ, $response);
        });
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    App::get('/v1/' . TABLE . '/:id')
        ->desc('Get a resource entity by ID.')
        ->groups([TABLE])
        ->label('response.model', CLAZZ)
        ->label('method', 'get' . CLAZZ)
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
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    App::put('/v1/' . TABLE . '/:id')
        ->desc('Update a resource.')
        ->groups([TABLE])
        ->label('response.model', CLAZZ)
        ->label('method', 'update' . CLAZZ)
        ->param('id', null, new UID(), 'Note unique ID.')
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
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    App::post('/v1/' . TABLE)
        ->desc('Add a new resource entity.')
        ->groups([TABLE])
        ->label('response.model', CLAZZ)
        ->label('method', 'create' . CLAZZ)
        ->param('title', '', new Text(1024), 'Note title. Max length: 128 chars.')
        ->param('body', '', new Text(0), 'Note body.')
        ->inject('request')
        ->inject('response')
        ->inject('orm')
        ->action(function (
            string   $title,
            string   $body,
            Request  $request,
            Response $response,
            ORM      $orm
        ) {
            post($orm, CLAZZ, [$title, $body], $response);
        });
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    App::patch('/v1/' . TABLE . '/:id')
        ->desc('Update a resource entity attribute.')
        ->groups([TABLE])
        ->label('response.model', CLAZZ)
        ->label('method', 'update' . CLAZZ . 'Attribute')
        ->param('id', null, new UID(), 'Unique ID.')
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
} catch (Exception $e) {
    echo $e->getMessage();
}

try {
    App::delete('/v1/' . TABLE . '/:id')
        ->groups([TABLE])
        ->label('response.model', 'any')
        ->label('method', 'delete' . CLAZZ)
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
} catch (Exception $e) {
    echo $e->getMessage();
}
