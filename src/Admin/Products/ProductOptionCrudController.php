<?php

namespace Adeliom\EasyShopBundle\Admin\Products;

use Adeliom\EasyFieldsBundle\Admin\Field\TranslationField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sylius\Bundle\ProductBundle\Form\Type\ProductOptionValueType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

abstract class ProductOptionCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "product_option";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyShop/SyliusFormTheme.html.twig')
            ->addFormTheme('@EasyFields/form/translations_widget.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_configuration_options_of_your_products")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_product_option")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_product_option")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.options")
            ->setEntityLabelInSingular('sylius.ui.options')
            ->setEntityLabelInPlural('sylius.ui.options')
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $fieldsConfig = [
            'name' => [
                'field_type' => TextType::class,
                'required' => true,
                'label' => 'sylius.form.option.name'
            ]
        ];

        yield TextField::new('code', 'sylius.ui.code')
            ->setFormTypeOption('disabled', ($pageName == Crud::PAGE_EDIT ? 'disabled' : ''))
            ->setColumns(6);
        yield IntegerField::new('position', 'sylius.form.option.position')
            ->setRequired(false)
            ->setColumns(6);

        yield FormField::addPanel('sylius.form.option.name');
        yield TranslationField::new("translations", false, $fieldsConfig);

        yield FormField::addPanel('sylius.form.option.values');
        yield CollectionField::new("values", "sylius.form.option.values")
            ->setEntryType(ProductOptionValueType::class)
            ->allowAdd()
            ->setFormTypeOption("by_reference", false)
            ->setFormTypeOption("label", false)
            ->setFormTypeOption("required", true)
            ->setFormTypeOption("button_add_label", 'sylius.form.option_value.add_value');
    }
}
