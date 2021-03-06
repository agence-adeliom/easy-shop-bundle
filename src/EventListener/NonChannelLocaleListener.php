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

use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class NonChannelLocaleListener
{
    /** @var RouterInterface */
    private $router;

    /** @var LocaleProviderInterface */
    private $channelBasedLocaleProvider;

    /** @var FirewallMap */
    private $firewallMap;

    /** @var string[] */
    private $firewallNames;

    /**
     * @param string[] $firewallNames
     */
    public function __construct(
        RouterInterface $router,
        LocaleProviderInterface $channelBasedLocaleProvider,
        FirewallMap $firewallMap,
        array $firewallNames
    ) {
        Assert::notEmpty($firewallNames);
        Assert::allString($firewallNames);

        $this->channelBasedLocaleProvider = $channelBasedLocaleProvider;
        $this->firewallMap = $firewallMap;
        $this->firewallNames = $firewallNames;
        $this->router = $router;
    }

    /**
     * @throws NotFoundHttpException
     */
    public function restrictRequestLocale(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        /** @psalm-suppress RedundantConditionGivenDocblockType Symfony docblock is not always true */
        if ($request->attributes && in_array($request->attributes->get('_route'), ['_wdt', '_profiler', '_profiler_search', '_profiler_search_results'])) {
            return;
        }

        $currentFirewall = $this->firewallMap->getFirewallConfig($request);
        if (!$this->isFirewallSupported($currentFirewall)) {
            return;
        }

        $requestLocale = $request->getLocale();
        if (!in_array($requestLocale, $this->channelBasedLocaleProvider->getAvailableLocalesCodes(), true)) {
            $event->setResponse(
                new RedirectResponse(
                    $this->router->generate(
                        'sylius_shop_homepage',
                        ['_locale' => $this->channelBasedLocaleProvider->getDefaultLocaleCode()]
                    )
                )
            );
        }
    }

    private function isFirewallSupported(?FirewallConfig $firewall = null): bool
    {
        return
            null !== $firewall &&
            in_array($firewall->getName(), $this->firewallNames)
        ;
    }
}
