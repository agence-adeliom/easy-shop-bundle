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

use Sylius\Bundle\CoreBundle\Form\Type\Product\ChannelPricingType;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChannelCollectionType extends AbstractType
{
    public function __construct(private readonly ChannelRepositoryInterface $channelRepository)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entries' => $this->channelRepository->findAll(),
            'entry_name' => static fn(ChannelInterface $channel) => $channel->getCode(),
            'error_bubbling' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $productVariant = $event->getForm()->getParent()->getViewData()->getVariants()[0];
            $event->getForm()->add('channelPricings', \Sylius\Bundle\CoreBundle\Form\Type\ChannelCollectionType::class, [
                'entry_type' => ChannelPricingType::class,
                'entry_options' => static fn(ChannelInterface $channel) => [
                    'channel' => $channel,
                    'product_variant' => $productVariant,
                    'required' => false,
                ],
                'label' => null,
            ]);
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $event->setData($event->getData()['channelPricings']);
        });
    }
}
