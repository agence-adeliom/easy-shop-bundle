<?php

namespace Adeliom\EasyShopBundle\Admin\Customer;

use Adeliom\EasyFieldsBundle\Admin\Field\ChoiceMaskField;
use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use App\Entity\Shop\Customer\Customer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sylius\Bundle\CoreBundle\Form\Type\User\ShopUserType;
use Sylius\Bundle\CoreBundle\Security\UserImpersonatorInterface;
use Sylius\Bundle\CustomerBundle\Form\Type\CustomerGroupChoiceType;
use Sylius\Bundle\CustomerBundle\Form\Type\GenderType;
use Sylius\Component\Core\Customer\Statistics\CustomerStatisticsProviderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Customer\Model\CustomerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class CustomerCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "customer";
    }

    public function createEntity(string $entityFqcn)
    {
        $customer = $this->get('sylius.factory.customer')->createNew();
        $customer->setUser($this->get('sylius.factory.shop_user')->createNew());
        return $customer;
    }

    public function configureActions(Actions $actions): Actions
    {
        $showOrders = Action::new("show_orders", 'sylius.ui.show_orders')->linkToCrudAction("showOrders");
        $actions->add(Crud::PAGE_INDEX, $showOrders);

        $showOrdersEdit = Action::new("show_orders", 'sylius.ui.show_orders')->linkToCrudAction("showOrders")->setCssClass("btn btn-secondary");
        $actions->add(Crud::PAGE_EDIT, $showOrdersEdit);
        $actions->add(Crud::PAGE_DETAIL, $showOrdersEdit);

        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        return $actions;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyShop/SyliusFormTheme.html.twig')
            ->addFormTheme('@EasyFields/form/choice_mask_widget.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_customers")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_customer")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_customer")
            ->setPageTitle(Crud::PAGE_DETAIL, function ($entity) {
                return sprintf('%s<br/><small>%s</small>', $entity->getFullName(), $entity->getEmail());
            })
            ->setEntityLabelInSingular('sylius.ui.customer')
            ->setEntityLabelInPlural('sylius.ui.customers')
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ->overrideTemplate('crud/detail', '@EasyShop/crud/customer/detail.html.twig')

            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $context = $this->get(AdminContextProvider::class)->getContext();
        $subject = $context->getEntity();

        yield FormField::addPanel("sylius.ui.customer_details");
        yield TextField::new("firstName", 'sylius.form.customer.first_name')->setRequired(true);
        yield TextField::new("lastName", 'sylius.form.customer.last_name')->setRequired(true);
        yield EmailField::new("email", 'sylius.form.customer.email')->setRequired(true);
        yield FormTypeField::new("group", 'sylius.form.customer.group', CustomerGroupChoiceType::class)->hideOnIndex()
            ->setRequired(false)
            ->setFormTypeOption("attr", ["data-ea-widget" => "ea-autocomplete"])
        ;

        yield FormField::addPanel("sylius.ui.extra_information");
        yield ChoiceField::new("gender", 'sylius.form.customer.gender')->hideOnIndex()
            ->setRequired(true)
            ->setFormType(GenderType::class)
            ->setChoices([
                'sylius.gender.unknown' => CustomerInterface::UNKNOWN_GENDER,
                'sylius.gender.male' => CustomerInterface::MALE_GENDER,
                'sylius.gender.female' => CustomerInterface::FEMALE_GENDER,
            ])
            ->setFormTypeOption('empty_data', CustomerInterface::UNKNOWN_GENDER)
        ;
        yield DateField::new("birthday", 'sylius.form.customer.birthday')->hideOnIndex()
            ->setFormType(BirthdayType::class)
        ;
        yield TelephoneField::new("phoneNumber", 'sylius.form.customer.phone_number')->hideOnIndex();
        yield BooleanField::new("subscribedToNewsletter", 'sylius.form.customer.subscribed_to_newsletter')->hideOnIndex()->renderAsSwitch(in_array($pageName, [Crud::PAGE_EDIT, Crud::PAGE_NEW]));

        yield FormField::addPanel("sylius.ui.account_credentials");
        yield ChoiceMaskField::new("createUser", 'sylius.ui.customer_can_login_to_the_store')->hideOnIndex()
            ->onlyOnForms()
            ->setRequired(true)
            ->setChoices(array_flip([
                "no" => 'sylius.ui.no_label',
                "yes" => 'sylius.ui.yes_label',
            ]))
            ->setFormTypeOption('data', $subject->getInstance() ? ($subject->getInstance()->getUser() ? "yes" : 'no') : "no")
            ->setFormTypeOption('required', false)
            ->setFormTypeOption('placeholder', null)
            ->setFormTypeOption('empty_data', "no")
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('mapped', false)
            ->setMap([
                "yes" => ["user"],
                "no" => [],
            ])
        ;
        yield FormTypeField::new("user", false, ShopUserType::class)->hideOnIndex()
            ->setRequired(false)
            ->setFormTypeOption("label", false)
        ;

        yield DateTimeField::new('user.createdAt', 'sylius.ui.registration_date')->onlyOnIndex();
        yield BooleanField::new('user.enabled', 'sylius.form.user.enabled')->renderAsSwitch(false)->onlyOnIndex();
        yield BooleanField::new('user.verifiedAt', 'sylius.form.user.verified')->renderAsSwitch(false)->onlyOnIndex();
    }

    protected function processUploadedFiles(FormInterface $form): void
    {
        parent::processUploadedFiles($form);
        global $createUser;
        $createUser = false;
        if($form->getData() instanceof CustomerInterface){
            $createUser = $form->get("createUser")->getData() == "yes";
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        global $createUser;
        if(!$createUser){
            if($entityInstance->getUser() && $entityInstance->getUser()->getId()){
                $user = $entityInstance->getUser();
                $user->setEnabled(false);
                $entityInstance->setUser($user);
            }else{
                $entityInstance->setUser(null);
            }
        }
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function detail(AdminContext $context)
    {
        $request = $context->getRequest();
        $customerStatistics = $this->get('sylius.customer_statistics_provider')->getCustomerStatistics($context->getEntity()->getInstance());
        $request->attributes->set("statistics", $customerStatistics);

        return parent::detail($context);
    }

    public function impersonate(AdminContext $context)
    {
        if (!$this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED);
        }

        $customer = $context->getEntity()->getInstance();
        /** @var ShopUserInterface $user */
        if($user = $customer->getUser()){
            $this->get('sylius.security.shop_user_impersonator')->impersonate($user);
            $this->addFlash('success', $this->get(TranslatorInterface::class)->trans(
                'sylius.customer.impersonate',
                [
                    '%name%' => $user->getEmailCanonical(),
                ],
                'flashes'
            ));
        }

        return new RedirectResponse($context->getRequest()->headers->get('referer'));
    }

    public function showOrders(AdminContext $context){
        $orderCrud = $context->getCrudControllers()->findCrudFqcnByEntityFqcn($this->get(ParameterBagInterface::class)->get('sylius.model.order.class'));
        return $this->redirect(
            $this->get(AdminUrlGenerator::class)
                ->setController($orderCrud)
                ->setAction(Action::INDEX)
                ->set('filters', ['customer' => ['value' => $context->getEntity()->getPrimaryKeyValue(), 'comparison' => '=']])
                ->generateUrl()
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.customer_statistics_provider' => '?'.CustomerStatisticsProviderInterface::class,
            'sylius.factory.customer' => '?'.FactoryInterface::class,
            'sylius.factory.shop_user' => '?'.FactoryInterface::class,
            'sylius.security.shop_user_impersonator' => '?'.UserImpersonatorInterface::class,
            TranslatorInterface::class => '?'.TranslatorInterface::class,
            ParameterBagInterface::class => '?'.ParameterBagInterface::class,
        ]);
    }

}
