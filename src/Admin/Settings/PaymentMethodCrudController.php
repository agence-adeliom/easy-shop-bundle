<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

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
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\PayumBundle\Form\Type\GatewayConfigType;
use Sylius\Component\Core\Factory\PaymentMethodFactoryInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class PaymentMethodCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "payment_method";
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.custom_factory.payment_method' => '?' . PaymentMethodFactoryInterface::class,
            TranslatorInterface::class => '?' . TranslatorInterface::class,
            ParameterBagInterface::class => '?' . ParameterBagInterface::class,
        ]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyFields/form/translations_widget.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_payment_methods_available_to_your_customers")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_payment_method")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_payment_method")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.payment_method")
            ->setEntityLabelInSingular('sylius.ui.payment_method')
            ->setEntityLabelInPlural('sylius.ui.payment_methods')
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $url = $this->get(AdminUrlGenerator::class)->setController($this::class)->setAction(Action::NEW);
        $actions = parent::configureActions($actions);

        foreach ($this->get(ParameterBagInterface::class)->get('sylius.gateway_factories') as $gatewayFactory => $gatewayFactoryName) {
            $newAdd = Action::new($gatewayFactoryName, $gatewayFactoryName)->linkToUrl((clone $url)->set("gatewayFactory", $gatewayFactory)->set("gatewayFactoryName", $gatewayFactoryName)->generateUrl())->createAsGlobalAction()->setCssClass("btn btn-primary");
            $actions->add(Crud::PAGE_INDEX, $newAdd);
        }

        $actions->remove(Crud::PAGE_INDEX, Action::NEW);

        return $actions;
    }

    public function new(AdminContext $context)
    {
        global $gatewayFactory, $gatewayFactoryName;
        $gatewayFactory = $context->getRequest()->query->get("gatewayFactory");
        $gatewayFactoryName = $context->getRequest()->query->get("gatewayFactoryName");
        return parent::new($context);
    }

    public function createEntity(string $entityFqcn)
    {
        global $gatewayFactory, $gatewayFactoryName;
        $syliusPaymentMethod = $this->get('sylius.custom_factory.payment_method')->createWithGateway($gatewayFactory);
        $gatewayconfig = $syliusPaymentMethod->getGatewayConfig();
        $gatewayconfig->setGatewayName($gatewayFactoryName);
        /**
         * @var PaymentMethodInterface $paymentMethod
         */
        $paymentMethod = new $entityFqcn();
        $paymentMethod->setGatewayConfig($gatewayconfig);
        $paymentMethod->initializeTranslationsCollection();
        return $paymentMethod;
    }

    public function configureFields(string $pageName): iterable
    {
        $context = $this->get(AdminContextProvider::class)->getContext();
        $subject = $context->getEntity()->getInstance();

        yield FormField::addPanel("sylius.ui.details")->collapsible()->renderCollapsed(false);

        yield TextField::new('code', "sylius.ui.code")
            ->setFormTypeOption('disabled', ($pageName == Crud::PAGE_EDIT ? 'disabled' : ''))
            ->setColumns(6);

        yield IntegerField::new('position', 'sylius.form.payment_method.position')
            ->setColumns(6);

        yield BooleanField::new('enabled', 'sylius.form.payment_method.enabled')->renderAsSwitch(in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT]));

        yield FormTypeField::new('channels', 'sylius.form.payment_method.channels', ChannelChoiceType::class)->hideOnIndex()
            ->setFormTypeOptions(['multiple' => true, 'expanded' => true, "attr" => ["data-ea-widget" => "ea-autocomplete"]]);

        yield FormField::addPanel("sylius.ui.gateway_configuration")->collapsible()->renderCollapsed(false);

        yield TextField::new('gatewayConfig.gatewayName')
            ->setLabel('sylius.form.gateway_config.type')
            ->formatValue(fn($value) => $this->get(TranslatorInterface::class)->trans($value))
            ->setFormTypeOption('disabled', 'disabled')->onlyOnIndex();

        if ($subject) {
            yield FormTypeField::new('gatewayConfig', 'sylius.ui.gateway_configuration', GatewayConfigType::class)->hideOnIndex()
                ->setFormTypeOptions(['data' => $subject->getGatewayConfig()])->onlyOnForms();
        }


        yield FormField::addPanel("sylius.ui.translations")->collapsible()->renderCollapsed(false);

        $fieldsConfig = [
            'name' => [
                'field_type' => TextType::class,
                'label' => 'sylius.ui.label',
                'required' => true,
            ],
            'description' => [
                'field_type' => TextareaType::class,
                'label' => 'sylius.ui.description',
                'required' => false,
            ],
            'instructions' => [
                'field_type' => TextareaType::class,
                'help' => 'sylius.ui.the_instructions_below_will_be_displayed_to_the_customer',
                'required' => false,
            ],
        ];

        yield TranslationField::new("translations", 'sylius.ui.content', $fieldsConfig)->hideOnIndex();
    }
}
