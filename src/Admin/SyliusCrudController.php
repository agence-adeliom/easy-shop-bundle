<?php

namespace Adeliom\EasyShopBundle\Admin;


use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\FormInterface;

abstract class SyliusCrudController extends AbstractCrudController {

    abstract public static function getResource(): string;

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addHtmlContentToHead('<style>.alert-error{    background-color: #f5d8e0;    border-color: #f0c5d0;    color: #7b243b;}</style>')
            ;
    }

    public function createEditForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityDto->getInstance()) , sprintf("sylius.%s.initialize_update", static::getResource()));
        return parent::createEditForm($entityDto, $formOptions, $context);
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityDto->getInstance()) , sprintf("sylius.%s.initialize_create", static::getResource()));
        return parent::createNewForm($entityDto, $formOptions, $context);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->persist($entityInstance);
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityInstance) , sprintf("sylius.%s.pre_update", static::getResource()));
        $entityManager->flush();
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityInstance) , sprintf("sylius.%s.post_update", static::getResource()));
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityInstance) , sprintf("sylius.%s.pre_create", static::getResource()));
        $entityManager->persist($entityInstance);
        $entityManager->flush();
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityInstance) , sprintf("sylius.%s.post_create", static::getResource()));
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityInstance) , sprintf("sylius.%s.pre_delete", static::getResource()));
        $entityManager->remove($entityInstance);
        $entityManager->flush();
        $this->get("event_dispatcher")->dispatch(new GenericEvent($entityInstance) , sprintf("sylius.%s.post_delete", static::getResource()));
    }

}
