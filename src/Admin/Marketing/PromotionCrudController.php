<?php

namespace Adeliom\EasyShopBundle\Admin\Marketing;

use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyFieldsBundle\Admin\Field\SortableCollectionField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use Adeliom\EasyShopBundle\Form\Type\PromotionBundle\PromotionActionType;
use Adeliom\EasyShopBundle\Form\Type\PromotionBundle\PromotionRuleType;
use App\Entity\Shop\Promotion\Promotion;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\PromotionBundle\Form\Type\PromotionCouponGeneratorInstructionType;
use Sylius\Bundle\PromotionBundle\Form\Type\PromotionCouponType;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Factory\PromotionCouponFactoryInterface;
use Sylius\Component\Promotion\Generator\PromotionCouponGeneratorInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class PromotionCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "promotion";
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.factory.promotion_coupon' => '?' . PromotionCouponFactoryInterface::class,
            'sylius.manager.promotion_coupon' => '?' . EntityManagerInterface::class,
            'sylius.repository.promotion_coupon' => '?' . PromotionCouponRepositoryInterface::class,
            'sylius.promotion_coupon_generator' => '?' . PromotionCouponGeneratorInterface::class,
            ParameterBagInterface::class => '?' . ParameterBagInterface::class,
        ]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyFields/form/choice_mask_widget.html.twig')
            ->addFormTheme('@EasyFields/form/sortable_widget.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_promotions")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.create_promotion")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_promotion")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.promotions")
            ->setEntityLabelInSingular('sylius.ui.promotions')
            ->setEntityLabelInPlural('sylius.ui.promotions')
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $viewCoupon = Action::new('manageCoupon', 'sylius.ui.manage_coupons', 'fas fa-ticket-alt')
            ->displayIf(static fn($entity) => $entity->isCouponBased())->linkToCrudAction("manageCoupon");

        $actions
            ->add(Crud::PAGE_INDEX, $viewCoupon);


        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code', 'sylius.ui.code')
            ->setFormTypeOption('disabled', ($pageName == Crud::PAGE_EDIT ? 'disabled' : ''))
            ->setRequired(true)->setColumns(6);
        yield TextField::new('name', 'sylius.form.promotion.name')->setRequired(true)->setColumns(6);
        yield TextareaField::new('description', 'sylius.form.promotion.description')->setColumns(12)->hideOnIndex();
        yield IntegerField::new('usageLimit', 'sylius.form.promotion.usage_limit')->setColumns(6);
        yield IntegerField::new('priority', 'sylius.form.promotion.priority')->setColumns(6)->setHelp('sylius.form.promotion.priority-help');
        yield BooleanField::new('exclusive', 'sylius.form.promotion.exclusive')->setColumns(6)->hideOnIndex()->setHelp('sylius.form.promotion.exclusive-help');
        yield BooleanField::new('couponBased', 'sylius.form.promotion.coupon_based')->setColumns(6)->renderAsSwitch(in_array($pageName, [Crud::PAGE_EDIT, Crud::PAGE_NEW]));

        yield FormTypeField::new('channels', 'sylius.form.promotion.channels', ChannelChoiceType::class)->hideOnIndex()
            ->setFormTypeOptions(['multiple' => true, 'expanded' => true]);

        yield DateTimeField::new("startsAt", 'sylius.form.promotion.starts_at')->setColumns(6)->hideOnIndex();
        yield DateTimeField::new("endsAt", 'sylius.form.promotion.ends_at')->setColumns(6)->hideOnIndex();

        yield SortableCollectionField::new('rules', 'sylius.form.promotion.rules')->setColumns(6)
            ->setEntryType(PromotionRuleType::class)->allowAdd()->allowDrag(false)
            ->setFormTypeOption('hide_title', true)
            ->hideOnIndex();
        yield SortableCollectionField::new('actions', 'sylius.form.promotion.actions')->setColumns(6)
            ->setEntryType(PromotionActionType::class)->allowAdd()->allowDrag(false)
            ->setFormTypeOption('hide_title', true)
            ->hideOnIndex();
    }

    public function manageCoupon(AdminContext $context): Response
    {
        return $this->render('@EasyShop/crud/promotion/coupon.html.twig', [
            'promotion' => $context->getEntity()->getInstance()
        ]);
    }

    public function createCoupon(AdminContext $context): Response
    {
        $coupon = $this->container->get("sylius.factory.promotion_coupon")->createForPromotion($context->getEntity()->getInstance());
        $form = $this->createForm(PromotionCouponType::class, $coupon);

        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $coupon = $form->getData();
            $this->container->get("sylius.manager.promotion_coupon")->persist($coupon);
            $this->container->get("sylius.manager.promotion_coupon")->flush();
            $url = $this->container->get(AdminUrlGenerator::class)->setController($this::class)->setEntityId($coupon->getPromotion()->getId())->setAction("manageCoupon")->generateUrl();
            return $this->redirect($url);
        }

        return $this->render('@EasyShop/crud/promotion/new_coupon.html.twig', [
            'promotion' => $context->getEntity()->getInstance(),
            'form' => $form->createView()
        ]);
    }

    public function generateCoupons(AdminContext $context): Response
    {
        $form = $this->createForm(PromotionCouponGeneratorInstructionType::class);

        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $instruction = $form->getData();
            $this->container->get('sylius.promotion_coupon_generator')->generate($context->getEntity()->getInstance(), $instruction);

            $url = $this->container->get(AdminUrlGenerator::class)->setController($this::class)->setEntityId($context->getEntity()->getPrimaryKeyValue())->setAction("manageCoupon")->generateUrl();
            return $this->redirect($url);
        }

        return $this->render('@EasyShop/crud/promotion/generate_coupons.html.twig', [
            'promotion' => $context->getEntity()->getInstance(),
            'form' => $form->createView()
        ]);
    }

    public function batchDeleteCoupons(AdminContext $context): Response
    {
        $coupon = null;
        foreach ($context->getRequest()->get("batchActionEntityIds", []) as $i) {
            $coupon = $this->container->get("sylius.repository.promotion_coupon")->find($i);
            if (!$coupon) {
                continue;
            }

            $this->container->get("sylius.manager.promotion_coupon")->remove($coupon);
            $this->container->get("sylius.manager.promotion_coupon")->flush();
        }

        $url = $this->container->get(AdminUrlGenerator::class)->setController($this::class)->setEntityId($coupon->getPromotion()->getId())->setAction("manageCoupon")->generateUrl();
        return $this->redirect($url);
    }

    public function editCoupon(AdminContext $context): Response
    {
        $coupon = $this->container->get('sylius.repository.promotion_coupon')->find($context->getRequest()->query->get("couponId"));
        if (!($coupon instanceof PromotionCouponInterface)) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(PromotionCouponType::class, $coupon);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $coupon = $form->getData();
            $this->container->get("sylius.manager.promotion_coupon")->persist($coupon);
            $this->container->get("sylius.manager.promotion_coupon")->flush();
            $url = $this->container->get(AdminUrlGenerator::class)->setController($this::class)->setEntityId($coupon->getPromotion()->getId())->setAction("manageCoupon")->generateUrl();
            return $this->redirect($url);
        }

        return $this->render('@EasyShop/crud/promotion/edit_coupon.html.twig', [
            'promotion' => $context->getEntity()->getInstance(),
            'form' => $form->createView()
        ]);
    }

    public function deleteCoupon(AdminContext $context): Response
    {
        $coupon = $this->container->get('sylius.repository.promotion_coupon')->find($context->getRequest()->query->get("couponId"));
        if (!($coupon instanceof PromotionCouponInterface)) {
            throw new NotFoundHttpException();
        }

        $this->container->get("sylius.manager.promotion_coupon")->remove($coupon);
        $this->container->get("sylius.manager.promotion_coupon")->flush();

        $url = $this->container->get(AdminUrlGenerator::class)->setController($this::class)->setEntityId($coupon->getPromotion()->getId())->setAction("manageCoupon")->generateUrl();
        return $this->redirect($url);
    }
}
