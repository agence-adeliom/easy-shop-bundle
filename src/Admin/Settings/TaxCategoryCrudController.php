<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

abstract class TaxCategoryCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "tax_category";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_tax_categories")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_tax_category")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_tax_category")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.tax_category")
            ->setEntityLabelInSingular('sylius.ui.tax_category')
            ->setEntityLabelInPlural('sylius.ui.tax_categories')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code', 'sylius.ui.code')->setColumns(6)->setRequired(true)
            ->setFormTypeOption('disabled', ($pageName == Crud::PAGE_EDIT ? 'disabled' : ''));
        yield TextField::new('name', 'sylius.form.tax_category.name')->setColumns(6)->setRequired(true);
        yield TextareaField::new('description', 'sylius.form.tax_category.description')->hideOnIndex();
    }
}
