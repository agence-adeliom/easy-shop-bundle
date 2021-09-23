<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;

abstract class CurrencyCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "currency";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.currencies")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_currency")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_currency")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.currency")
            ->setEntityLabelInSingular('sylius.ui.currency')
            ->setEntityLabelInPlural('sylius.ui.currencies')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield CurrencyField::new('code', "sylius.ui.code")->showName()->showCode()->showSymbol();
    }

}
