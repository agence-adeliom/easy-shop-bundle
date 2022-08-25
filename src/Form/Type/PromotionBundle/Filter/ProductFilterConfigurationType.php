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

namespace Adeliom\EasyShopBundle\Form\Type\PromotionBundle\Filter;

use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductFilterConfigurationType extends AbstractType
{
    public function __construct(private readonly DataTransformerInterface $productsToCodesTransformer, private readonly string $model)
    {
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('products', EntityType::class, [
                'class' => $this->model,
                'label' => 'sylius.form.promotion_filter.products',
                'multiple' => true,
                'choice_value' => 'code',
                'choice_name' => 'name',
                'attr' => [
                    'data-ea-widget' => "ea-autocomplete"
                ]
            ])
        ;

        $builder->get('products')->addModelTransformer($this->productsToCodesTransformer);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_promotion_action_filter_product_configuration';
    }
}
