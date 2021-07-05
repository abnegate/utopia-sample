<?php

namespace SampleAPI\Model;

use JetBrains\PhpStorm\ArrayShape;
use SampleAPI\Data\Model;

class Note extends Model
{
    public function __construct(
        protected object $id,
        public string $title,
        public string $body
    )
    {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    #[ArrayShape([
        'id' => 'object',
        'type' => 'string',
        'attributes' => 'array',
        'relationships' => 'array',
        'meta' => 'array',
        'links' => 'array'
    ])]
    public function getJSONAPI(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getJsonType(),
            'attributes' => $this->getAttributes()
        ];
    }

    public function getAttributes(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}