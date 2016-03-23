<?php

namespace MakinaCorpus\ULink;

interface EntityFinderInterface
{
    /**
     * Get entity type this instance searches for
     *
     * @return string
     *   Might be either one of:
     *     - "ENTITY_TYPE"
     *     - "ENTITY_TYPE:BUNDLE"
     */
    public function getType();

    /**
     * Get label to display in autocomplete
     *
     * @return string
     */
    public function getLabel();

    /**
     * Find entities
     *
     * @param string $string
     *   String to search for
     * @param boolean $prefix
     *   If set to false, you may search infix
     * @param int $limit
     *   Maximum number of items to return
     *
     * @return EntityFinderResult[]
     */
    public function find($string, $prefix = true, $limit = 20);
}