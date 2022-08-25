<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyFieldsBundle\Admin\Field\SortableCollectionField;
use Adeliom\EasyFieldsBundle\Admin\Field\TranslationField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use Adeliom\EasyShopBundle\Form\Type\ShippingBundle\ShippingMethodCalculatorType;
use Adeliom\EasyShopBundle\Form\Type\ShippingBundle\ShippingMethodRuleType;
use App\Entity\Shop\Shipping\ShippingMethod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sylius\Bundle\AddressingBundle\Form\Type\ZoneChoiceType;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingCategoryChoiceType;
use Sylius\Bundle\TaxationBundle\Form\Type\TaxCategoryChoiceType;
use Sylius\Component\Core\Model\Scope;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Review\Model\Review;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class ShippingMethodCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "shipping_method";
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.form_registry.shipping_calculator' => '?' . FormTypeRegistryInterface::class,
            'sylius.registry.shipping_calculator' => '?' . ServiceRegistryInterface::class
        ]);
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyShop/SyliusFormTheme.html.twig')
            ->addFormTheme('@EasyFields/form/choice_mask_widget.html.twig')
            ->addFormTheme('@EasyFields/form/sortable_widget.html.twig')
            ->addFormTheme('@EasyFields/form/translations_widget.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_shipping_methods")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_shipping_method")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_shipping_method")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.shipping_method_details")
            ->setEntityLabelInSingular('sylius.ui.shipping_method')
            ->setEntityLabelInPlural('sylius.ui.shipping_methods')
            ->showEntityActionsAsDropdown(false)
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $archive = Action::new('archive', 'sylius.ui.archive')->addCssClass('text-warning')
            ->displayIf(static fn($entity) => $entity->getArchivedAt() == null)->linkToCrudAction("archive");

        $restore = Action::new('restore', 'sylius.ui.restore')->addCssClass('text-warning')
            ->displayIf(static fn($entity) => $entity->getArchivedAt() != null)->linkToCrudAction("restore");

        $archiveButton = Action::new('archive', 'sylius.ui.archive')
            ->displayIf(static fn($entity) => $entity->getArchivedAt() == null)->linkToCrudAction("archive")->setCssClass("btn btn-warning");
        $restoreButton = Action::new('restore', 'sylius.ui.restore')
            ->displayIf(static fn($entity) => $entity->getArchivedAt() != null)->linkToCrudAction("restore")->setCssClass("btn btn-warning");

        $actions->add(Crud::PAGE_INDEX, $archive);
        $actions->add(Crud::PAGE_INDEX, $restore);

        $actions->add(Crud::PAGE_EDIT, $archiveButton);
        $actions->add(Crud::PAGE_EDIT, $restoreButton);

        $actions->add(Crud::PAGE_DETAIL, $archiveButton);
        $actions->add(Crud::PAGE_DETAIL, $restoreButton);

        return $actions;
    }

    public function archive(AdminContext $context): Response
    {
        $entity = $context->getEntity()->getInstance();
        $entity->setArchivedAt(new \DateTime());
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entity);

        if (null !== $referrer = $context->getReferrer()) {
            return $this->redirect($referrer);
        }

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function restore(AdminContext $context): Response
    {
        $entity = $context->getEntity()->getInstance();
        $entity->setArchivedAt(null);
        $this->updateEntity($this->container->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entity);

        if (null !== $referrer = $context->getReferrer()) {
            return $this->redirect($referrer);
        }

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('sylius.ui.shipping_method');

        $fieldsConfig = [
            'name' => [
                'field_type' => TextType::class,
                'label' => 'sylius.form.shipping_method.name',
                'required' => true,
            ],
            'description' => [
                'field_type' => TextareaType::class,
                'label' => 'sylius.form.shipping_method.description',
                'required' => true,
            ]
        ];
        yield TextField::new('code', 'sylius.ui.code')
            ->setFormTypeOption('disabled', ($pageName == Crud::PAGE_EDIT ? 'disabled' : ''))
            ->setRequired(true);
        yield FormTypeField::new('zone', 'sylius.form.shipping_method.zone', ZoneChoiceType::class)
            ->setFormTypeOption("zone_scope", Scope::SHIPPING)->setRequired(true);
        yield IntegerField::new('position', 'sylius.form.shipping_method.position')->setRequired(true);
        yield BooleanField::new('enabled', 'sylius.form.locale.enabled')->renderAsSwitch(in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT]));
        yield FormTypeField::new('channels', 'sylius.form.shipping_method.channels', ChannelChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("multiple", true)
            ->setFormTypeOption("expanded", true);
        yield FormTypeField::new('taxCategory', 'sylius.form.shipping_method.tax_category', TaxCategoryChoiceType::class)->hideOnIndex()
            ->setFormTypeOption("required", false)
            ->setFormTypeOption("placeholder", '---');
        yield FormTypeField::new('category', 'sylius.form.shipping_method.category', ShippingCategoryChoiceType::class)->hideOnIndex();
        yield ChoiceField::new("categoryRequirement", 'sylius.form.shipping_method.category_requirement')->hideOnIndex()
            ->setChoices([
                'sylius.form.shipping_method.match_none_category_requirement' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_NONE,
                'sylius.form.shipping_method.match_any_category_requirement' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ANY,
                'sylius.form.shipping_method.match_all_category_requirement' => ShippingMethodInterface::CATEGORY_REQUIREMENT_MATCH_ALL,
            ])->renderExpanded();

        yield FormTypeField::new('calculator', 'sylius.form.shipping_method.calculator', ShippingMethodCalculatorType::class)->hideOnIndex();

        yield FormField::addPanel('sylius.form.shipping_method.rules')->setHelp("sylius.form.shipping_method.rules_help");
        yield SortableCollectionField::new('rules', 'sylius.form.shipping_method.rules')->hideOnIndex()
            ->setEntryType(ShippingMethodRuleType::class)->allowDrag(false);

        yield FormField::addPanel('sylius.form.shipping_method.translations');
        yield TranslationField::new("translations", false, $fieldsConfig)->hideOnIndex();

        yield BooleanField::new('archivedAt', 'sylius.ui.archival')->onlyOnIndex()->renderAsSwitch(false)->formatValue(static fn($value) => !is_null($value));
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formOptions->set("allow_extra_fields", true);
        return parent::createNewFormBuilder($entityDto, $formOptions, $context);
    }
}
