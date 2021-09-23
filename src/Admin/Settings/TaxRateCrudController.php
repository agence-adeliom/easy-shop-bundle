<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sylius\Bundle\AddressingBundle\Form\Type\ZoneChoiceType;
use Sylius\Bundle\TaxationBundle\Form\Type\TaxCalculatorChoiceType;
use Sylius\Bundle\TaxationBundle\Form\Type\TaxCategoryChoiceType;
use Sylius\Component\Core\Model\Scope;

abstract class TaxRateCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "tax_rate";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_tax_rates")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_tax_rate")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_tax_rate")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.tax_rate_details")
            ->setEntityLabelInSingular('sylius.ui.tax_rates')
            ->setEntityLabelInPlural('sylius.ui.tax_rates')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel("sylius.ui.general_info")->collapsible()->renderCollapsed(false);

        yield TextField::new('code', 'sylius.ui.code')
            ->setFormTypeOption('disabled', (in_array($pageName, [Crud::PAGE_EDIT]) ? 'disabled' : ''))
            ->setColumns(6);

        yield TextField::new('name', 'sylius.form.tax_rate.name')
            ->setColumns(6);

        yield FormField::addPanel("sylius.ui.criteria")->collapsible()->renderCollapsed(false);

        yield FormTypeField::new('category', 'sylius.form.tax_rate.category', TaxCategoryChoiceType::class)
            ->setFormTypeOption("attr", ["data-ea-widget" => "ea-autocomplete"])
            ->setColumns(6)
            ->setRequired(true);

        yield FormTypeField::new('zone', 'sylius.form.address.zone', ZoneChoiceType::class)
            ->setFormTypeOptions(['zone_scope' => Scope::TAX, "attr" => ["data-ea-widget" => "ea-autocomplete"]])
            ->setColumns(6)
            ->setRequired(true);

        yield FormField::addPanel("sylius.ui.taxes")->collapsible()->renderCollapsed(false);

        yield FormTypeField::new('calculator', 'sylius.form.tax_rate.calculator', TaxCalculatorChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("attr", ["data-ea-widget" => "ea-autocomplete"]);

        yield PercentField::new('amount', 'sylius.form.tax_rate.amount')
            ->setFormTypeOption("scale", 3);

        yield BooleanField::new('includedInPrice',"sylius.form.tax_rate.included_in_price")->hideOnIndex();
    }

}
