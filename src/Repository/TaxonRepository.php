<?php

namespace Adeliom\EasyShopBundle\Repository;

use Sylius\Component\Taxonomy\Model\TaxonInterface;

class TaxonRepository extends \Sylius\Bundle\TaxonomyBundle\Doctrine\ORM\TaxonRepository
{
    public function findOneBySlug(string $slug, string $locale): ?TaxonInterface
    {
        $slugsArray = preg_split('~/~', $slug, -1, PREG_SPLIT_NO_EMPTY);
        $slug = end($slugsArray);
        return $this->createQueryBuilder('o')
            ->addSelect('translation')
            ->innerJoin('o.translations', 'translation')
            ->andWhere('o.enabled = :enabled')
            ->andWhere('translation.slug = :slug')
            ->andWhere('translation.locale = :locale')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
