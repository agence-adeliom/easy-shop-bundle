<?php

namespace Adeliom\EasyShopBundle\Form\Type\PromotionBundle;

use Sylius\Bundle\CoreBundle\Form\Type\ChannelCollectionType;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

final class ChannelBasedUnitFixedDiscountConfigurationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => UnitFixedDiscountConfigurationType::class,
            'entry_options' => static fn(ChannelInterface $channel) => [
                'label' => $channel->getName(),
                'currency' => $channel->getBaseCurrency()->getCode(),
            ],
            'constraints' => [
                new Valid()
            ]
        ]);
    }

    public function getParent(): string
    {
        return ChannelCollectionType::class;
    }
}
