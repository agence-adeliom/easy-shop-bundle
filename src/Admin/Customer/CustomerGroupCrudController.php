<?php

namespace Adeliom\EasyShopBundle\Admin\Customer;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use App\Entity\Shop\Customer\CustomerGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

abstract class CustomerGroupCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "customer_group";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyShop/SyliusFormTheme.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_customer_groups")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.customer_groups")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.customer_groups")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.customer_groups")
            ->setEntityLabelInSingular('sylius.ui.customer_groups')
            ->setEntityLabelInPlural('sylius.ui.customer_groups')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new("code", 'sylius.ui.code')->setRequired(true)
            ->setFormTypeOption('disabled', ($pageName == Crud::PAGE_EDIT ? 'disabled' : ''));
        yield TextField::new("name", 'sylius.form.customer_group.name')->setRequired(true);
    }
}
