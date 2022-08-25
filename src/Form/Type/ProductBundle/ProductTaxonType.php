<?php

namespace Adeliom\EasyShopBundle\Form\Type\ProductBundle;

use Sylius\Bundle\CoreBundle\Form\DataTransformer\ProductTaxonToTaxonTransformer;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\RecursiveTransformer;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceAutocompleteChoiceType;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductTaxonType extends AbstractType
{
    public function __construct(private readonly FactoryInterface $productTaxonFactory, private readonly RepositoryInterface $productTaxonRepository, private readonly RepositoryInterface $taxonRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple']) {
            $builder->addModelTransformer(
                new RecursiveTransformer(
                    new ProductTaxonToTaxonTransformer(
                        $this->productTaxonFactory,
                        $this->productTaxonRepository,
                        $options['product']
                    )
                )
            );
        }

        if (!$options['multiple']) {
            $builder->addModelTransformer(
                new ProductTaxonToTaxonTransformer(
                    $this->productTaxonFactory,
                    $this->productTaxonRepository,
                    $options['product']
                )
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => $this->taxonRepository->getClassName(),
            'choice_name' => 'name',
            'choice_label' => static fn($item) => $item->getTree(" / ", true),
            'choice_value' => 'code',
        ]);

        $resolver
            ->setRequired('product')
            ->setAllowedTypes('product', ProductInterface::class)
        ;
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_product_taxon_autocomplete_choice';
    }
}
