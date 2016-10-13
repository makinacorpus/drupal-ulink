<?php

namespace MakinaCorpus\ULink\EventDispatcher;

use Symfony\Component\EventDispatcher\GenericEvent;

class EntityLinkFilterEvent extends GenericEvent
{
    const EVENT_BEFORE_FILTER = 'before_filter';

    /**
     * Constructor.
     *
     * @param array $uriInfo
     *  Information about all URIs found in a text, keyed by their offset.
     *
     *  [
     *    42 => [
     *      'uri'  => 'entity://node/1',
     *      'type' => 'node',
     *      'id'   => 1,
     *    ], ...
     *  ]
     *
     *  Alter a key (offset) or an original URI will have no effect.
     *
     * @param array $arguments
     */
    public function __construct(array $uriInfo, array $arguments = [])
    {
        parent::__construct($uriInfo, $arguments);
    }

    /**
     * Provides URI information.
     *
     * @return []
     */
    public function &getURIInfo()
    {
        return $this->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function &getSubject()
    {
        return $this->subject;
    }
}
