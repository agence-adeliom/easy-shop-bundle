<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyFieldsBundle\Admin\Field\AssociationField;
use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sylius\Bundle\AddressingBundle\Form\Type\CountryChoiceType;
use Sylius\Bundle\AddressingBundle\Form\Type\ZoneChoiceType;
use Sylius\Bundle\CoreBundle\Form\Type\ShopBillingDataType;
use Sylius\Bundle\CoreBundle\Form\Type\TaxCalculationStrategyChoiceType;
use Sylius\Bundle\CurrencyBundle\Form\Type\CurrencyChoiceType;
use Sylius\Bundle\LocaleBundle\Form\Type\LocaleChoiceType;
use Sylius\Bundle\ThemeBundle\Form\Type\ThemeNameChoiceType;
use Sylius\Component\Core\Model\Scope;

abstract class ChannelCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "channel";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->addFormTheme('@EasyEditor/form/editor_widget.html.twig')
            ->addFormTheme('@EasyFields/form/association_widget.html.twig')
            ->addFormTheme('@EasyFields/form/sortable_widget.html.twig')
            ->addFormTheme('@EasyFields/form/choice_mask_widget.html.twig')
            ->addFormTheme('@EasyFields/form/translations_widget.html.twig')
            ->addFormTheme('@EasyMedia/form/easy-media.html.twig')

            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_channels")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_channel")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_channel")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.channel")
            ->setEntityLabelInSingular('sylius.ui.channel')
            ->setEntityLabelInPlural('sylius.ui.channels')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code', 'sylius.ui.code')->setColumns(6)->setFormTypeOption('disabled', (in_array($pageName, [Crud::PAGE_EDIT]) ? 'disabled' : ''));
        yield TextField::new('name', 'sylius.form.channel.name')->setColumns(6);
        yield TextareaField::new('description', 'sylius.form.channel.description')->setColumns(12)
            ->hideOnIndex();
        yield BooleanField::new('enabled', 'sylius.form.channel.enabled')->setColumns(4)->renderAsSwitch(in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT]));
        yield ColorField::new('color', 'sylius.form.channel.color')->setColumns(4)->hideOnIndex();
        yield TextField::new('hostname', 'sylius.form.channel.hostname')->setColumns(4)->hideOnIndex();


        yield FormTypeField::new('locales', 'sylius.form.channel.locales', LocaleChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("required", false)
            ->setFormTypeOption("multiple", true)
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(6);

        yield FormTypeField::new('defaultLocale', 'sylius.form.channel.locale_default', LocaleChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("required", true)
            ->setFormTypeOption("placeholder", null)
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(6)
            ->hideOnIndex();

        yield FormTypeField::new('currencies', 'sylius.form.channel.currencies', CurrencyChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("required", false)
            ->setFormTypeOption("multiple", true)
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(6)
            ->hideOnIndex();

        yield FormTypeField::new('baseCurrency', 'sylius.form.channel.currency_base', CurrencyChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("required", true)
            ->setFormTypeOption("multiple", false)
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(6)
            ->hideOnIndex();

        yield FormTypeField::new('countries', 'sylius.form.channel.countries', CountryChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("required", false)
            ->setFormTypeOption("multiple", true)
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(12)
            ->hideOnIndex();

        yield FormTypeField::new('defaultTaxZone', 'sylius.form.channel.tax_zone_default', ZoneChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("required", false)
            ->setFormTypeOption("zone_scope", Scope::TAX)
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(6)
            ->hideOnIndex();

        yield FormTypeField::new('taxCalculationStrategy', 'sylius.form.channel.tax_calculation_strategy', TaxCalculationStrategyChoiceType::class)->hideOnIndex()
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(6)
            ->hideOnIndex();

        yield FormTypeField::new('themeName', 'sylius.form.channel.theme', ThemeNameChoiceType::class)
            ->setFormTypeOption("required", false)
            ->setFormTypeOption("empty_data", null)
            ->setFormTypeOption("placeholder", 'sylius.ui.no_theme')
            ->setFormTypeOption('attr', ["data-ea-widget" => "ea-autocomplete"])->setColumns(12)
            ->hideOnIndex();

        yield EmailField::new('contactEmail', "sylius.form.channel.contact_email")->setColumns(6)
            ->hideOnIndex();
        yield TelephoneField::new('contactPhoneNumber', "sylius.form.channel.contact_phone_number")->setColumns(6)
            ->hideOnIndex();
        yield BooleanField::new('skippingShippingStepAllowed', 'sylius.form.channel.skipping_shipping_step_allowed')
            ->hideOnIndex();
        yield BooleanField::new('skippingPaymentStepAllowed', 'sylius.form.channel.skipping_payment_step_allowed')
            ->hideOnIndex();
        yield BooleanField::new('accountVerificationRequired', 'sylius.form.channel.account_verification_required')
            ->hideOnIndex();

        yield AssociationField::new('menuTaxon', 'sylius.form.channel.menu_taxon')
            ->hideOnIndex();

        yield FormTypeField::new('shopBillingData', 'sylius.form.channel.shop_billing_data', ShopBillingDataType::class)
            ->hideOnIndex();

    }

}
