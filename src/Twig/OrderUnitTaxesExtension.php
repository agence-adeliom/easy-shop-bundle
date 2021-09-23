<?php

namespace Adeliom\EasyShopBundle\Twig;

use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Model\AdjustmentInterface as BaseAdjustmentInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OrderUnitTaxesExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sylius_admin_order_unit_tax_included', [$this, 'getIncludedTax']),
            new TwigFunction('sylius_admin_order_unit_tax_excluded', [$this, 'getExcludedTax']),
        ];
    }

    public function getIncludedTax(OrderItemInterface $orderItemUnit): int
    {
        return $this->getAmount($orderItemUnit, true);
    }

    public function getExcludedTax(OrderItemInterface $orderItemUnit): int
    {
        return $this->getAmount($orderItemUnit, false);
    }

    private function getAmount(OrderItemInterface $orderItem, bool $neutral): int
    {
        $total = array_reduce(
            $orderItem->getAdjustmentsRecursively(AdjustmentInterface::TAX_ADJUSTMENT)->toArray(),
            static function (int $total, BaseAdjustmentInterface $adjustment) use ($neutral) {
                return $neutral === $adjustment->isNeutral() ? $total + $adjustment->getAmount() : $total;
            },
            0
        );

        return $total;
    }
}
