<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use App\Entity\Shop\Locale\Locale;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;

abstract class LocaleCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "locale";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.locales")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_locale")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_locale")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.locale")
            ->setEntityLabelInSingular('sylius.ui.locale')
            ->setEntityLabelInPlural('sylius.ui.locales')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        yield LocaleField::new('code')->showName()->showCode();
    }
}
