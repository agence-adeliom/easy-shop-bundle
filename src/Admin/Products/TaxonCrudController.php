<?php

namespace Adeliom\EasyShopBundle\Admin\Products;

use Adeliom\EasyFieldsBundle\Admin\Field\AssociationField;
use Adeliom\EasyFieldsBundle\Admin\Field\TranslationField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\RouterInterface;

abstract class TaxonCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "taxon";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyEditor/form/editor_widget.html.twig')
            ->addFormTheme('@EasyFields/form/association_widget.html.twig')
            ->addFormTheme('@EasyFields/form/sortable_widget.html.twig')
            ->addFormTheme('@EasyFields/form/choice_mask_widget.html.twig')
            ->addFormTheme('@EasyFields/form/translations_widget.html.twig')
            ->addFormTheme('@EasyMedia/form/easy-media.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_taxons")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_taxon")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_taxon")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.taxonomy")
            ->setEntityLabelInSingular('sylius.ui.taxonomy')
            ->setEntityLabelInPlural('sylius.ui.taxons')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }


    public function configureActions(Actions $actions): Actions
    {

        $viewTaxon = Action::new('viewTaxon', 'Voir la page', 'fa fa-eye')->linkToUrl(fn(TaxonInterface $taxon) => $this->get(RouterInterface::class)->generate('sylius_shop_product_index', [
            'slug' => $taxon->getTree()
        ]))->setHtmlAttributes(["target" => "_blank"]);

        $viewTaxonButton = Action::new('viewTaxon', 'Voir la page', 'fa fa-eye')->linkToUrl(fn(TaxonInterface $taxon) => $this->get(RouterInterface::class)->generate('sylius_shop_product_index', [
            'slug' => $taxon->getTree()
        ]))->setHtmlAttributes(["target" => "_blank"])->setCssClass("btn btn-info");
        $actions->add(Crud::PAGE_INDEX, $viewTaxon);
        $actions->add(Crud::PAGE_DETAIL, $viewTaxonButton);
        $actions->add(Crud::PAGE_EDIT, $viewTaxonButton);
        return $actions;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            RouterInterface::class => '?' . RouterInterface::class,
        ]);
    }


    public function configureFields(string $pageName): iterable
    {
        $fieldsConfig = [
            'name' => [
                'field_type' => TextType::class,
                'label' => 'sylius.form.taxon.name',
                'required' => true,
            ],
            'slug' => [
                'field_type' => TextType::class,
                'label' => 'sylius.form.taxon.slug',
                'required' => false,
            ],
            'description' => [
                'field_type' => TextareaType::class,
                'label' => 'sylius.form.taxon.description',
                'required' => false,
            ]
        ];

        yield TextField::new('code', 'sylius.ui.code');
        yield TextField::new('name', 'sylius.ui.name')->onlyOnIndex();
        yield AssociationField::new('parent', 'sylius.form.taxon.parent')->autocomplete()->listSelector()->listDisplayColumns([1, 2]);
        yield BooleanField::new('enabled', 'sylius.form.taxon.enabled')->renderAsSwitch(in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT]));
        yield FormField::addPanel('sylius.form.taxon.name')->collapsible();
        yield TranslationField::new("translations", false, $fieldsConfig);
    }
}
