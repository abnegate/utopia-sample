<?php

namespace SampleAPI\Model;

use SampleAPI\Data\Model;

class Note extends Model
{
    public function __construct(
        protected $id,
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

    public function getAttributes(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}