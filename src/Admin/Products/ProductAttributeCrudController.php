<?php

namespace Adeliom\EasyShopBundle\Admin\Products;

use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyFieldsBundle\Admin\Field\TranslationField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sylius\Bundle\AttributeBundle\Form\Type\AttributeTypeChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Registry\FormTypeRegistryInterface;
use Sylius\Component\Attribute\Factory\AttributeFactoryInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

abstract class ProductAttributeCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "product_attribute";
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.form_registry.attribute_type' => '?' . FormTypeRegistryInterface::class,
            'sylius.custom_factory.product_attribute' => '?' . AttributeFactoryInterface::class,
            ParameterBagInterface::class => '?' . ParameterBagInterface::class,
        ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $url = $this->container->get(AdminUrlGenerator::class)->setController($this::class)->setAction(Action::NEW);

        $actions = parent::configureActions($actions);
        $actions->remove(Crud::PAGE_INDEX, Action::NEW);

        foreach (array_reverse($this->container->get(ParameterBagInterface::class)->get("sylius.attribute.attribute_types")) as $type => $name) {
            $actionType = Action::new($type, $name)
                ->linkToUrl((clone $url)->set("attributeType", $type)->generateUrl())
                ->createAsGlobalAction()
                ->setCssClass("btn btn-primary");
            $actions->add(Crud::PAGE_INDEX, $actionType);
        }

        return $actions;
    }

    public function new(AdminContext $context)
    {
        global $attributeType;
        $attributeType = $context->getRequest()->query->get("attributeType");

        return parent::new($context);
    }

    public function createEntity(string $entityFqcn)
    {
        global $attributeType;
        /** @var ProductAttributeInterface $entity */
        $entity = $this->container->get('sylius.custom_factory.product_attribute')->createTyped($attributeType);
        return $entity;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyShop/SyliusFormTheme.html.twig')
            ->addFormTheme('@EasyFields/form/translations_widget.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_attributes_of_your_products")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.create_product_attribute")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_product_attribute")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.association")
            ->setEntityLabelInSingular('sylius.ui.attributes')
            ->setEntityLabelInPlural('sylius.ui.attributes')
            ->setFormOptions([
                "attr" => ["novalidate" => "novalidate"],
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $context = $this->container->get(AdminContextProvider::class)->getContext();
        $subject = $context->getEntity();
        $attribute = $subject->getInstance();

        $fieldsConfig = [
            'name' => [
                'field_type' => TextType::class,
                'required' => true,
                'label' => 'sylius.form.attribute.name'
            ]
        ];

        yield TextField::new('code', 'sylius.ui.code')
            ->setFormTypeOption('disabled', ($pageName == Crud::PAGE_EDIT ? 'disabled' : ''))
            ->setColumns(4);
        yield IntegerField::new('position', 'sylius.form.product_attribute.position')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setRequired(false)
            ->setFormTypeOption('invalid_message', 'sylius.product_attribute.invalid')
            ->setColumns(4);

        yield FormTypeField::new('type', 'sylius.form.attribute.type', AttributeTypeChoiceType::class)
            ->setFormTypeOption("disabled", true)
            ->setColumns(4);
        yield BooleanField::new('translatable', 'sylius.form.attribute.translatable')->renderAsSwitch(in_array($pageName, [Crud::PAGE_EDIT, Crud::PAGE_NEW]));

        if (in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT]) && (($attribute instanceof AttributeInterface) && $this->container->get('sylius.form_registry.attribute_type')->has($attribute->getType(), 'configuration'))) {
            yield FormTypeField::new('configuration', 'sylius.form_registry.attribute_type', $this->container->get('sylius.form_registry.attribute_type')->get($attribute->getType(), 'configuration'))
                ->setFormTypeOption("auto_initialize", false);
        }

        yield FormField::addPanel('sylius.form.attribute.translations');
        yield TranslationField::new("translations", false, $fieldsConfig);
    }
}
