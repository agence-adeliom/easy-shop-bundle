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
use App\Entity\Shop\Promotion\PromotionRule;
use App\Entity\Shop\Shipping\ShippingMethod;
use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Bundle\CoreBundle\Form\Type\Promotion\Rule\ChannelBasedTotalOfItemsFromTaxonConfigurationType;
use Sylius\Bundle\PromotionBundle\Form\Type\PromotionRuleChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class PromotionRuleType extends AbstractType
{
    public function __construct(private readonly array $rules, private readonly FormTypeRegistryInterface $formTypeRegistry, private readonly string $entityClass)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $map = [];
        foreach (array_keys($this->rules) as $k) {
            $map[$k] = [$k];
        }

        $builder
            ->add('type', ChoiceMaskType::class, [
                'label' => 'sylius.form.promotion_rule.type',
                'choices' => array_flip($this->rules),
                'map' => $map,
            ])
        ;
        foreach (array_keys($this->rules) as $form) {
            $ruleOptions = [
                "label" => false,
                'constraints' => [
                    new Valid()
                ]
            ];
            if ($form == "contains_product") {
                $builder->add($form, \Adeliom\EasyShopBundle\Form\Type\PromotionBundle\ContainsProductConfigurationType::class, $ruleOptions);
            } elseif ($form == "has_taxon") {
                $builder->add($form, \Adeliom\EasyShopBundle\Form\Type\PromotionBundle\HasTaxonConfigurationType::class, $ruleOptions);
            } elseif ($form == "total_of_items_from_taxon") {
                $builder->add($form, \Adeliom\EasyShopBundle\Form\Type\PromotionBundle\ChannelBasedTotalOfItemsFromTaxonConfigurationType::class, $ruleOptions);
            } else {
                $builder->add($form, $this->formTypeRegistry->get($form, "default"), $ruleOptions);
            }
        }

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($value) {
                if ($value) {
                    return [
                        'type' => $value->getType(),
                        $value->getType() => $value->getConfiguration()
                    ];
                }

                return $value;
            },
            function ($value) {
                if ($value) {
                    $obj = new $this->entityClass();
                    $obj->setType($value['type']);
                    $obj->setConfiguration($value['configuration']);
                    return $obj;
                }

                return $value;
            }
        ));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            foreach (array_keys($this->rules) as $rule) {
                $form->remove($rule);
            }

            $data = [
                "type" => $data["type"],
                "configuration" => $data[$data["type"]] ?? $data["configuration"] ?? [],
            ];
            $event->setData($data);

            $form->add('type_view', ChoiceType::class, [
                'label' => 'sylius.form.promotion_action.type',
                'choices' => array_flip($this->rules),
                'data' => $data["type"],
                'disabled' => true,
                'mapped' => false
            ]);
            $form->add('type', HiddenType::class);

            $ruleOptions = [
                "label" => false,
                'constraints' => [
                    new Valid()
                ]
            ];
            if ($data["type"] == "contains_product") {
                $form->add('configuration', \Adeliom\EasyShopBundle\Form\Type\PromotionBundle\ContainsProductConfigurationType::class, $ruleOptions);
            } elseif ($data["type"] == "has_taxon") {
                $form->add('configuration', \Adeliom\EasyShopBundle\Form\Type\PromotionBundle\HasTaxonConfigurationType::class, $ruleOptions);
            } elseif ($data["type"] == "total_of_items_from_taxon") {
                $form->add('configuration', \Adeliom\EasyShopBundle\Form\Type\PromotionBundle\ChannelBasedTotalOfItemsFromTaxonConfigurationType::class, $ruleOptions);
            } else {
                $form->add('configuration', $this->formTypeRegistry->get($data["type"], "default"), $ruleOptions);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault("allow_extra_fields", true);
        $resolver->setDefault("constraints", [
            new Valid()
        ]);
    }
}
