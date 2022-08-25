<?php

namespace Adeliom\EasyShopBundle\Router;

use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class LocaleStrippingRouter implements RouterInterface, WarmableInterface, RequestMatcherInterface
{
    public function __construct(private readonly RouterInterface $router, private readonly LocaleContextInterface $localeContext)
    {
    }

    public function match($pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $url = $this->router->generate($name, $parameters, $referenceType);

        if (!str_contains($url, '_locale')) {
            return $url;
        }

        return $this->removeUnusedQueryArgument($url, '_locale', $this->localeContext->getLocaleCode());
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    public function warmUp($cacheDir): array
    {
        if ($this->router instanceof WarmableInterface) {
            $this->router->warmUp($cacheDir);
        }
    }

    private function removeUnusedQueryArgument(string $url, string $key, string $value): string
    {
        $replace = [
            sprintf('&%s=%s', $key, $value) => '',
            sprintf('?%s=%s&', $key, $value) => '?',
            sprintf('?%s=%s', $key, $value) => '',
        ];

        return str_replace(array_keys($replace), $replace, $url);
    }

    public function matchRequest(Request $request): array
    {
        return $this->router->matchRequest($request);
    }
}
