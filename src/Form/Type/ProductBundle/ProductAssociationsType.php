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

namespace Adeliom\EasyShopBundle\Form\Type\ProductBundle;

use App\Entity\Shop\Product\Product;
use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\FixedCollectionType;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductAssociationsType extends AbstractType
{
    public function __construct(private readonly RepositoryInterface $productAssociationTypeRepository, private readonly RepositoryInterface $productRepository, private readonly DataTransformerInterface $productsToProductAssociationsTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $assoc = $this->productAssociationTypeRepository->findAll();
        foreach ($assoc as $item) {
            $code = $item->getCode();
            $builder->add($code, EntityType::class, [
                'property_path' => sprintf('[%s]', $code),
                'label' => $item->getName() ?: $item->getCode(),
                'class' => $this->productRepository->getClassName(),
                'choice_value' => 'code',
                'multiple' => true,
                'attr' => [
                    'data-ea-widget' => "ea-autocomplete",
                    "data-ea-autocomplete-endpoint-url" => $options['data-ea-autocomplete-endpoint-url']
                ]
            ]);
        }

        $builder->addModelTransformer($this->productsToProductAssociationsTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "compound" => true,
            "by_reference" => false,
            "allow_extra_fields" => true,
            "data-ea-autocomplete-endpoint-url" => '',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_product_associations';
    }
}
