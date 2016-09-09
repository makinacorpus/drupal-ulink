<?php

namespace MakinaCorpus\ULink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Generate links from internal resources URI
 *
 * @todo unit test me!
 */
final class EntityLinkGenerator
{
    const SCHEME_REGEX = '@entity:(|//)([\w-]+)/([a-zA-Z\d]+)@';
    const STACHE_REGEX = '@\{\{([\w-]+)/([a-zA-Z\d]+)\}\}@';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
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
     */
    public function getEntityPathFromURI($uri)
    {
        $matches = [];

        if (preg_match(self::SCHEME_REGEX, $uri, $matches)) {
            return $this->getEntityPath($matches[2], $matches[3]);
        }
        if (preg_match(self::STACHE_REGEX, $uri, $matches)) {
            return $this->getEntityPath($matches[1], $matches[2]);
        }

        throw new \InvalidArgumentException(sprintf("%s: invalid entity URI scheme or malformed URI", $uri));
    }

    /**
     * Replace all occurences of entity URIs in text by the generated URLs
     *
     * @param string $text
     *
     * @return string
     */
    public function replaceAllInText($text)
    {
        $matches = [];

        if (preg_match_all(EntityLinkGenerator::SCHEME_REGEX, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach (array_reverse($matches[0]) as $match) {
                list($match, $offset) = $match;
                try {
                    $uri = url($match);
                } catch (\Exception $e) {
                    $uri = '#'; // Silent fail for frontend
                }
                $text = substr_replace($text, $uri, $offset, strlen($match));
            }
        }

        if (preg_match_all(EntityLinkGenerator::STACHE_REGEX, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach (array_reverse($matches[0]) as $match) {
                list($match, $offset) = $match;
                try {
                    $uri = url($match);
                } catch (\Exception $e) {
                    $uri = '#'; // Silent fail for frontend
                }
                $text = str_replace($match, $uri, $text);
            }
        }

        return $text;
    }
}
