<?php

namespace Adeliom\EasyShopBundle\Router;

use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class LocaleStrippingRouter implements RouterInterface, WarmableInterface, RequestMatcherInterface
{
    /** @var Router */
    private $router;

    /** @var LocaleContextInterface */
    private $localeContext;

    public function __construct(RouterInterface $router, LocaleContextInterface $localeContext)
    {
        $this->router = $router;
        $this->localeContext = $localeContext;
    }

    public function match($pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $url = $this->router->generate($name, $parameters, $referenceType);

        if (false === strpos($url, '_locale')) {
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

    public function warmUp($cacheDir): void
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
