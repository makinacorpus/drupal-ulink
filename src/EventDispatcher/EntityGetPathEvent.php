<?php

namespace MakinaCorpus\ULink\EventDispatcher;

use Symfony\Component\EventDispatcher\GenericEvent;

class EntityGetPathEvent extends GenericEvent
{
    const EVENT_GET_PATH = 'ulink.entity_get_path';

    private $type;
    private $id;
    private $path;

    public function __construct(string $type, string $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path ?? '';
    }
}
