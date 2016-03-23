<?php

namespace MakinaCorpus\ULink;

class EntityFinderRegistry
{
    /**
     * @var EntityFinderInterface[][]
     */
    private $instances = [];

    /**
     * Register a single instance
     *
     * @param EntityFinderInterface $instance
     */
    public function register(EntityFinderInterface $instance)
    {
        $this->instances[$instance->getType()][] = $instance;
    }

    /**
     * Get all instances for type(s)
     *
     * @param string|string[] $types
     *
     * @return EntityFinderInterface[]
     */
    public function get($types)
    {
        $ret = [];

        if (!$types) {
            return $ret;
        }

        if (!is_array($types)) {
            $types = [];
        }

        foreach ($types as $type) {
            if (isset($this->instances[$type])) {
                $ret = array_merge($ret, $this->instances[$type]);
            }
        }

        return $ret;
    }

    /**
     * Get all instances
     *
     * @return EntityFinderInterface[]
     */
    public function all()
    {
        $ret = [];

        foreach ($this->instances as $instances) {
            $ret = array_merge($ret, $instances);
        }

        return $ret;
    }
}