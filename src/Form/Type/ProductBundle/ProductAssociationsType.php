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
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductAssociationsType extends AbstractType
{
    /** @var RepositoryInterface */
    private $productAssociationTypeRepository;

    /** @var RepositoryInterface */
    private $productRepository;

    /** @var DataTransformerInterface */
    private $productsToProductAssociationsTransformer;

    public function __construct(
        RepositoryInterface $productAssociationTypeRepository,
        RepositoryInterface $productRepository,
        DataTransformerInterface $productsToProductAssociationsTransformer
    ) {
        $this->productAssociationTypeRepository = $productAssociationTypeRepository;
        $this->productRepository = $productRepository;
        $this->productsToProductAssociationsTransformer = $productsToProductAssociationsTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $assoc = $this->productAssociationTypeRepository->findAll();
        foreach ($assoc as $item){
            $code = $item->getCode();
            $builder->add($code, EntityType::class, [
                'property_path' => "[$code]",
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
