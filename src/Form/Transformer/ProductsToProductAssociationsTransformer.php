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

namespace Adeliom\EasyShopBundle\Form\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\DataTransformerInterface;

final class ProductsToProductAssociationsTransformer implements DataTransformerInterface
{
    /** @var FactoryInterface */
    private $productAssociationFactory;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var RepositoryInterface */
    private $productAssociationTypeRepository;

    /**
     * @var Collection|ProductAssociationInterface[]
     *
     * @psalm-var Collection<array-key, ProductAssociationInterface>
     */
    private $productAssociations;

    public function __construct(
        FactoryInterface $productAssociationFactory,
        ProductRepositoryInterface $productRepository,
        RepositoryInterface $productAssociationTypeRepository
    ) {
        $this->productAssociationFactory = $productAssociationFactory;
        $this->productRepository = $productRepository;
        $this->productAssociationTypeRepository = $productAssociationTypeRepository;
    }

    public function transform($productAssociations)
    {
        $this->setProductAssociations($productAssociations);

        if (null === $productAssociations) {
            return '';
        }

        $values = [];

        /** @var ProductAssociationInterface $productAssociation */
        foreach ($productAssociations as $productAssociation) {
            $values[$productAssociation->getType()->getCode()] = $productAssociation->getAssociatedProducts();
        }

        return $values;
    }

    public function reverseTransform($values): ?Collection
    {
        if (null === $values || '' === $values || !is_array($values)) {
            return null;
        }

        /**
         * @psalm-var Collection<array-key, ProductAssociationInterface> $productAssociations
         */
        $productAssociations = new ArrayCollection();

        foreach ($values as $productAssociationTypeCode => $productCodes) {
            if (null === $productCodes) {
                continue;
            }

            /** @var ProductAssociationInterface $productAssociation */
            $productAssociation = $this->getProductAssociationByTypeCode($productAssociationTypeCode);
            if($productCodes instanceof Collection){
                $productCodes = $productCodes->toArray();
            }
            $this->setAssociatedProductsByProductCodes($productAssociation, $productCodes);
            $productAssociations->add($productAssociation);
        }

        $this->setProductAssociations(null);
        return $productAssociations;
    }

    private function getCodesAsStringFromProducts(Collection $products): ?array
    {
        if ($products->isEmpty()) {
            return null;
        }

        $codes = [];

        /** @var ProductInterface $product */
        foreach ($products as $product) {
            $codes[] = $product->getCode();
        }

        return $codes;
    }

    private function getProductAssociationByTypeCode(string $productAssociationTypeCode): ProductAssociationInterface
    {
        foreach ($this->productAssociations as $productAssociation) {
            if ($productAssociationTypeCode === $productAssociation->getType()->getCode()) {
                return $productAssociation;
            }
        }

        /** @var ProductAssociationTypeInterface $productAssociationType */
        $productAssociationType = $this->productAssociationTypeRepository->findOneBy([
            'code' => $productAssociationTypeCode,
        ]);

        /** @var ProductAssociationInterface $productAssociation */
        $productAssociation = $this->productAssociationFactory->createNew();
        $productAssociation->setType($productAssociationType);

        return $productAssociation;
    }

    private function setAssociatedProductsByProductCodes(
        ProductAssociationInterface $productAssociation,
        array $productCodes
    ): void {
        $productAssociation->clearAssociatedProducts();
        foreach ($productCodes as $product) {
            $productAssociation->addAssociatedProduct($product);
        }
    }

    private function setProductAssociations(?Collection $productAssociations): void
    {
        $this->productAssociations = $productAssociations;
    }
}
