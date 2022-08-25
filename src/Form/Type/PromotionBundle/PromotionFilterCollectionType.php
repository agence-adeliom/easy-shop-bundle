<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Adeliom\EasyShopBundle\Form\Type\PromotionBundle;

use Adeliom\EasyShopBundle\Form\Type\PromotionBundle\Filter\ProductFilterConfigurationType;
use Adeliom\EasyShopBundle\Form\Type\PromotionBundle\Filter\TaxonFilterConfigurationType;
use Sylius\Bundle\PromotionBundle\Form\Type\Filter\PriceRangeFilterConfigurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PromotionFilterCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('price_range_filter', PriceRangeFilterConfigurationType::class, [
            'label' => 'sylius.form.promotion_filter.price_range',
            'required' => false,
            'currency' => $options['currency'],
        ]);
        $builder->add('taxons_filter', TaxonFilterConfigurationType::class, [
            'label' => false,
            'required' => false,
        ]);
        $builder->add('products_filter', ProductFilterConfigurationType::class, [
            'label' => false,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('currency')
            ->setAllowedTypes('currency', 'string')
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_promotion_filters';
    }
}
