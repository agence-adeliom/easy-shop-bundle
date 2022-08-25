<?php

namespace Adeliom\EasyShopBundle\Form\Type\ProductBundle;

use Adeliom\EasyMediaBundle\Form\EasyMediaType;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductImageType extends AbstractType
{
    public function __construct(protected ParameterBag $parameterBag)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('path', EasyMediaType::class, [
                'label' => 'sylius.form.image.file',
                'required' => true,
                "restrictions_uploadTypes" => ["image/*"],
            ]);

        $class = $this->parameterBag->get("sylius.model.product_image.class");
        if (isset($options['product']) && $options['product'] instanceof ProductInterface) {
            $builder
                ->add('productVariants', ChoiceType::class, [
                    'label' => 'sylius.ui.product_variants',
                    'multiple' => true,
                    'required' => false,
                    'choice_label' => static fn($v) => $v->getDescriptor(),
                    'choice_value' => static fn($v) => $v->getCode(),
                    'attr' => [
                        'data-ea-widget' => "ea-autocomplete"
                    ],
                    'choices' => $options['product']->getVariants()
                ])
            ;
        }

        $builder->add('type', ChoiceType::class, [
            'label' => 'sylius.form.image.type',
            'required' => false,
            'choices' => [
                "Miniature" => 'thumbnail',
                "Principale" => 'main',
            ]
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($value) {
                if ($value) {
                    return [
                        "type" => $value->getType(),
                        "path" => $value->getPath(),
                        "productVariants" => $value->getProductVariants()->toArray(),
                    ];
                }

                return $value;
            },
            static function ($value) use ($class) {
                if ($value) {
                    $image = new $class();
                    $image->setType($value["type"]);
                    $image->setPath($value["path"]);
                    if (isset($value["productVariants"])) {
                        foreach ($value["productVariants"] as $variant) {
                            $image->addProductVariant($variant);
                        }
                    }

                    return $image;
                }

                return $value;
            }
        ));
    }

    /**
     * @psalm-suppress MissingPropertyType
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['product'] = $options['product'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('product');
        $resolver->setAllowedTypes('product', ProductInterface::class);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_product_image';
    }
}
