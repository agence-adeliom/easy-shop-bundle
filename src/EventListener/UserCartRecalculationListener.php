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

namespace Adeliom\EasyShopBundle\EventListener;

use Adeliom\EasyShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Webmozart\Assert\Assert;

final class UserCartRecalculationListener
{
    public function __construct(private readonly CartContextInterface $cartContext, private readonly OrderProcessorInterface $orderProcessor, private readonly SectionProviderInterface $uriBasedSectionContext)
    {
    }

    public function recalculateCartWhileLogin(\Symfony\Component\Security\Http\Event\InteractiveLoginEvent|\UserEvent $event): void
    {
        if (!$this->uriBasedSectionContext->getSection() instanceof ShopSection) {
            return;
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (!$event instanceof InteractiveLoginEvent && !$event instanceof UserEvent) {
            throw new \TypeError(sprintf(
                '$event needs to be an instance of "%s" or "%s"',
                InteractiveLoginEvent::class,
                UserEvent::class
            ));
        }

        try {
            $cart = $this->cartContext->getCart();
        } catch (CartNotFoundException) {
            return;
        }

        Assert::isInstanceOf($cart, OrderInterface::class);

        $this->orderProcessor->process($cart);
    }
}
