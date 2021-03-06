<?php

namespace SampleAPI\Model;

use JetBrains\PhpStorm\ArrayShape;
use SampleAPI\Data\Model;

/**
 * A Note model containing a title and body.
 *
 * Class Note
 * @package SampleAPI\Model
 */
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
     * Get the title of this note.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the tile fo this note.
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get the body of this note.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set the body of this note.
     *
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    #[ArrayShape([
        'title' => "string",
        'body' => "string"
    ])]
    public function getAttributes(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}