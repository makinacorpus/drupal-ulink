<?php

namespace MakinaCorpus\ULink\Finder;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\ULink\EntityFinderInterface;
use MakinaCorpus\ULink\EntityFinderResult;

class NodeFinder implements EntityFinderInterface
{
    use StringTranslationTrait;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var string
     */
    private $bundle;

    /**
     * @var string
     */
    private $name;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param string $bundle
     * @param string $name
     */
    public function __construct(\DatabaseConnection $db, $bundle, $name = null)
    {
        $this->db = $db;
        $this->bundle = $bundle;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'node:' . $this->bundle;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->name ? $this->t($this->name) : $this->t("@type content", ['@type' => $this->bundle]);
    }

    /**
     * {@inheritdoc}
     */
    public function find($string, $prefix = true, $limit = 20)
    {
        if ($prefix) {
            $escaped = db_like($string) . '%';
        } else {
            $escaped = '%' . db_like($string) . '%';
        }

        $map = $this
            ->db
            ->select('node', 'n')
            ->fields('n', ['nid', 'title'])
            ->condition('n.type', $this->bundle)
            ->condition('n.title', $escaped, 'LIKE')
            ->orderBy('n.title')
            ->range(0, $limit)
            ->execute()
            ->fetchAllKeyed()
        ;

        $ret = [];

        foreach ($map as $id => $title) {
            $ret[] = new EntityFinderResult('node', $id, $title, $this->getLabel());
        }

        return $ret;
    }
}