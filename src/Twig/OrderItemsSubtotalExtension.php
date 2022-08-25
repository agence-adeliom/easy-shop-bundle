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

namespace Adeliom\EasyShopBundle\Twig;

use Adeliom\EasyShopBundle\Calculator\OrderItemsSubtotalCalculator;
use Adeliom\EasyShopBundle\Calculator\OrderItemsSubtotalCalculatorInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OrderItemsSubtotalExtension extends AbstractExtension
{
    private readonly \Adeliom\EasyShopBundle\Calculator\OrderItemsSubtotalCalculatorInterface $calculator;

    public function __construct(?OrderItemsSubtotalCalculatorInterface $calculator = null)
    {
        if (null === $calculator) {
            $calculator = new OrderItemsSubtotalCalculator();

            @trigger_error(
                'Not passing a calculator is deprecated since 1.6. Argument will no longer be optional from 2.0.',
                \E_USER_DEPRECATED
            );
        }

        $this->calculator = $calculator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sylius_order_items_subtotal', $this->getSubtotal(...)),
        ];
    }

    public function getSubtotal(OrderInterface $order): int
    {
        return $this->calculator->getSubtotal($order);
    }
}
