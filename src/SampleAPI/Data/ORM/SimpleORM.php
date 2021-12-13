<?php

namespace SampleAPI\Data\ORM;

use Exception;
use SampleAPI\Data\Model;
use SampleAPI\Data\SortOrder;
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

    private function docToClass(Document $doc, string $class)
    {
        $props = $doc->getAttributes();
        $props['id'] = $doc->getId();
        return new $class(...$props);
    }

    public function find(
        string $class,
        array $queries = [],
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    ): array
    {
        $table = Strings::classToTableName($class);

        $docs = $this->db->find(
            $table,
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
        $table = Strings::classToTableName($model::class);

        $this->createTable($table, $model);

        try {
            $props = array_merge([
                '$read' => ['role:all'],
                '$write' => ['role:all'],
                '$collection' => $table,
            ], $model->getAttributesValues());

            $doc = $this->db->createDocument(
                $table,
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

            foreach ($model->getAttributes() as $name => $attribute) {
                $this->db->createAttribute(
                    $tableName,
                    $name,
                    $attribute['type'],
                    $attribute['size'],
                    $attribute['required'] ?? false,
                );
            }
        } catch (Throwable $ex) {
            Console::error($ex);
        }
    }

    /**
     * @throws Exception
     */
    public function update(Model $model)
    {
        $table = Strings::classToTableName($model::class);

        $doc = $this->db->getDocument(
            $table,
            $model->getId(),
        );

        foreach ($model->getAttributes() as $name => $attribute) {
            $doc->setAttribute($name, $attribute['value']);
        }

        $doc = $this->db->updateDocument(
            $table,
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
        $table = Strings::classToTableName($class);

        return $this->db->deleteDocument(
            $table,
            $id
        );
    }
}