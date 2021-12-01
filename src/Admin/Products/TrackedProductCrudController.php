<?php

namespace Adeliom\EasyShopBundle\Admin\Products;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Proxy;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

abstract class TrackedProductCrudController extends AbstractCrudController
{

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyCommon/crud/custom_panel.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.inventory_state")
            ->setEntityLabelInSingular('sylius.ui.inventory_state')
            ->setEntityLabelInPlural('sylius.ui.inventory_state')
            ->showEntityActionsAsDropdown();
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder->andWhere($queryBuilder->getRootAliases()[0].'.tracked = 1');
        return $queryBuilder;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters->add(TextFilter::new("code", "sylius.ui.code"));
        $filters->add(NumericFilter::new("onHand", "sylius.ui.available_on_hand"));
        $filters->add(NumericFilter::new("onHold", "sylius.ui.on_hold"));
        return $filters;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->remove(Action::INDEX, 'edit');
        $actions->remove(Action::INDEX, 'delete');
        $actions->remove(Action::INDEX, 'new');

        $actions->add(Action::INDEX, Action::new('editProductVariant', 'sylius.ui.edit_product_variant', 'fas fa-edit')->linkToCrudAction("productVariantEdit"));
        $actions->add(Action::INDEX, Action::new('editProduct', 'sylius.ui.edit_product', 'fas fa-edit')->linkToCrudAction("productEdit"));

        return $actions;
    }

    public function productEdit(AdminContext $context)
    {
        $entity = $context->getEntity();
        $product = $entity->getInstance()->getProduct();

        $objectClass = get_class($product);
        $reflectionClass = new \ReflectionClass($objectClass);
        if ($product instanceof Proxy) {
            $reflectionClass = $reflectionClass->getParentClass();
        }

        $url = $this->get(AdminUrlGenerator::class)
            ->setController($context->getCrudControllers()->findCrudFqcnByEntityFqcn($reflectionClass->getName()))
            ->setAction('edit')
            ->setEntityId($product->getId());
        return $this->redirect($url);
    }

    public function productVariantEdit(AdminContext $context)
    {
        $entity = $context->getEntity();
        $product = $entity->getInstance()->getProduct();

        $objectClass = get_class($product);
        $reflectionClass = new \ReflectionClass($objectClass);
        if ($product instanceof Proxy) {
            $reflectionClass = $reflectionClass->getParentClass();
        }

        $url = $this->get(AdminUrlGenerator::class)
            ->setController($context->getCrudControllers()->findCrudFqcnByEntityFqcn($reflectionClass->getName()))
            ->setAction('editVariant')
            ->setEntityId($product->getId())
            ->set("variantId", $entity->getPrimaryKeyValue());
        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code', 'sylius.ui.code');
        yield TextField::new('name', 'sylius.ui.name');
        yield NumberField::new('onHand', 'sylius.ui.available_on_hand');
        yield NumberField::new('onHold', 'sylius.ui.on_hold');
    }

}
