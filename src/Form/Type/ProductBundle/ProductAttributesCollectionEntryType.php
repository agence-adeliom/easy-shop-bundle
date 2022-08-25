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

use Adeliom\EasyShopBundle\Form\Type\TranslationsFormsType;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\FixedCollectionType;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductAttributesCollectionEntryType extends AbstractType
{
    public function __construct(private readonly FormTypeRegistryInterface $formTypeRegistry, private readonly RepositoryInterface $localeRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductAttributeInterface $attribute */
        $attribute = $options['attribute'];
        $builder
            ->add('attribute', HiddenType::class, ["data" => $attribute->getCode()])
            ->add('position', HiddenType::class)
        ;
        $required = true;
        if ($attribute->isTranslatable()) {
            foreach ($this->localeRepository->findAll() as $locale) {
                $builder->add("value__" . $locale->getCode(), $this->formTypeRegistry->get($attribute->getType(), "default"), [
                    'label' => $locale->getName(),
                    'required' => $required,
                    'auto_initialize' => false,
                    'locale_code' => $locale->getCode(),
                    'configuration' => $attribute->getConfiguration(),
                ]);
                $required = false;
            }
        } else {
            $builder->add("value", $this->formTypeRegistry->get($attribute->getType(), "default"), [
                'label' => $attribute->getName(),
                'required' => $required,
                'auto_initialize' => false,
                'configuration' => $attribute->getConfiguration(),
            ]);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attribute'] = $options['attribute'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // this defines the available options and their default values when
        // they are not configured explicitly when using the form type
        $resolver->setDefaults([
            'compound' => true,
            'attribute' => null,
            'allow_extra_fields' => true,
        ]);

        // optionally you can also restrict the options type or types (to get
        // automatic type validation and useful error messages for end users)
        $resolver->setAllowedTypes('attribute', ProductAttributeInterface::class);
    }
}
