<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

abstract class ShippingCategoryCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "shipping_category";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_shipping_categories")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_shipping_category")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_shipping_category")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.shipping_category")
            ->setEntityLabelInSingular('sylius.ui.shipping_category')
            ->setEntityLabelInPlural('sylius.ui.shipping_categories')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code', "sylius.ui.code")->setColumns(6)->setRequired(true)
            ->setFormTypeOption('disabled', (in_array($pageName, [Crud::PAGE_EDIT]) ? 'disabled' : ''));
        yield TextField::new('name', 'sylius.form.shipping_category.name')->setColumns(6)->setRequired(true);
        yield TextareaField::new('description', 'sylius.form.shipping_category.description')->hideOnIndex();
    }

}
