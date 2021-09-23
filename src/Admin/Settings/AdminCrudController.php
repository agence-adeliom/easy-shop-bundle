<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AdminCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "admin_user";
    }

    public function createEntity(string $entityFqcn)
    {
        return $this->get('sylius.factory.admin_user')->createNew();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_api_users")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_api_users")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_api_users")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.api_users_details")
            ->setEntityLabelInSingular('sylius.ui.api_user')
            ->setEntityLabelInPlural('sylius.ui.api_users')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new("username", 'sylius.form.user.username')->setRequired(true)->setColumns('col-12 col-sm-4');
        yield EmailField::new("email", 'sylius.form.user.email')->setRequired(true)->setColumns('col-12 col-sm-4');
        yield TextField::new("plainPassword", 'sylius.form.user.password.label')->setRequired($pageName == Crud::PAGE_NEW)->setColumns('col-12 col-sm-4')->onlyOnForms();
        yield LocaleField::new('localeCode', 'sylius.ui.locale')
            ->showCode(true)
            ->setColumns('col-12 col-sm-4')
            ->setFormTypeOption("required", true);
        yield BooleanField::new("enabled", 'sylius.form.user.enabled')->renderAsSwitch($pageName != Crud::PAGE_INDEX)->setColumns('col-12 col-sm-6');
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.factory.admin_user' => '?'.FactoryInterface::class,
            TranslatorInterface::class => '?'.TranslatorInterface::class,
            ParameterBagInterface::class => '?'.ParameterBagInterface::class,
        ]);
    }



}
