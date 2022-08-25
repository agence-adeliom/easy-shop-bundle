<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use Sylius\Bundle\AddressingBundle\Form\Type\ProvinceType;

abstract class CountryCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "country";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_countries")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_country")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_country")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.country_details")
            ->setEntityLabelInSingular('sylius.ui.country')
            ->setEntityLabelInPlural('sylius.ui.countries')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield CountryField::new('code', "sylius.ui.code")->setRequired(true);
        yield BooleanField::new('enabled', "sylius.ui.enabled")->renderAsSwitch(in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT]));
        yield CollectionField::new("provinces", "sylius.form.country.provinces")->hideOnIndex()->setEntryType(ProvinceType::class);
    }
}
