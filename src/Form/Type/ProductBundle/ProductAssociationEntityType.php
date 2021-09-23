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
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductAssociationEntityType extends AbstractType
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
        $builder->addModelTransformer($this->productsToProductAssociationsTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'by_reference' => false,
            'class' => $this->productRepository->getClassName()
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
