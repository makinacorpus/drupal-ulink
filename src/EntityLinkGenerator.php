<?php

namespace MakinaCorpus\ULink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\ULink\EventDispatcher\EntityGetPathEvent;
use MakinaCorpus\ULink\EventDispatcher\EntityLinkFilterEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Generate links from internal resources URI
 *
 * @todo
 *  - Unit test me!
 *  - Replace all arrays of URI information by instances
 *    of a class designed for that.
 */
final class EntityLinkGenerator
{
    const SCHEME_TYPE = 'scheme';
    const STACHE_TYPE = 'stache';

    const SCHEME_REGEX = '@entity:(?:|//)(?<type>[\w-]+)/(?<id>[a-zA-Z\d]+)@';
    const STACHE_REGEX = '@\{\{(?<type>[\w-]+)/(?<id>[a-zA-Z\d]+)\}\}@';

    /**
     * Performance optimization for various Drupal hooks.
     *
     * This always must match above regexes.
     *
     * @param string $uri
     *
     * @return boolean
     */
    static public function URIIsCandidate($uri)
    {
        return '{' === $uri[0] || 'entity://' === substr($uri, 0, 9) || ('node' !== substr($uri, 0, 4) && 1 === substr_count($uri, '/'));
    }

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EntityManager $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Provides URI regex patterns keyed by types.
     *
     * @return string[]
     */
    private function getURIPatterns()
    {
        return [
            self::SCHEME_TYPE => self::SCHEME_REGEX,
            self::STACHE_TYPE => self::STACHE_REGEX,
        ];
    }

    /**
     * Uses drupal 7 API to generate the entity URL
     *
     * @param string $type
     * @param mixed $entity
     *
     * @return string
     */
    private function getDrupalEntityPath($type, $entity)
    {
        $uri = entity_uri($type, $entity);

        if (!$uri) {
            throw new \InvalidArgumentException(sprintf("%s: entity type is not supported yet"));
        }

        return $uri['path'];
    }

    /**
     * Get entity internal path
     *
     * @param string $type
     *   Entity type
     * @param int|string $id
     *   Entity identifier
     *
     * @return string
     *   The Drupal internal path
     */
    public function getEntityPath($type, $id)
    {
        // Allow other modules to interact
        $event = new EntityGetPathEvent($type, $id);
        $this->eventDispatcher->dispatch(EntityGetPathEvent::EVENT_GET_PATH, $event);
        if ($path = $event->getPath()) {
            return $path;
        }

        // In most cases, this will be used for nodes only, so just set the
        // node URL.
        // It will avoid nasty bugs, since the 'text' core module does sanitize
        // (and call check_markup()) during field load if there are any circular
        // links dependencies between two nodes, it triggers an finite loop.
        // This will also make the whole faster.
        // @todo If node does not exists, no error will be triggered.
        if ('node' === $type) {
            return 'node/' . $id;
        }

        $entity = $this->entityManager->getStorage($type)->load($id);
        if (!$entity) {
            throw new \InvalidArgumentException(sprintf("entity of type %s with identifier %s does not exist", $type, $id));
        }

        if (!$entity instanceof EntityInterface) {
            return $this->getDrupalEntityPath($type, $entity);
        } else {
            return $entity->url();
        }
    }

    /**
     * Get entity internal path from internal URI
     *
     * @param string $uri
     *   Must match one of the supported schemes
     *
     * @throws \InvalidArgumentException
     */
    public function getEntityPathFromURI($uri)
    {
        if ($parts = $this->decomposeURI($uri)) {
            return $this->getEntityPath($parts['type'], $parts['id']);
        }

        throw new \InvalidArgumentException(sprintf("%s: invalid entity URI scheme or malformed URI", $uri));
    }

    /**
     * Extract entity type and id from the given URI.
     *
     * @param string $uri
     * @param string $type
     *  The type of the URI will be passed on to you through this argument.
     *
     * @return string[]
     */
    public function decomposeURI($uri, &$type = null)
    {
        $elements = [];

        if (!self::URIIsCandidate($uri)) {
            return $elements;
        }

        foreach ($this->getURIPatterns() as $name => $pattern) {
            if (preg_match($pattern, $uri, $matches)) {
                $type = $name;
                $elements = [
                    'type'  => $matches['type'],
                    'id'    => $matches['id'],
                ];
                break;
            }
        }

        return $elements;
    }

    /**
     * Format an URI.
     *
     * @param string Entity type
     * @param integer Entity identifier
     * @param string Type of the URI
     *
     * @return string
     */
    public function formatURI($type, $id, $uriType = self::SCHEME_TYPE)
    {
        switch ($uriType) {
            case self::SCHEME_TYPE:
                return 'entity://' . $type . '/' . $id;
            case self::STACHE_TYPE:
                return '{{' . $type . '/' . $id . '}}';
        }
        throw new \InvalidArgumentException(sprintf("%s: invalid URI type", $uriType));
    }

    /**
     * Replace all occurences of entity URIs in text by the generated URLs.
     *
     * @param string $text
     *
     * @return string
     */
    public function replaceAllInText($text)
    {
        $uriInfo = [];

        // Collects all µlink URIs present in the text and stores them
        // in an array keyed by their offset.
        foreach ($this->getURIPatterns() as $pattern) {
            $matches = [];

            if (preg_match_all($pattern, $text, $matches,  PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                foreach ($matches as $match) {
                    list($uri, $offset) = $match[0];

                    $uriInfo[$offset] = [
                        'uri'   => $uri,
                        'type'  => $match['type'][0],
                        'id'    => $match['id'][0],
                    ];
                }
            }
        }

        // Sorts collected URIs by descending offset to start replacements
        // by the end of the text, ensuring in this way that the next offsets
        // will stay valid after each replacement.
        krsort($uriInfo);

        // Dispatches an event to allow some alterations of URIs information
        // before transform them in standard URLs.
        $event = new EntityLinkFilterEvent($uriInfo);
        $this->eventDispatcher->dispatch(EntityLinkFilterEvent::EVENT_BEFORE_FILTER, $event);

        // Replaces µlink URIs by standard URLs.
        foreach ($event->getURIInfo() as $offset => $info) {
            // Something inserted a new entry!?
            // Ok, just ignore it.
            if (!isset($uriInfo[$offset])) {
                continue;
            }
            // Something altered the original URI!?
            // Ok, just retrieve the text's version for the next.
            if ($info['uri'] !== $uriInfo[$offset]['uri']) {
                $info['uri'] = $uriInfo[$offset]['uri'];
            }

            try {
                $uri = url($this->getEntityPath($info['type'], $info['id']));
            } catch (\Exception $e) {
                $uri = '#'; // Silent fail for frontend
            }

            $text = substr_replace($text, $uri, $offset, strlen($info['uri']));
        }

        return $text;
    }
}
