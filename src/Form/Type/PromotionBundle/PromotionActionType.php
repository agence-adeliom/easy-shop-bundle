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
use App\Entity\Shop\Promotion\PromotionAction;
use App\Entity\Shop\Promotion\PromotionRule;
use App\Entity\Shop\Shipping\ShippingMethod;
use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Bundle\PromotionBundle\Form\Type\PromotionRuleChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class PromotionActionType extends AbstractType
{
    /** @var FormTypeRegistryInterface */
    private $formTypeRegistry;

    /** @var array */
    private $actions;

    private $entityClass;

    public function __construct(array $actions, FormTypeRegistryInterface $formTypeRegistry, string $entityClass)
    {
        $this->formTypeRegistry = $formTypeRegistry;
        $this->actions = $actions;
        $this->entityClass = $entityClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $map = [];
        foreach ($this->actions as $k => $v){
            $map[$k] = [$k];
        }

        $builder
            ->add('type', ChoiceMaskType::class, [
                'label' => 'sylius.form.promotion_action.type',
                'choices' => array_flip($this->actions),
                'map' => $map,
            ])
        ;
        foreach ($this->actions as $form => $label){
            $actionOptions = [
                "label" => false,
                //"mapped" => false,
                'constraints' => [
                    new Valid()
                ]
            ];
            if($form == 'unit_fixed_discount'){
                $builder->add($form, ChannelBasedUnitFixedDiscountConfigurationType::class, $actionOptions);
            }elseif($form == 'unit_percentage_discount'){
                $builder->add($form, ChannelBasedUnitPercentageDiscountConfigurationType::class, $actionOptions);
            }else{
                $builder->add($form, $this->formTypeRegistry->get($form, "default"), $actionOptions);
            }
        }

        $builder->addModelTransformer(new CallbackTransformer(
            function ($value){
                if ($value){
                    return [
                        'type' => $value->getType(),
                        $value->getType() => $value->getConfiguration()
                    ];
                }
                return $value;
            },
            function ($value){
                if ($value){
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

            foreach (array_keys($this->actions) as $rule){
                $form->remove($rule);
            }

            $data = [
                "type" => $data["type"],
                "configuration" => isset($data[$data["type"]]) ? $data[$data["type"]] : (isset($data["configuration"]) ? $data["configuration"] : []),
            ];
            $event->setData($data);

            $form->add('type_view', ChoiceType::class, [
                'label' => 'sylius.form.promotion_action.type',
                'choices' => array_flip($this->actions),
                'disabled' => true,
                'mapped' => false
            ]);
            $form->add('type', HiddenType::class);

            $actionOptions = [
                "label" => false,
                'constraints' => [
                    new Valid()
                ]
            ];
            if($data["type"] == 'unit_fixed_discount'){
                $form->add("configuration", ChannelBasedUnitFixedDiscountConfigurationType::class, $actionOptions);
            }elseif($data["type"] == 'unit_percentage_discount'){
                $form->add("configuration", ChannelBasedUnitPercentageDiscountConfigurationType::class, $actionOptions);
            }else{
                $form->add("configuration", $this->formTypeRegistry->get($data["type"], "default"), $actionOptions);
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
