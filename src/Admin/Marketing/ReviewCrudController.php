<?php

namespace Adeliom\EasyShopBundle\Admin\Marketing;

use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use SM\Factory\Factory;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\ProductReviewTransitions;
use Sylius\Component\Review\Model\Review;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\HttpFoundation\Response;

abstract class ReviewCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "product_review";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsAsDropdown(false)
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_reviews")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.reviews")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.review_details")
            ->setEntityLabelInSingular('sylius.ui.reviews')
            ->setEntityLabelInPlural('sylius.ui.reviews')
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $accept = Action::new('accept', 'sylius.ui.accept')->addCssClass('text-success')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() == Review::STATUS_NEW;
            })->linkToCrudAction("accept");

        $reject = Action::new('reject', 'sylius.ui.reject')->addCssClass('text-warning')
            ->displayIf(static function ($entity) {
                return $entity->getStatus() == Review::STATUS_NEW;
            })->linkToCrudAction("reject");

        $actions
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_INDEX, $accept)
            ->disable(Action::NEW)
        ;
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'sylius.form.review.title')->setRequired(true);
        yield TextareaField::new('comment', 'sylius.form.review.comment')->setRequired(true);
        yield FormTypeField::new('rating', 'sylius.form.review.rating', RangeType::class)
            ->setFormTypeOption("attr", [
                'min' => 1,
                'max' => 5
            ])
            ->setRequired(true);

        yield ChoiceField::new('status', 'sylius.form.review.status.label')->setChoices([
            "sylius.ui.".Review::STATUS_ACCEPTED => Review::STATUS_ACCEPTED,
            "sylius.ui.".Review::STATUS_NEW => Review::STATUS_NEW,
            "sylius.ui.".Review::STATUS_REJECTED => Review::STATUS_REJECTED
        ])->renderAsBadges([
            Review::STATUS_ACCEPTED => 'success',
            Review::STATUS_NEW => 'info',
            Review::STATUS_REJECTED => 'danger'
        ])->setRequired(true)->onlyOnIndex();
    }

    public function accept(AdminContext $context): Response
    {
        return $this->updateReviewStatus(ProductReviewTransitions::TRANSITION_ACCEPT, $context);
    }

    public function reject(AdminContext $context): Response
    {
        return $this->updateReviewStatus(ProductReviewTransitions::TRANSITION_REJECT, $context);
    }

    private function updateReviewStatus(string $transition, AdminContext $context)
    {
        $entity = $context->getEntity()->getInstance();

        $sm = $this->get(Factory::class)->get($entity, "sylius_product_review");
        if($sm->apply($transition)) {
            $this->updateEntity($this->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entity);
        }

        if (null !== $referrer = $context->getReferrer()) {
            return $this->redirect($referrer);
        }

        return $this->redirect($this->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            Factory::class => '?'.FactoryInterface::class
        ]);
    }

}
