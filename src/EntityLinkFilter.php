<?php

namespace MakinaCorpus\ULink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityLinkFilter extends FilterBase implements ContainerFactoryPluginInterface
{
    const ENTITY_REGEX = '@entity://([\w-]+)/([a-zA-Z\d]+)@';

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        return new static(
            $configuration,
            $pluginId,
            $pluginDefinition,
            $container->get('entity.manager')
        );
    }

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param mixed[] $configuration
     * @param string $pluginId
     * @param string $pluginDefinition
     * @param EntityManager $entityManager
     */
    public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityManager $entityManager)
    {
        parent::__construct($configuration, $pluginId, $pluginDefinition);

        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode)
    {
        $matches  = [];
        $done     = [];

        if (preg_match_all(self::ENTITY_REGEX, $text, $matches)) {
            foreach ($matches[0] as $index => $match) {

                if (isset($done[$match])) {
                    continue;
                }
                $done[$match] = true;

                $type = $matches[1][$index];
                $id   = $matches[2][$index];

                try {
                    $entity = $this->entityManager->getStorage($type)->load($id);

                    // To be remove for Drupal 8
                    if (!$entity instanceof EntityInterface) {
                        $uri = entity_uri($type, $entity);
                        if (!$uri) {
                            throw new \InvalidArgumentException(sprintf("%s: entity type is not supported yet"));
                        }
                    } else {
                        $uri = $entity->url();
                    }

                    if (!$uri) {
                        throw new \InvalidArgumentException(sprintf("%s: entity type cannot provide URI"));
                    }

                    $text = str_replace($match, $uri, $text);

                } catch (\Exception $e) {
                    // Entity type does not exist, just fail silently, don't
                    // even I don't care...
                }
            }
        }

        return new FilterProcessResult($text);
    }
}