<?php

namespace SampleAPI\Data;

use Exception;
use SampleAPI\Strings;
use Throwable;
use Utopia\CLI\Console;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Authorization;

class SimpleORM implements ORM
{
    use InsertAllParallel;

    public function __construct(
        protected Database $db,
        protected string $schema
    )
    {
    }

    /**
     * @throws Exception
     */
    public function findById(
        string $class,
        $id,
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    )
    {
        $doc = $this->findDoc($class, $id);

        if ($doc->isEmpty()) {
            return null;
        }

        return $this->docToClass($doc, $class);
    }

    private function findDoc(string $class, $id)
    {
        $tableName = Strings::classToTableName($class);

        try {
            return $this->db->getDocument(
                $tableName,
                $id
            );
        } catch (Throwable $ex) {
            Console::error($ex);
        }

        return null;
    }

    public function find(
        string $class,
        array $queries = [],
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    ): array
    {
        $tableName = Strings::classToTableName($class);

        $docs = $this->db->find(
            $tableName,
            $queries,
            $count,
            $offset,
        );

        return array_map(function (Document $doc) use ($class) {
            return $this->docToClass($doc, $class);
        }, $docs);
    }

    /**
     * @throws Exception|Throwable
     */
    public function insert(Model $model)
    {
        $tableName = Strings::classToTableName($model::class);

        $this->createTable($tableName, $model);

        try {
            $props = array_merge([
                '$read' => ['role:all'],
                '$write' => ['role:all'],
                '$collection' => $tableName,
            ], $model->getAttributes());

            $doc = $this->db->createDocument(
                $tableName,
                new Document($props)
            );

            return $this->docToClass($doc, $model::class);
        } catch (Throwable $ex) {
            Console::error($ex);
            throw $ex;
        }
    }

    private function createTable(
        string $tableName,
        Model $model,
    )
    {
        try {
            if ($this->db->getCollection($tableName)->getId()) {
                return;
            }
        } catch (Throwable $th) {
            Console::error($th);
        }

        try {
            $this->db->createCollection($tableName);
            foreach ($model->getAttributes() as $attr => $val) {
                $type = is_numeric($val)
                    ? Database::VAR_INTEGER
                    : Database::VAR_STRING;

                $this->db->createAttribute(
                    $tableName,
                    $attr,
                    $type,       // FIXME: This should come from the model
                    5000,   // FIXME: This should come from the model
                    true // FIXME: This should come from the model
                );
            }
        } catch (Throwable $ex) {
            Console::error($ex);
        }
    }

    private function docToClass(Document $doc, string $class)
    {
        $props = $doc->getAttributes();
        $props['id'] = $doc->getId();
        return new $class(...$props);
    }

    /**
     * @throws Exception
     */
    public function update(Model $model)
    {
        $doc = $this->db->getDocument(
            Strings::classToTableName($model::class),
            $model->getId(),
        );

        foreach ($model->getAttributes() as $attr => $val) {
            $doc->setAttribute($attr, $val);
        }

        $doc = $this->db->updateDocument(
            Strings::classToTableName($model::class),
            $model->getId(),
            $doc,
        );

        return $this->docToClass($doc, $model::class);
    }

    /**
     * @throws Authorization
     */
    public function delete(
        string $class,
        $id
    ): bool
    {
        return $this->db->deleteDocument(
            Strings::classToTableName($class),
            $id
        );
    }
}