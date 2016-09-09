<?php

namespace MakinaCorpus\ULink;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityLinkFilter extends FilterBase implements ContainerFactoryPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        return new static(
            $configuration,
            $pluginId,
            $pluginDefinition,
            $container->get('ulink.entity_link_generator')
        );
    }

    /**
     * @var EntityLinkGenerator
     */
    private $linkGenerator;

    /**
     * Default constructor
     *
     * @param mixed[] $configuration
     * @param string $pluginId
     * @param string $pluginDefinition
     * @param EntityLinkGenerator $linkGenerator
     */
    public function __construct(array $configuration, $pluginId, $pluginDefinition, EntityLinkGenerator $linkGenerator)
    {
        parent::__construct($configuration, $pluginId, $pluginDefinition);

        $this->linkGenerator = $linkGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function process($text, $langcode)
    {
        return new FilterProcessResult($this->linkGenerator->replaceAllInText($text));
    }
}
