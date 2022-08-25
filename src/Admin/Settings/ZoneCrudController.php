<?php

namespace Adeliom\EasyShopBundle\Admin\Settings;

use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sylius\Bundle\AddressingBundle\Form\Type\CountryCodeChoiceType;
use Sylius\Bundle\AddressingBundle\Form\Type\ProvinceCodeChoiceType;
use Sylius\Bundle\AddressingBundle\Form\Type\ZoneCodeChoiceType;
use Sylius\Bundle\AddressingBundle\Form\Type\ZoneMemberType;
use Sylius\Bundle\AddressingBundle\Form\Type\ZoneTypeChoiceType;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Core\Model\Scope;

abstract class ZoneCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "zone";
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_zones")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_zone")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_zone")
            ->setPageTitle(Crud::PAGE_DETAIL, "sylius.ui.zone_details")
            ->setEntityLabelInSingular('sylius.ui.zone')
            ->setEntityLabelInPlural('sylius.ui.zones')

            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $url = $this->container->get(AdminUrlGenerator::class)->setController($this::class)->setAction(Action::NEW);

        $newZoneCountries = Action::new('zoneCountries', 'sylius.ui.zone_consisting_of_countries')->linkToUrl((clone $url)->set("zoneType", ZoneInterface::TYPE_COUNTRY)->generateUrl())->createAsGlobalAction()->setCssClass("btn btn-primary");
        $newZoneProvinces = Action::new('zoneProvinces', 'sylius.ui.zone_consisting_of_provinces')->linkToUrl((clone $url)->set("zoneType", ZoneInterface::TYPE_PROVINCE)->generateUrl())->createAsGlobalAction()->setCssClass("btn btn-primary");
        $newZoneOther = Action::new('zoneOther', 'sylius.ui.zone_consisting_of_other_zones')->linkToUrl((clone $url)->set("zoneType", ZoneInterface::TYPE_ZONE)->generateUrl())->createAsGlobalAction()->setCssClass("btn btn-primary");

        $actions = parent::configureActions($actions);

        $actions->remove(Crud::PAGE_INDEX, Action::NEW);
        $actions->add(Crud::PAGE_INDEX, $newZoneOther);
        $actions->add(Crud::PAGE_INDEX, $newZoneProvinces);
        $actions->add(Crud::PAGE_INDEX, $newZoneCountries);

        return $actions;
    }

    public function new(AdminContext $context)
    {
        global $zoneType;
        $zoneType = $context->getRequest()->query->get("zoneType");
        return parent::new($context);
    }

    public function createEntity(string $entityFqcn)
    {
        global $zoneType;
        /** @var ZoneInterface $entity */
        $entity = new $entityFqcn();
        $entity->setType($zoneType);
        $entity->setScope(Scope::ALL);
        return $entity;
    }

    public function configureFields(string $pageName): iterable
    {
        $context = $this->container->get(AdminContextProvider::class)->getContext();
        $subject = $context->getEntity();
        $zone = $subject->getInstance();

        yield FormTypeField::new("type", 'sylius.form.zone.type', ZoneTypeChoiceType::class)->setColumns(3)
            ->setFormTypeOption('disabled', 'disabled');
        yield TextField::new('code', 'sylius.ui.code')->setColumns(3);
        yield TextField::new('name', 'sylius.form.zone.name')->setColumns(3);
        yield ChoiceField::new('scope', 'sylius.form.zone.scope')->setColumns(3)->hideOnIndex()->setChoices([
            "sylius.ui.shipping" => Scope::SHIPPING,
            "sylius.ui.tax" => Scope::TAX,
            "sylius.ui.all" => Scope::ALL,
        ])->setFormTypeOption("placeholder", 'sylius.form.zone.select_scope');

        if (in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT])) {
            $entryOptions = [
                'entry_type' => $this->getZoneMemberEntryType($zone->getType()),
                'entry_options' => $this->getZoneMemberEntryOptions($zone->getType()),
            ];

            if ($zone->getType() === ZoneInterface::TYPE_ZONE) {
                $entryOptions['entry_options']['choice_filter'] = static fn(?ZoneInterface $subZone): bool => $subZone !== null && $zone->getId() !== $subZone->getId();
            }

            yield CollectionField::new("members", "sylius.ui.members")
                ->setEntryType(ZoneMemberType::class)
                ->setFormTypeOption("entry_options", $entryOptions)
                ->setFormTypeOption('allow_add', true)
                ->setFormTypeOption('allow_delete', true)
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('delete_empty', true);
        }
    }

    private function getZoneMemberEntryType(string $zoneMemberType): string
    {
        $zoneMemberEntryTypes = [
            ZoneInterface::TYPE_COUNTRY => CountryCodeChoiceType::class,
            ZoneInterface::TYPE_PROVINCE => ProvinceCodeChoiceType::class,
            ZoneInterface::TYPE_ZONE => ZoneCodeChoiceType::class,
        ];

        return $zoneMemberEntryTypes[$zoneMemberType];
    }

    private function getZoneMemberEntryOptions(string $zoneMemberType): array
    {
        $zoneMemberEntryOptions = [
            ZoneInterface::TYPE_COUNTRY => ['label' => 'sylius.form.zone.types.country'],
            ZoneInterface::TYPE_PROVINCE => ['label' => 'sylius.form.zone.types.province'],
            ZoneInterface::TYPE_ZONE => ['label' => 'sylius.form.zone.types.zone'],
        ];

        return $zoneMemberEntryOptions[$zoneMemberType];
    }
}
