<?php

namespace Adeliom\EasyShopBundle\Form\Type\PromotionBundle\Filter;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

final class TaxonFilterConfigurationType extends AbstractType
{
    /** @var DataTransformerInterface */
    private $taxonsToCodesTransformer;

    private $model;

    public function __construct(DataTransformerInterface $taxonsToCodesTransformer, string $model)
    {
        $this->taxonsToCodesTransformer = $taxonsToCodesTransformer;
        $this->model = $model;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taxons', EntityType::class, [
                'class' => $this->model,
                'label' => 'sylius.form.promotion_filter.taxons',
                'multiple' => true,
                'required' => false,
                'choice_name' => 'fullname',
                'choice_label' => function ($item) {
                    return $item->getTree(" / ", true);
                },
                'choice_value' => 'code',
                'attr' => [
                    'data-ea-widget' => "ea-autocomplete"
                ]
            ])
        ;

        $builder->get('taxons')->addModelTransformer($this->taxonsToCodesTransformer);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_promotion_action_filter_taxon_configuration';
    }
}
