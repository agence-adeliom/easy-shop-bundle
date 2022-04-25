<?php

namespace Adeliom\EasyShopBundle\Form\Type\ShippingBundle;

use Adeliom\EasyFieldsBundle\Form\ChoiceMaskType;
use App\Entity\Shop\Shipping\ShippingMethod;
use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Calculator\ChannelBasedFlatRateConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Calculator\ChannelBasedPerUnitRateConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\ChannelBasedOrderTotalGreaterThanOrEqualConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\ChannelBasedOrderTotalLessThanOrEqualConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\OrderTotalGreaterThanOrEqualConfigurationType;
use Sylius\Bundle\CoreBundle\Form\Type\Shipping\Rule\OrderTotalLessThanOrEqualConfigurationType;
use Sylius\Bundle\CoreBundle\Templating\Helper\PriceHelper;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Bundle\ShippingBundle\Form\Type\AbstractConfigurableShippingMethodElementType;
use Sylius\Bundle\ShippingBundle\Form\Type\Calculator\FlatRateConfigurationType;
use Sylius\Bundle\ShippingBundle\Form\Type\Calculator\PerUnitRateConfigurationType;
use Sylius\Bundle\ShippingBundle\Form\Type\Rule\TotalWeightGreaterThanOrEqualConfigurationType;
use Sylius\Bundle\ShippingBundle\Form\Type\Rule\TotalWeightLessThanOrEqualConfigurationType;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingMethodRuleChoiceType;
use Sylius\Component\Core\OrderProcessing\ShippingChargesProcessor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShippingMethodCalculatorType extends AbstractType
{
    /** @var array */
    private $calculators;

    /**
     * @param array $calculators
     */
    public function __construct($calculators)
    {
        $this->calculators = $calculators;
    }

    public function buildForm(FormBuilderInterface $builder, array $options = []): void
    {
        $builder
            ->add('type', ChoiceMaskType::class, [
                'choices' => array_flip($this->calculators),
                'label' => false,
                'attr' => [
                    'data-form-collection' => 'update',
                ],
                "map" => call_user_func(function () {
                    $map = [];
                    foreach ($this->calculators as $key => $calculator) {
                        $map[$key] = [$key];
                    }
                    return $map;
                })
            ])
            ->add("flat_rate", ChannelBasedFlatRateConfigurationType::class, ["label" => false])
            ->add("per_unit_rate", ChannelBasedPerUnitRateConfigurationType::class, ["label" => false])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $parent = $form->getParent();

            if($data) {
                /** @var ShippingMethod $shipping */
                $shipping = $parent->getData();
                $configurations = $shipping->getConfiguration();
                $newData = [
                    "type" => $data,
                    $data => $configurations
                ];
                $event->setData($newData);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $keys = array_diff(array_keys($data), ["type", $data["type"]]);

            foreach ($keys as $key){
                unset($data[$key]);
                $form->remove($key);
            }
            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            $keys = array_diff(array_keys($data), ["type", $data["type"]]);

            foreach ($keys as $key){
                unset($data[$key]);
                $form->remove($key);
            }

            $parent = $form->getParent();
            $event->setData($data["type"]);
            $shipping = $parent->getData();
            $shipping->setConfiguration($data[$data["type"]] ?? []);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault("compound", true);
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_shipping_method_rule';
    }
}
