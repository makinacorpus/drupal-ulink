<?php

namespace MakinaCorpus\ULink;

class EntityFinderResult
{
    private $type;

    private $id;

    private $title;

    private $group;

    private $description;

    /**
     * Default constructor
     *
     * @param string $type
     *   Entity type
     * @param int $id
     *   Entity identfier
     * @param string $title
     * @param string $group
     * @param string $description
     */
    public function __construct($type, $id, $title = null, $group = null, $description = null)
    {
        $this->type = $type;
        $this->id = $id;
        $this->title = $title;
        $this->group = $group;
        $this->description = $description;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
