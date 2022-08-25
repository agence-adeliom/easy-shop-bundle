<?php

namespace Adeliom\EasyShopBundle\Admin\Sales;

use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use SM\Factory\Factory;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Sylius\Bundle\CoreBundle\Mailer\Emails;
use Sylius\Bundle\MoneyBundle\Formatter\MoneyFormatterInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Dashboard\DashboardStatistics;
use Sylius\Component\Core\Dashboard\DashboardStatisticsProviderInterface;
use Sylius\Component\Core\Dashboard\Interval;
use Sylius\Component\Core\Dashboard\SalesDataProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderShippingStates;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Sylius\Component\Mailer\Sender\Sender;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Shipping\ShipmentTransitions;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

abstract class OrderCrudController extends SyliusCrudController
{
    public function __construct(private \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    public static function getResource(): string
    {
        return "order";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyShop/SyliusFormTheme.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.orders")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_order")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_addresses")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.order_details")
            ->setEntityLabelInSingular('sylius.ui.order')
            ->setEntityLabelInPlural('sylius.ui.orders')
            ->showEntityActionsAsDropdown(false)
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ->setDefaultSort([
                "createdAt" => "DESC"
            ])
            ->overrideTemplate('crud/detail', '@EasyShop/crud/order/detail.html.twig')
            ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere("entity.state NOT LIKE 'cart'");
        return $qb;
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

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add(DateTimeFilter::new('createdAt', 'sylius.ui.date'))
            ->add(EntityFilter::new('channel', 'sylius.ui.channel'))
            ->add(EntityFilter::new('customer', 'sylius.ui.customer'))
            ->add(ChoiceFilter::new('state', 'sylius.ui.state')->setChoices([
                'sylius.ui.' . OrderInterface::STATE_CART => OrderInterface::STATE_CART,
                'sylius.ui.' . OrderInterface::STATE_CANCELLED => OrderInterface::STATE_CANCELLED,
                'sylius.ui.' . OrderInterface::STATE_FULFILLED => OrderInterface::STATE_FULFILLED,
                'sylius.ui.' . OrderInterface::STATE_NEW => OrderInterface::STATE_NEW
            ]))
            ->add(ChoiceFilter::new('paymentState', 'sylius.ui.payment_state')->setChoices([
                'sylius.ui.' . OrderPaymentStates::STATE_AUTHORIZED => OrderPaymentStates::STATE_AUTHORIZED,
                'sylius.ui.' . OrderPaymentStates::STATE_AWAITING_PAYMENT => OrderPaymentStates::STATE_AWAITING_PAYMENT,
                'sylius.ui.' . OrderPaymentStates::STATE_CANCELLED => OrderPaymentStates::STATE_CANCELLED,
                'sylius.ui.' . OrderPaymentStates::STATE_PAID => OrderPaymentStates::STATE_PAID,
                'sylius.ui.' . OrderPaymentStates::STATE_PARTIALLY_AUTHORIZED => OrderPaymentStates::STATE_PARTIALLY_AUTHORIZED,
                'sylius.ui.' . OrderPaymentStates::STATE_PARTIALLY_PAID => OrderPaymentStates::STATE_PARTIALLY_PAID,
                'sylius.ui.' . OrderPaymentStates::STATE_PARTIALLY_REFUNDED => OrderPaymentStates::STATE_PARTIALLY_REFUNDED,
                'sylius.ui.' . OrderPaymentStates::STATE_REFUNDED => OrderPaymentStates::STATE_REFUNDED,
            ]))
            ->add(ChoiceFilter::new('shippingState', 'sylius.ui.shipping_state')->setChoices([
                'sylius.ui.' . OrderShippingStates::STATE_CART => OrderShippingStates::STATE_CART,
                'sylius.ui.' . OrderShippingStates::STATE_CANCELLED => OrderShippingStates::STATE_CANCELLED,
                'sylius.ui.' . OrderShippingStates::STATE_PARTIALLY_SHIPPED => OrderShippingStates::STATE_PARTIALLY_SHIPPED,
                'sylius.ui.' . OrderShippingStates::STATE_READY => OrderShippingStates::STATE_READY,
                'sylius.ui.' . OrderShippingStates::STATE_SHIPPED => OrderShippingStates::STATE_SHIPPED,
            ]))
        ;

        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName == Crud::PAGE_INDEX) {
            yield DateTimeField::new('createdAt', 'sylius.ui.date');
            yield TextField::new('channel', 'sylius.ui.channel');
            yield TextField::new('number', 'sylius.ui.code')->formatValue(static function ($value, $entity) {
                if ($value) {
                    return "#" . $value;
                }
            });
            yield TextField::new('customer', 'sylius.ui.customer')->formatValue(static function ($value, $entity) {
                if ($value) {
                    return '<strong>' . $entity->getCustomer()->getFullName() . "</strong><br>" . $entity->getCustomer()->getEmail();
                }
            });
            yield ChoiceField::new('state', 'sylius.ui.state')
                ->setChoices([
                    'sylius.ui.' . OrderInterface::STATE_CART => OrderInterface::STATE_CART,
                    'sylius.ui.' . OrderInterface::STATE_CANCELLED => OrderInterface::STATE_CANCELLED,
                    'sylius.ui.' . OrderInterface::STATE_FULFILLED => OrderInterface::STATE_FULFILLED,
                    'sylius.ui.' . OrderInterface::STATE_NEW => OrderInterface::STATE_NEW
                ])->setTemplatePath('@EasyShop/crud/Common/Label/orderStates.html.twig');
            yield ChoiceField::new('paymentState', 'sylius.ui.payment_state')
                ->setChoices([
                    'sylius.ui.' . OrderPaymentStates::STATE_AUTHORIZED => OrderPaymentStates::STATE_AUTHORIZED,
                    'sylius.ui.' . OrderPaymentStates::STATE_AWAITING_PAYMENT => OrderPaymentStates::STATE_AWAITING_PAYMENT,
                    'sylius.ui.' . OrderPaymentStates::STATE_CANCELLED => OrderPaymentStates::STATE_CANCELLED,
                    'sylius.ui.' . OrderPaymentStates::STATE_PAID => OrderPaymentStates::STATE_PAID,
                    'sylius.ui.' . OrderPaymentStates::STATE_PARTIALLY_AUTHORIZED => OrderPaymentStates::STATE_PARTIALLY_AUTHORIZED,
                    'sylius.ui.' . OrderPaymentStates::STATE_PARTIALLY_PAID => OrderPaymentStates::STATE_PARTIALLY_PAID,
                    'sylius.ui.' . OrderPaymentStates::STATE_PARTIALLY_REFUNDED => OrderPaymentStates::STATE_PARTIALLY_REFUNDED,
                    'sylius.ui.' . OrderPaymentStates::STATE_REFUNDED => OrderPaymentStates::STATE_REFUNDED,
                ])->setTemplatePath('@EasyShop/crud/Common/Label/paymentStates.html.twig');
            yield ChoiceField::new('shippingState', 'sylius.ui.shipping_state')
                ->setChoices([
                    'sylius.ui.' . OrderShippingStates::STATE_CART => OrderShippingStates::STATE_CART,
                    'sylius.ui.' . OrderShippingStates::STATE_CANCELLED => OrderShippingStates::STATE_CANCELLED,
                    'sylius.ui.' . OrderShippingStates::STATE_PARTIALLY_SHIPPED => OrderShippingStates::STATE_PARTIALLY_SHIPPED,
                    'sylius.ui.' . OrderShippingStates::STATE_READY => OrderShippingStates::STATE_READY,
                    'sylius.ui.' . OrderShippingStates::STATE_SHIPPED => OrderShippingStates::STATE_SHIPPED,
                ])->setTemplatePath('@EasyShop/crud/Common/Label/shipmentStates.html.twig');
            yield NumberField::new('total', 'sylius.ui.total')->formatValue(static function ($value, $entity) {
                $formatter = new \NumberFormatter($entity->getLocaleCode(), \NumberFormatter::CURRENCY);
                return $formatter->formatCurrency($entity->getTotal() / 100, $entity->getCurrencyCode());
            })->setCssClass('text-md-end');
            yield CurrencyField::new('currencyCode', 'sylius.ui.currency');
        }

        if ($pageName == Crud::PAGE_EDIT) {
            yield FormTypeField::new('shippingAddress', 'sylius.ui.shipping_address', AddressType::class);
            yield FormTypeField::new('billingAddress', 'sylius.ui.billing_address', AddressType::class);
        }
    }

    public function showCustomer(AdminContext $context)
    {
        $customerCrud = $context->getCrudControllers()->findCrudFqcnByEntityFqcn($this->container->get(ParameterBagInterface::class)->get('sylius.model.customer.class'));
        return $this->redirect(
            $this->container->get(AdminUrlGenerator::class)
                ->setController($customerCrud)
                ->setAction(Action::DETAIL)
                ->set(EA::ENTITY_ID, $context->getRequest()->query->get("customerId"))
                ->generateUrl()
        );
    }

    public function showShipment(AdminContext $context)
    {
        $shipmentCrud = $context->getCrudControllers()->findCrudFqcnByEntityFqcn($this->container->get(ParameterBagInterface::class)->get('sylius.model.shipment.class'));
        return $this->redirect(
            $this->container->get(AdminUrlGenerator::class)
                ->setController($shipmentCrud)
                ->setAction(Action::DETAIL)
                ->set(EA::ENTITY_ID, $context->getRequest()->query->get("shipmentId"))
                ->generateUrl()
        );
    }

    public function paymentComplete(AdminContext $context)
    {
        $request = $context->getRequest();
        $paymentId = $request->query->get('payment_id');

        $payment = $this->container->get('sylius.repository.payment')->find($paymentId);
        if (!$payment instanceof \PaymentInterface) {
            throw new NotFoundHttpException(sprintf('The payment with id %s has not been found', $paymentId));
        }

        $em = $this->managerRegistry->getManager();
        $sm = $this->container->get(Factory::class)->get($payment, "sylius_payment");
        if ($sm->apply(PaymentTransitions::TRANSITION_COMPLETE)) {
            $em->persist($payment);
            $em->flush();

            $this->addFlash(
                'success',
                'sylius.shipment.completed'
            );
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function paymentRefund(AdminContext $context)
    {
        $request = $context->getRequest();
        $paymentId = $request->query->get('payment_id');

        $payment = $this->container->get('sylius.repository.payment')->find($paymentId);
        if (!$payment instanceof \PaymentInterface) {
            throw new NotFoundHttpException(sprintf('The payment with id %s has not been found', $paymentId));
        }

        $em = $this->managerRegistry->getManager();
        $sm = $this->container->get(Factory::class)->get($payment, "sylius_payment");
        if ($sm->apply(PaymentTransitions::TRANSITION_REFUND)) {
            $em->persist($payment);
            $em->flush();

            $this->addFlash(
                'success',
                'sylius.shipment.completed'
            );
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function shipmentTracking(AdminContext $context)
    {

        $request = $context->getRequest();
        /** @var OrderInterface|null $order */

        $order = $context->getEntity()->getInstance();
        $shipmentId = $request->query->get('shipment_id');

        $shipment = $this->container->get('sylius.repository.shipment')->find($shipmentId);
        if (!$shipment instanceof \ShipmentInterface) {
            throw new NotFoundHttpException(sprintf('The shipment with id %s has not been found', $shipmentId));
        }

        $em = $this->managerRegistry->getManager();
        $sm = $this->container->get(Factory::class)->get($shipment, "sylius_shipment");
        if ($request->get('tracking') && $sm->apply(ShipmentTransitions::TRANSITION_SHIP)) {
            $shipment->setTracking($request->get('tracking'));
            $em->persist($shipment);
            $em->flush();
            $this->container->get(Sender::class)->send(
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

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function resendOrderConfirmationEmail(AdminContext $context)
    {
        $request = $context->getRequest();
        /** @var OrderInterface|null $order */
        $order = $context->getEntity()->getInstance();

        if (!$this->container->get(CsrfTokenManager::class)->isTokenValid(new CsrfToken($order->getId(), (string) $request->query->get('_csrf_token')))) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Invalid csrf token.');
        }

        $this->container->get(Sender::class)->send(
            Emails::ORDER_CONFIRMATION_RESENT,
            [$order->getCustomer()->getEmail()],
            [
                'order' => $order,
                'channel' => $order->getChannel(),
                'localeCode' => $order->getLocaleCode(),
            ]
        );

        $this->addFlash(
            'success',
            'sylius.email.order_confirmation_resent'
        );

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function resendShipmentConfirmationEmail(AdminContext $context)
    {
        $request = $context->getRequest();

        /** @var OrderInterface|null $order */
        $order = $context->getEntity()->getInstance();
        $shipmentId = $request->query->get('shipment_id');

        if (!$this->container->get(CsrfTokenManager::class)->isTokenValid(new CsrfToken($shipmentId, (string) $request->query->get('_csrf_token')))) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Invalid csrf token.');
        }

        $shipment = $this->container->get('sylius.repository.shipment')->find($shipmentId);
        if (!$shipment instanceof \ShipmentInterface) {
            throw new NotFoundHttpException(sprintf('The shipment with id %s has not been found', $shipmentId));
        }

        $this->container->get(Sender::class)->send(
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

    private function findChannelByCodeOrFindFirst(?string $channelCode): ?ChannelInterface
    {
        if (null !== $channelCode) {
            return $this->container->get('sylius.repository.channel')->findOneByCode($channelCode);
        }

        return $this->container->get('sylius.repository.channel')->findOneBy([]);
    }

    public function statistics(AdminContext $context)
    {
        $request = $context->getRequest();
        /** @var ChannelInterface|null $channel */
        $channel = $this->findChannelByCodeOrFindFirst($request->query->has('channel') ? (string) $request->query->get('channel') : null);

        return $this->renderStatistics($channel, $context);
    }

    public function renderStatistics(ChannelInterface $channel, AdminContext $context): Response
    {
        $request = $context->getRequest();

        return $this->render(
            '@EasyShop/crud/dashboard/Statistics/_template.html.twig',
            $this->getRawData(
                $channel,
                (new \DateTime((string) $request->query->get('startDate', 'first day of january this year'))),
                (new \DateTime((string) $request->query->get('endDate', 'tomorrow'))),
                (string) $request->query->get('interval', 'month')
            )
        );
    }

    public function getRawData(ChannelInterface $channel, \DateTimeInterface $startDate, \DateTimeInterface $endDate, string $interval): array
    {
        $moneyFormatter = $this->container->get(MoneyFormatterInterface::class);
        $statisticsProvider = $this->container->get(DashboardStatisticsProviderInterface::class);
        $salesDataProvider = $this->container->get(SalesDataProviderInterface::class);

        /** @var DashboardStatistics $statistics */
        $statistics = $statisticsProvider->getStatisticsForChannelInPeriod($channel, $startDate, $endDate);

        $salesSummary = $salesDataProvider->getSalesSummary(
            $channel,
            $startDate,
            $endDate,
            Interval::{$interval}()
        );

        /** @var string $currencyCode */
        $currencyCode = $channel->getBaseCurrency()->getCode();

        return [
            'sales_summary' => [
                'intervals' => $salesSummary->getIntervals(),
                'sales' => $salesSummary->getSales(),
            ],
            'channel' => [
                'base_currency_code' => $currencyCode,
                'channel_code' => $channel->getCode(),
            ],
            'statistics' => [
                'total_sales' => $moneyFormatter->format($statistics->getTotalSales(), $currencyCode, $channel->getDefaultLocale()),
                'number_of_new_orders' => $statistics->getNumberOfNewOrders(),
                'number_of_new_customers' => $statistics->getNumberOfNewCustomers(),
                'average_order_value' => $moneyFormatter->format($statistics->getAverageOrderValue(), $currencyCode, $channel->getDefaultLocale()),
            ],
            'latest' => [
                "customer" => $this->container->get('sylius.repository.customer')->findLatest(5),
                "order" => $this->container->get('sylius.repository.order')->findLatest(5),
            ]
        ];
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.repository.order' => '?' . OrderRepositoryInterface::class,
            'sylius.repository.customer' => '?' . CustomerRepositoryInterface::class,
            'sylius.repository.channel' => '?' . ChannelRepositoryInterface::class,
            'sylius.repository.shipment' => '?' . ShipmentRepositoryInterface::class,
            'sylius.repository.payment' => '?' . PaymentRepositoryInterface::class,
            Factory::class => '?' . FactoryInterface::class,
            Sender::class => '?' . SenderInterface::class,
            CsrfTokenManager::class => '?' . CsrfTokenManagerInterface::class,
            ParameterBagInterface::class => '?' . ParameterBagInterface::class,
            DashboardStatisticsProviderInterface::class => '?' . DashboardStatisticsProviderInterface::class,
            SalesDataProviderInterface::class => '?' . SalesDataProviderInterface::class,
            MoneyFormatterInterface::class => '?' . MoneyFormatterInterface::class,
        ]);
    }
}
