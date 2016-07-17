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
    const SCHEME_REGEX = '@entity:(|//)([\w-]+)/([a-zA-Z\d]+)@';
    const MOUSTACHE_REGEX = '@\{\{([\w-]+)/([a-zA-Z\d]+)\}\}@';

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

    protected function getEntityURI($type, $id)
    {
        // In most cases, this will be used for nodes only, so just set the
        // node URL.
        // It will avoid nasty bugs, since the 'text' core module does sanitize
        // (and call check_markup()) during field load if there are any circular
        // links dependencies between two nodes, it triggers an finite loop.
        // This will also make the whole faster.
        // @todo If node does not exists, no error will be triggered.
        if ('node' === $type) {
            $uri = url('node/' . $id);
        } else {
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
        }

        if (!$uri) {
            throw new \InvalidArgumentException(sprintf("%s: entity type cannot provide URI"));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode)
    {
        $matches  = [];
        $done     = [];

        if (preg_match_all(self::SCHEME_REGEX, $text, $matches)) {
            foreach ($matches[0] as $index => $match) {

                if (isset($done[$match])) {
                    continue;
                }
                $done[$match] = true;

                $type = $matches[2][$index];
                $id   = $matches[3][$index];

                try {
                    $uri = $this->getEntityURI($type, $id);
                } catch (\Exception $e) {
                    // Entity type does not exist, just fail silently, don't
                    // even I don't care...
                    $uri = '#';
                }

                $text = str_replace($match, $uri, $text);
            }
        }

        if (preg_match_all(self::MOUSTACHE_REGEX, $text, $matches)) {
            foreach ($matches[0] as $index => $match) {

                if (isset($done[$match])) {
                    continue;
                }
                $done[$match] = true;

                $type = $matches[1][$index];
                $id   = $matches[2][$index];

                try {
                    $uri = $this->getEntityURI($type, $id);
                } catch (\Exception $e) {
                    // Entity type does not exist, just fail silently, don't
                    // even I don't care...
                    $uri = '#';
                }

                $text = str_replace($match, $uri, $text);
            }
        }

        return new FilterProcessResult($text);
    }
}
