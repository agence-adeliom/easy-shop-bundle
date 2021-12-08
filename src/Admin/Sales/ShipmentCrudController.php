<?php

namespace Adeliom\EasyShopBundle\Admin\Sales;

use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use App\Entity\Shop\Shipping\Shipment;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use SM\Factory\Factory;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\CoreBundle\Mailer\Emails;
use Sylius\Bundle\OrderBundle\Doctrine\ORM\OrderRepository;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Sylius\Component\Mailer\Sender\Sender;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Shipping\ShipmentTransitions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

abstract class ShipmentCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "shipment";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.shipments")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.shipment_details")
            ->setEntityLabelInSingular('sylius.ui.shipment')
            ->setEntityLabelInPlural('sylius.ui.shipments')
            ->showEntityActionsAsDropdown(false)
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ->setDefaultSort([
                "createdAt" => "DESC"
            ])
            ->overrideTemplate('crud/detail', '@EasyShop/crud/shipment/detail.html.twig')
            ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere("entity.state NOT LIKE 'cart'");

        return $qb;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add(DateTimeFilter::new('createdAt','sylius.ui.date'))
            ->add(ChoiceFilter::new('state','sylius.ui.state')->setChoices([
                'sylius.ui.' . ShipmentInterface::STATE_CART => ShipmentInterface::STATE_CART,
                'sylius.ui.' . ShipmentInterface::STATE_CANCELLED => ShipmentInterface::STATE_CANCELLED,
                'sylius.ui.' . ShipmentInterface::STATE_SHIPPED => ShipmentInterface::STATE_SHIPPED,
                'sylius.ui.' . ShipmentInterface::STATE_READY => ShipmentInterface::STATE_READY,
            ]))
        ;

        return $filters;
    }

    public function configureActions(Actions $actions): Actions
    {

        $actions->remove(Crud::PAGE_INDEX, Action::NEW);
        $actions->remove(Crud::PAGE_INDEX, Action::EDIT);
        $actions->remove(Crud::PAGE_INDEX, Action::DELETE);
        $actions->remove(Crud::PAGE_DETAIL, Action::DELETE);
        $actions->remove(Crud::PAGE_DETAIL, Action::EDIT);

        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('createdAt', 'sylius.ui.created_at');
        yield DateTimeField::new('shippedAt', 'sylius.ui.shipped_at');
        yield TextField::new('order.channel', 'sylius.ui.channel');
        yield TextField::new('order.number', 'sylius.ui.code')->formatValue(function ($value, $entity){
            if($value){
                return "#" . $value;
            }
        });
        yield TextField::new('order.customer', 'sylius.ui.customer')->formatValue(function ($value, $entity){
            if($value){
                return '<strong>' . $entity->getOrder()->getCustomer()->getFullName() . "</strong><br>" . $entity->getOrder()->getCustomer()->getEmail();
            }
        });
        yield ChoiceField::new('state', 'sylius.ui.state')
            ->setChoices([
                'sylius.ui.' . ShipmentInterface::STATE_CART => ShipmentInterface::STATE_CART,
                'sylius.ui.' . ShipmentInterface::STATE_CANCELLED => ShipmentInterface::STATE_CANCELLED,
                'sylius.ui.' . ShipmentInterface::STATE_SHIPPED => ShipmentInterface::STATE_SHIPPED,
                'sylius.ui.' . ShipmentInterface::STATE_READY => ShipmentInterface::STATE_READY,
            ])->setTemplatePath('@EasyShop/crud/Common/Label/shipmentState.html.twig');
    }

    public function shipmentTracking(AdminContext $context)
    {
        $request = $context->getRequest();

        /** @var ShipmentInterface|null $shipment */
        $shipment = $context->getEntity()->getInstance();
        /** @var OrderInterface|null $order */
        $order = $shipment->getOrder();

        $em = $this->getDoctrine()->getManager();
        $sm = $this->get(Factory::class)->get($shipment, "sylius_shipment");
        if($request->get('tracking')){
            if($sm->apply(ShipmentTransitions::TRANSITION_SHIP)) {
                $shipment->setTracking($request->get('tracking'));
                $em->persist($shipment);
                $em->flush();

                $this->get(Sender::class)->send(
                    Emails::SHIPMENT_CONFIRMATION,
                    [$order->getCustomer()->getEmail()],
                    [
                        'shipment' => $shipment,
                        'order' => $order,
                        'channel' => $order->getChannel(),
                        'localeCode' => $order->getLocaleCode(),
                    ]
                );
                $this->addFlash(
                    'success',
                    'sylius.shipment.shipped'
                );
            }
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function resendShipmentConfirmationEmail(AdminContext $context)
    {
        $request = $context->getRequest();

        /** @var ShipmentInterface|null $shipment */
        $shipment = $context->getEntity()->getInstance();
        /** @var OrderInterface|null $order */
        $order = $shipment->getOrder();

        if (!$this->get(CsrfTokenManager::class)->isTokenValid(new CsrfToken($shipment->getId(), (string) $request->query->get('_csrf_token')))) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Invalid csrf token.');
        }

        $this->get(Sender::class)->send(
            Emails::SHIPMENT_CONFIRMATION,
            [$order->getCustomer()->getEmail()],
            [
                'shipment' => $shipment,
                'order' => $order,
                'channel' => $order->getChannel(),
                'localeCode' => $order->getLocaleCode(),
            ]
        );

        $this->addFlash(
            'success',
            'sylius.email.shipment_confirmation_resent'
        );

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function showOrder(AdminContext $context)
    {
        /** @var ParameterBagInterface $bag */
        $bag = $this->get(ParameterBagInterface::class);

        $order = $context->getEntity()->getInstance()->getOrder();
        $crud = $context->getCrudControllers()->findCrudFqcnByEntityFqcn($bag->get('sylius.model.order.class'));

        return $this->redirect(
            $this->get(AdminUrlGenerator::class)
                ->setController($crud)
                ->setAction(Action::DETAIL)
                ->set(EA::ENTITY_ID, $order->getId())
                ->generateUrl()
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            OrderRepository::class => '?'.OrderRepositoryInterface::class,
            'sylius.repository.shipment' => '?'.ShipmentRepositoryInterface::class,
            'sylius.repository.payment' => '?'.PaymentRepositoryInterface::class,
            Factory::class => '?'.FactoryInterface::class,
            Sender::class => '?'.SenderInterface::class,
            CsrfTokenManager::class => '?'.CsrfTokenManagerInterface::class,
            ParameterBagInterface::class => '?'.ParameterBagInterface::class,
        ]);
    }

}
