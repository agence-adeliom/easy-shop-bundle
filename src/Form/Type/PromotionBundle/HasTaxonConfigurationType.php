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

use Adeliom\EasyFieldsBundle\Form\ChoiceMaskType;
use App\Entity\Shop\Product\Product;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Sylius\Bundle\PromotionBundle\Form\Type\PromotionRuleChoiceType;
use Sylius\Bundle\ResourceBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class HasTaxonConfigurationType extends AbstractType
{
    /** @var RepositoryInterface */
    private $taxonRepository;

    /** @var DataTransformerInterface */
    private $taxonsToCodesTransformer;

    public function __construct(RepositoryInterface $taxonRepository, DataTransformerInterface $taxonsToCodesTransformer)
    {
        $this->taxonRepository = $taxonRepository;
        $this->taxonsToCodesTransformer = $taxonsToCodesTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taxons', EntityType::class, [
                'label' => 'sylius.form.promotion_rule.has_taxon.taxons',
                'class' => $this->taxonRepository->getClassName(),
                'multiple' => true,
                'attr' => [
                    "data-ea-widget" => "ea-autocomplete"
                ],
                'choice_value' => function ($entity) {
                    return $entity ? $entity->getCode() : '';
                },
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']])
                ],
            ])
        ;
        $builder->get('taxons')->addModelTransformer($this->taxonsToCodesTransformer);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_promotion_rule_has_taxon_configuration';
    }
}
