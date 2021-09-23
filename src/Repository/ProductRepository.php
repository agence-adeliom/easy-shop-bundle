<?php

namespace Adeliom\EasyShopBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use SyliusLabs\AssociationHydrator\AssociationHydrator;

class ProductRepository extends \Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository
{
    /** @var AssociationHydrator */
    private $associationHydrator;

    public function __construct(EntityManager $entityManager, Mapping\ClassMetadata $class)
    {
        parent::__construct($entityManager, $class);
        $this->associationHydrator = new AssociationHydrator($entityManager, $class);
    }

    public function findOneByChannelAndSlug(ChannelInterface $channel, string $locale, string $slug): ?ProductInterface
    {
        $slugsArray = preg_split('~/~', $slug, -1, PREG_SPLIT_NO_EMPTY);
        $slug = end($slugsArray);

        $product = $this->createQueryBuilder('o')
            ->addSelect('translation')
            ->innerJoin('o.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->andWhere('translation.slug = :slug')
            ->andWhere(':channel MEMBER OF o.channels')
            ->andWhere('o.enabled = :enabled')
            ->setParameter('channel', $channel)
            ->setParameter('locale', $locale)
            ->setParameter('slug', $slug)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $this->associationHydrator->hydrateAssociations($product, [
            'images',
            'options',
            'options.translations',
            'variants',
            'variants.channelPricings',
            'variants.optionValues',
            'variants.optionValues.translations',
        ]);

        return $product;
    }
}
