<?php

namespace Adeliom\EasyShopBundle\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

trait EasyShopDashboardTrait {

    public function getSyliusEntity(string $model){
        $parameterBag = $this->container->get("parameter_bag");
        return $parameterBag->get(sprintf("sylius.model.%s.class", $model));
    }

    public function productItems(): iterable
    {
        yield MenuItem::section('sylius.ui.products');
        yield MenuItem::linkToCrud('sylius.ui.taxons', 'fas fa-folder', $this->getSyliusEntity("taxon"));
        yield MenuItem::linkToCrud('sylius.ui.items', 'fas fa-cube', $this->getSyliusEntity("product"));
        yield MenuItem::linkToCrud('sylius.ui.inventory', 'fas fa-history', $this->getSyliusEntity("product_variant"));
        yield MenuItem::linkToCrud('sylius.ui.attributes', 'fas fa-cubes', $this->getSyliusEntity("product_attribute"));
        yield MenuItem::linkToCrud('sylius.ui.options', 'fas fa-sliders-h', $this->getSyliusEntity("product_option"));
        yield MenuItem::linkToCrud('sylius.ui.association_types', 'fas fa-tasks', $this->getSyliusEntity("product_association_type"));
    }

    public function marketingItems(): iterable
    {
        yield MenuItem::section('sylius.ui.marketing');
        yield MenuItem::linkToCrud('sylius.ui.promotions', 'fas fa-percentage', $this->getSyliusEntity("promotion"));
        yield MenuItem::linkToCrud('sylius.ui.product_reviews', 'fas fa-newspaper', $this->getSyliusEntity("product_review"));
    }

    public function salesItems(): iterable
    {
        yield MenuItem::section('sylius.ui.sales');
        yield MenuItem::linkToCrud('sylius.ui.orders', 'fas fa-shopping-cart', $this->getSyliusEntity("order"));
        yield MenuItem::linkToCrud('sylius.ui.payments', 'fas fa-credit-card', $this->getSyliusEntity("payment"));
        yield MenuItem::linkToCrud('sylius.ui.shipments', 'fas fa-truck', $this->getSyliusEntity("shipment"));
        yield MenuItem::linkToCrud('sylius.ui.orders_statistics', 'far fa-chart-bar', $this->getSyliusEntity("order"))->setAction("statistics");
    }

    public function customerItems(): iterable
    {
        yield MenuItem::section('sylius.ui.customer');
        yield MenuItem::linkToCrud('sylius.ui.customers', 'fas fa-users', $this->getSyliusEntity("customer"));
        yield MenuItem::linkToCrud('sylius.form.user.groups', 'fas fa-archive', $this->getSyliusEntity("customer_group"));
    }

    public function configurationItems(): iterable
    {
        yield MenuItem::section('sylius.ui.configuration');
        yield MenuItem::linkToCrud('sylius.ui.channels', 'fas fa-random', $this->getSyliusEntity("channel"));
        yield MenuItem::linkToCrud('sylius.ui.countries', 'fas fa-flag', $this->getSyliusEntity("country"));
        yield MenuItem::linkToCrud('sylius.ui.zones', 'fas fa-globe', $this->getSyliusEntity("zone"));
        yield MenuItem::linkToCrud('sylius.ui.currencies', 'fas fa-dollar-sign', $this->getSyliusEntity("currency"));
        yield MenuItem::linkToCrud('sylius.ui.exchange_rates', 'fas fa-exchange-alt', $this->getSyliusEntity("exchange_rate"));
        yield MenuItem::linkToCrud('sylius.ui.locales', 'fas fa-language', $this->getSyliusEntity("locale"));
        yield MenuItem::linkToCrud('sylius.ui.payment_methods', 'fas fa-credit-card', $this->getSyliusEntity("payment_method"));
        yield MenuItem::linkToCrud('sylius.ui.shipping_methods', 'fas fa-truck', $this->getSyliusEntity("shipping_method"));
        yield MenuItem::linkToCrud('sylius.ui.shipping_categories', 'fas fa-th-list', $this->getSyliusEntity("shipping_category"));
        yield MenuItem::linkToCrud('sylius.ui.tax_categories', 'fas fa-tags', $this->getSyliusEntity("tax_category"));
        yield MenuItem::linkToCrud('sylius.ui.tax_rates', 'fas fa-money-bill', $this->getSyliusEntity("tax_rate"));
        yield MenuItem::linkToCrud('sylius.ui.api_users', 'fas fa-user-astronaut', $this->getSyliusEntity("admin_user"));
    }

}
