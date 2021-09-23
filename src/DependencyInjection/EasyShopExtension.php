<?php

namespace Adeliom\EasyShopBundle\DependencyInjection;

use Adeliom\EasyBlockBundle\Block\BlockInterface;
use Adeliom\EasyBlockBundle\DependencyInjection\Configuration;
use Adeliom\EasyShopBundle\Locale\LocaleSwitcherInterface;
use Sylius\Bundle\CoreBundle\Checkout\CheckoutRedirectListener;
use Sylius\Bundle\CoreBundle\Checkout\CheckoutResolver;
use Sylius\Bundle\CoreBundle\Checkout\CheckoutStateUrlGenerator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestMatcher;

class EasyShopExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');


        $loaderXML = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loaderXML->load('services.xml');

        $loaderXML->load(sprintf('services/integrations/locale/%s.xml', $config['locale_switcher']));

        $container->setAlias(LocaleSwitcherInterface::class, 'sylius.shop.locale_switcher');

        $container->setParameter('sylius_shop.firewall_context_name', $config['firewall_context_name']);
        $container->setParameter(
            'sylius_shop.product_grid.include_all_descendants',
            $config['product_grid']['include_all_descendants']
        );
        $this->configureCheckoutResolverIfNeeded($config['checkout_resolver'], $container);
    }


    private function configureCheckoutResolverIfNeeded(array $config, ContainerBuilder $container): void
    {
        if (!$config['enabled']) {
            return;
        }

        $checkoutResolverDefinition = new Definition(
            CheckoutResolver::class,
            [
                new Reference('sylius.context.cart'),
                new Reference('sylius.router.checkout_state'),
                new Definition(RequestMatcher::class, [$config['pattern']]),
                new Reference('sm.factory'),
            ]
        );
        $checkoutResolverDefinition->addTag('kernel.event_subscriber');

        $checkoutStateUrlGeneratorDefinition = new Definition(
            CheckoutStateUrlGenerator::class,
            [
                new Reference('router'),
                $config['route_map'],
            ]
        );

        $container->setDefinition('sylius.resolver.checkout', $checkoutResolverDefinition);
        $container->setDefinition('sylius.listener.checkout_redirect', $this->registerCheckoutRedirectListener($config));
        $container->setDefinition('sylius.router.checkout_state', $checkoutStateUrlGeneratorDefinition);
    }

    private function registerCheckoutRedirectListener(array $config): Definition
    {
        $checkoutRedirectListener = new Definition(CheckoutRedirectListener::class, [
            new Reference('request_stack'),
            new Reference('sylius.router.checkout_state'),
            new Definition(RequestMatcher::class, [$config['pattern']]),
        ]);

        $checkoutRedirectListener
            ->addTag('kernel.event_listener', [
                'event' => 'sylius.order.post_address',
                'method' => 'handleCheckoutRedirect',
            ])
            ->addTag('kernel.event_listener', [
                'event' => 'sylius.order.post_select_shipping',
                'method' => 'handleCheckoutRedirect',
            ])
            ->addTag('kernel.event_listener', [
                'event' => 'sylius.order.post_payment',
                'method' => 'handleCheckoutRedirect',
            ])
        ;

        return $checkoutRedirectListener;
    }


    public function getAlias()
    {
        return 'easy_shop';
    }
}
