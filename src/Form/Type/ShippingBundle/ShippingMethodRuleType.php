<?php

namespace Adeliom\EasyShopBundle\Form\Type\ShippingBundle;

use Adeliom\EasyFieldsBundle\Form\ChoiceMaskType;
use App\Entity\Shop\Shipping\ShippingMethod;
use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Calculator\ChannelBasedFlatRateConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\ChannelBasedOrderTotalGreaterThanOrEqualConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\ChannelBasedOrderTotalLessThanOrEqualConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\OrderTotalGreaterThanOrEqualConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\OrderTotalLessThanOrEqualConfigurationType;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Bundle\ShippingBundle\Form\Type\AbstractConfigurableShippingMethodElementType;
use Sylius\Bundle\ShippingBundle\Form\Type\Calculator\FlatRateConfigurationType;
use Sylius\Bundle\ShippingBundle\Form\Type\Rule\TotalWeightGreaterThanOrEqualConfigurationType;
use Sylius\Bundle\ShippingBundle\Form\Type\Rule\TotalWeightLessThanOrEqualConfigurationType;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingMethodRuleChoiceType;
use Sylius\Component\Shipping\Model\ShippingMethodRule;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingMethodRuleType extends AbstractConfigurableShippingMethodElementType
{
    protected $rules;
    public function __construct(string $dataClass, array $validationGroups, FormTypeRegistryInterface $formTypeRegistry, $rules)
    {
        parent::__construct($dataClass, $validationGroups, $formTypeRegistry);
        $this->rules = $rules;
    }

    public function buildForm(FormBuilderInterface $builder, array $options = []): void
    {
        $rules = array_keys($this->rules);

        parent::buildForm($builder, $options);
        $builder
            ->add('type', ChoiceMaskType::class, [
                'choices' => array_flip($this->rules),
                'label' => 'sylius.form.shipping_method_rule.type',
                'attr' => [
                    'data-form-collection' => 'update',
                ],
                "map" => [
                    "total_weight_greater_than_or_equal" => ["total_weight_greater_than_or_equal"],
                    "total_weight_less_than_or_equal" => ["total_weight_less_than_or_equal"],
                    "order_total_greater_than_or_equal" => ["order_total_greater_than_or_equal"],
                    "order_total_less_than_or_equal" => ["order_total_less_than_or_equal"]
                ]
            ])
            ->add("total_weight_greater_than_or_equal", TotalWeightGreaterThanOrEqualConfigurationType::class, ["label" => false, "mapped" => false])
            ->add("total_weight_less_than_or_equal", TotalWeightLessThanOrEqualConfigurationType::class, ["label" => false, "mapped" => false])
            ->add("order_total_greater_than_or_equal", ChannelBasedOrderTotalGreaterThanOrEqualConfigurationType::class, ["label" => false, "mapped" => false])
            ->add("order_total_less_than_or_equal", ChannelBasedOrderTotalLessThanOrEqualConfigurationType::class, ["label" => false, "mapped" => false])
        ;




        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $parent = $form->getParent();

            if($parent && $data) {
                /** @var ShippingMethodRule $rule */
                $rule = $data;
                $form->remove("configuration");
                $form->get($rule->getType())->setData($rule->getConfiguration());
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($rules) {
            $data = $event->getData();
            $form = $event->getForm();
            $parent = $form->getParent();
            $data["configuration"] = $data[$data["type"]];

            unset($rules[$data["type"]]);
            foreach ($rules as $rule){
                $form->remove($rule);
                unset($data[$rule]);
            }
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault("allow_extra_fields", true);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_shipping_method_rule';
    }
}
