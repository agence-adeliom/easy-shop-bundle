<?php

namespace Adeliom\EasyShopBundle\Admin\Products;

use Adeliom\EasyFieldsBundle\Admin\Field\AssociationField;
use Adeliom\EasyFieldsBundle\Admin\Field\FormTypeField;
use Adeliom\EasyFieldsBundle\Admin\Field\SortableCollectionField;
use Adeliom\EasyFieldsBundle\Admin\Field\TranslationField;
use Adeliom\EasyShopBundle\Admin\SyliusCrudController;
use Adeliom\EasyShopBundle\Form\Admin\ProductAssociationsField;
use Adeliom\EasyShopBundle\Form\Admin\ProductAttributesField;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ChannelCollectionType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductImageType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductTaxonType;
use App\Entity\Shop\Product\Product;
use App\Entity\Shop\Taxonomy\Taxon;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Pagerfanta\Pagerfanta;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductGenerateVariantsType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductOptionChoiceType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantType;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingCategoryChoiceType;
use Sylius\Bundle\TaxationBundle\Form\Type\TaxCategoryChoiceType;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

abstract class ProductCrudController extends SyliusCrudController
{
    public static function getResource(): string
    {
        return "product";
    }

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'sylius.factory.product' => '?'.ProductFactoryInterface::class,
            'sylius.factory.product_variant' => '?'.ProductVariantFactoryInterface::class,
            'sylius.repository.product_variant' => '?'.ProductVariantRepositoryInterface::class,
            'sylius.manager.product' => '?'.EntityManagerInterface::class,
            'sylius.manager.product_variant' => '?'.EntityManagerInterface::class,
            ParameterBagInterface::class => '?'.ParameterBagInterface::class,
            RouterInterface::class => '?'.RouterInterface::class,
        ]);
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@EasyEditor/form/editor_widget.html.twig')
            ->addFormTheme('@EasyFields/form/association_widget.html.twig')
            ->addFormTheme('@EasyFields/form/sortable_widget.html.twig')
            ->addFormTheme('@EasyFields/form/choice_mask_widget.html.twig')
            ->addFormTheme('@EasyFields/form/translations_widget.html.twig')
            ->addFormTheme('@EasyCommon/crud/custom_panel.html.twig')
            ->addFormTheme('@EasyMedia/form/easy-media.html.twig')
            ->addFormTheme('@EasyShop/SyliusFormTheme.html.twig')
            ->setPageTitle(Crud::PAGE_INDEX, "sylius.ui.manage_products")
            ->setPageTitle(Crud::PAGE_NEW, "sylius.ui.new_product")
            ->setPageTitle(Crud::PAGE_EDIT, "sylius.ui.edit_product")
            ->setPageTitle(Crud::PAGE_DETAIL, function ($entity) {
                return (string) $entity;
            })
            ->setEntityLabelInSingular('sylius.ui.product')
            ->setEntityLabelInPlural('sylius.ui.products')
            ->setFormOptions([
                'validation_groups' => ['Default', 'sylius']
            ])

            ->showEntityActionsAsDropdown();
    }

    public function configureActions(Actions $actions): Actions
    {
        $url = $this->get(AdminUrlGenerator::class)->setController(get_class($this))->setAction(Action::NEW);

        $actions = parent::configureActions($actions);
        $addTypes = [ 'simple_product', 'configurable_product' ];

        foreach ($addTypes as $key ) {
            $newAdd = Action::new($key, 'sylius.ui.'.$key)->linkToUrl((clone $url)->set("productType", $key))->createAsGlobalAction()->setCssClass("btn btn-primary");
            $actions->add(Crud::PAGE_INDEX, $newAdd);
        }
        $actions->remove(Crud::PAGE_INDEX, Action::NEW);

        $manageVariant = Action::new("manage_variant", 'sylius.ui.manage_variants')->linkToCrudAction("manageVariants");
        $actions->add(Crud::PAGE_INDEX, $manageVariant);

        $manageVariantEdit = Action::new("manage_variant", 'sylius.ui.manage_variants')->linkToCrudAction("manageVariants")->setCssClass("btn btn-secondary");
        $actions->add(Crud::PAGE_EDIT, $manageVariantEdit);

        $viewProduct = Action::new('viewProduct', 'Voir le produit', 'fa fa-eye')->linkToUrl(function (ProductInterface $product) {
            $slug = $product->getSlug();
            if($taxon = $product->getMainTaxon()){
                $slug = $taxon->getTree()."/".$slug;
            }
            return $this->get(RouterInterface::class)->generate('sylius_shop_product_show', [
                'slug' => $slug
            ]);
        })->setHtmlAttributes(["target" => "_blank"]);

        $viewProductButton = Action::new('viewProduct', 'Voir le produit', 'fa fa-eye')->linkToUrl(function (ProductInterface $product) {
            $slug = $product->getSlug();
            if($taxon = $product->getMainTaxon()){
                $slug = $taxon->getTree()."/".$slug;
            }
            return $this->get(RouterInterface::class)->generate('sylius_shop_product_show', [
                'slug' => $slug
            ]);
        })->setHtmlAttributes(["target" => "_blank"])->setCssClass("btn btn-info");
        $actions->add(Crud::PAGE_INDEX, $viewProduct);
        $actions->add(Crud::PAGE_DETAIL, $viewProductButton);
        $actions->add(Crud::PAGE_EDIT, $viewProductButton);

        return $actions;
    }

    public function informationFields(string $pageName, AdminContext $context): iterable
    {
        yield FormField::addPanel("sylius.ui.details")->collapsible()->renderCollapsed(false);
        yield TextField::new('thumbnail')->setLabel('sylius.ui.image')->setVirtual(true)->onlyOnIndex()->setTemplatePath("@EasyShop/crud/Common/thumbnail.html.twig");
        yield TextField::new('code')->setLabel('sylius.ui.code');
        yield TextField::new('name')->setLabel('sylius.ui.name')->onlyOnIndex();
        yield BooleanField::new('enabled')->setLabel('sylius.ui.enabled')->renderAsSwitch(in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT]));
        if ($this->isSimpleProduct()) {
            yield BooleanField::new('variant.shippingRequired')->setLabel('sylius.form.variant.shipping_required');
        }else {
            yield FormTypeField::new('options', 'sylius.form.product.options', ProductOptionChoiceType::class)
                ->setFormTypeOptions([
                    'required' => false,
                    'multiple' => true,
                    "attr" => ["data-ea-widget" => "ea-autocomplete"]
                ])
                ->hideOnIndex();
            yield ChoiceField::new('variantSelectionMethod')->setLabel('sylius.form.product.variant_selection_method')->setRequired(true)
                ->setChoices(array_flip(\Sylius\Component\Core\Model\Product::getVariantSelectionMethodLabels()))
                ->hideOnIndex();
        }

        yield FormTypeField::new('channels', 'sylius.form.product.channels', ChannelChoiceType::class)
            ->hideOnIndex()
            ->setFormTypeOption("multiple", true)
            ->setFormTypeOption("expanded", true);
    }

    public function mediaFields(string $pageName, AdminContext $context): iterable
    {
        yield FormField::addPanel("sylius.ui.images")->collapsible()->renderCollapsed();
        yield SortableCollectionField::new('images', false)
            ->hideOnIndex()
            ->setEntryType(ProductImageType::class)
            ->setFormTypeOption('hide_title', true)
            ->setFormTypeOption('entry_options', [
                'product' => $context->getEntity()->getInstance()
            ])
            ->allowAdd()
            ->allowDrag()
            ->allowDelete();
    }

    public function inventoryFields(string $pageName, AdminContext $context): iterable
    {
        if ($this->isSimpleProduct()) {
            yield FormField::addPanel("sylius.ui.inventory")->collapsible()->renderCollapsed();
            yield NumberField::new('variant.onHand')->setLabel('sylius.form.variant.on_hand');
            yield BooleanField::new('variant.tracked')->setLabel('sylius.form.variant.tracked')->setHelp('sylius.form.variant.tracked_help');
        }
    }

    public function shippingFields(string $pageName, AdminContext $context): iterable
    {
        if ($this->isSimpleProduct()) {
            yield FormField::addPanel("sylius.ui.shipping")->collapsible()->renderCollapsed();

            yield FormTypeField::new('variant.shippingCategory', 'sylius.form.product_variant.shipping_category', ShippingCategoryChoiceType::class)
                ->setFormTypeOptions(["attr" => ["data-ea-widget" => "ea-autocomplete"]]);
            yield NumberField::new('variant.width','sylius.form.variant.width')->setColumns(4);
            yield NumberField::new('variant.height','sylius.form.variant.height')->setColumns(4);
            yield NumberField::new('variant.depth','sylius.form.variant.depth')->setColumns(4);
            yield NumberField::new('variant.weight','sylius.form.variant.weight')->setColumns(12);
        }
    }

    public function taxesFields(string $pageName, AdminContext $context): iterable
    {
        if ($this->isSimpleProduct()) {
            yield FormField::addPanel("sylius.ui.taxes")->collapsible()->renderCollapsed();

            yield FormTypeField::new('variant.taxCategory', 'sylius.form.product_variant.tax_category', TaxCategoryChoiceType::class)
                ->setFormTypeOptions(["attr" => ["data-ea-widget" => "ea-autocomplete"]]);
        }
    }

    public function pricingFields(string $pageName, AdminContext $context): iterable
    {
        if ($this->isSimpleProduct()) {
            yield FormField::addPanel("sylius.ui.pricing")->collapsible()->renderCollapsed();

            yield FormTypeField::new('virtualVariantChannelPricing', 'sylius.form.variant.price', ChannelCollectionType::class)
                ->setFormTypeOptions(["label" => false]);
        }
    }

    public function metaFields(string $pageName, AdminContext $context): iterable
    {
        $fieldsConfig = [
            'name' => [
                'field_type' => TextType::class,
                'required' => true,
            ],
            'slug' => [
                'field_type' => TextType::class,
                'required' => true,
            ],
            'description' => [
                'field_type' => TextareaType::class,
                'required' => false,
            ],
            'shortDescription' => [
                'field_type' => TextareaType::class,
                'required' => false,
            ],
            'metaKeywords' => [
                'field_type' => TextType::class,
                'required' => false,
            ],
            'metaDescription' => [
                'field_type' => TextareaType::class,
                'required' => false,
            ],
        ];

        yield FormField::addPanel("Contenus")->collapsible()->renderCollapsed();
        yield TranslationField::new("translations", false, $fieldsConfig);
    }

    public function taxonomyFields(string $pageName, AdminContext $context): iterable
    {
        yield FormField::addPanel("Taxonomy")->collapsible()->renderCollapsed();
        yield AssociationField::new('mainTaxon', 'sylius.ui.main_taxon')
            ->setFormTypeOption('class', $this->get(ParameterBagInterface::class)->get('sylius.model.taxon.class'))
            ->setFormTypeOption('choice_value', "code")
            ->setFormTypeOption('choice_label', function ($item) {
                return $item->getTree(" / ", true);
            });

        yield AssociationField::new('productTaxons')
            ->setFormType(ProductTaxonType::class)
            ->setFormTypeOption('class', $this->get(ParameterBagInterface::class)->get('sylius.model.taxon.class'))
            ->setFormTypeOption('product', $this->get(AdminContextProvider::class)->getContext()->getEntity()->getInstance())
            ->hideOnIndex();
    }

    public function attributesFields(string $pageName, AdminContext $context): iterable
    {
        yield FormField::addPanel("Attributes")->collapsible()->renderCollapsed();
        yield ProductAttributesField::new("attributes", false)
            ->hideOnIndex();
    }

    public function associationFields(string $pageName, AdminContext $context): iterable
    {
        yield FormField::addPanel("Associations")->collapsible()->renderCollapsed();
        yield ProductAssociationsField::new("associations", false)
            ->hideOnIndex();
    }

    public function configureFields(string $pageName): iterable
    {
        $context = $this->get(AdminContextProvider::class)->getContext();

        yield from $this->informationFields($pageName, $context);
        yield from $this->mediaFields($pageName, $context);
        yield from $this->pricingFields($pageName, $context);
        yield from $this->metaFields($pageName, $context);
        yield from $this->inventoryFields($pageName, $context);
        yield from $this->shippingFields($pageName, $context);
        yield from $this->taxesFields($pageName, $context);
        yield from $this->taxonomyFields($pageName, $context);
        yield from $this->attributesFields($pageName, $context);
        yield from $this->associationFields($pageName, $context);

        if ($this->isSimpleProduct()) {

//            yield FormField::addPanel("Variant")->collapsible()->renderCollapsed();
//            yield FormTypeField::new('variant', '', ProductVariantType::class)
//                ->setFormTypeOptions([
//                    'property_path' => 'variants[0]',
//                    'constraints' => [
//                        new Valid(),
//                    ]
//                ])
//                ->setTemplatePath("@EasyShop/form/admin_product.html.twig")
        }


    }

    public function new(AdminContext $context)
    {
        global $productType;
        $productType = $context->getRequest()->query->get("productType");
        return parent::new($context);
    }

    public function createEntity(string $entityFqcn)
    {
        if($this->isSimpleProduct()){
            return $this->get('sylius.factory.product')->createWithVariant();
        }
        return $this->get('sylius.factory.product')->createNew();

    }

    protected function isSimpleProduct(): bool
    {
        global $productType;
        /**
         * @var ProductInterface $entity
         */
        $entity = $this->get(AdminContextProvider::class)->getContext()->getEntity()->getInstance();
        return ((!empty($productType) && $productType === 'simple_product') || (!empty($entity) && $entity->getId() && $entity->isSimple()));
    }

    public function manageVariants(AdminContext $context): Response
    {
        /** @var \Sylius\Component\Product\Model\Product $product */
        $product = $context->getEntity()->getInstance();

        return $this->render('@EasyShop/crud/variant/list.html.twig', [
            'product' => $product
        ]);
    }

    public function createVariant(AdminContext $context): Response
    {
        $variant = $this->get('sylius.factory.product_variant')->createForProduct($context->getEntity()->getInstance());
        $form = $this->createForm(ProductVariantType::class, $variant);

        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $variant = $form->getData();
            $this->get('sylius.manager.product_variant')->persist($variant);
            $this->get('sylius.manager.product_variant')->flush();
            $url = $this->get(AdminUrlGenerator::class)->setController(get_class($this))->setEntityId($variant->getProduct()->getId())->setAction("manageVariants")->generateUrl();
            return $this->redirect($url);
        }

        return $this->render('@EasyShop/crud/variant/new_variant.html.twig', [
            'product' => $context->getEntity()->getInstance(),
            'form' => $form->createView()
        ]);
    }

    public function editVariant(AdminContext $context): Response
    {
        $variant = $this->get('sylius.repository.product_variant')->find($context->getRequest()->query->get("variantId"));
        if (!($variant instanceof ProductVariantInterface)){
            throw new NotFoundHttpException();
        }
        $this->get("event_dispatcher")->dispatch(new GenericEvent($variant) , sprintf("sylius.%s.initialize_update", "product_variant"));
        $form = $this->createForm(ProductVariantType::class, $variant);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $variant = $form->getData();
            $this->get('sylius.manager.product_variant')->persist($variant);
            $this->get('sylius.manager.product_variant')->flush();
            $url = $this->get(AdminUrlGenerator::class)->setController(get_class($this))->setEntityId($variant->getProduct()->getId())->setAction("manageVariants")->generateUrl();
            return $this->redirect($url);
        }

        return $this->render('@EasyShop/crud/variant/edit_variant.html.twig', [
            'product' => $context->getEntity()->getInstance(),
            'form' => $form->createView()
        ]);
    }

    public function deleteVariant(AdminContext $context): Response
    {
        $variant = $this->get('sylius.repository.product_variant')->find($context->getRequest()->query->get("variantId"));
        if (!($variant instanceof ProductVariantInterface)){
            throw new NotFoundHttpException();
        }

        $this->get('sylius.manager.product_variant')->remove($variant);
        $this->get('sylius.manager.product_variant')->flush();

        $url = $this->get(AdminUrlGenerator::class)->setController(get_class($this))->setEntityId($variant->getProduct()->getId())->setAction("manageVariants")->generateUrl();
        return $this->redirect($url);
    }

    public function batchDeleteVariants(AdminContext $context): Response
    {
        foreach ($context->getRequest()->get("batchActionEntityIds", []) as $i){
            $coupon = $this->get('sylius.repository.product_variant')->find($i);
            if (!$coupon) {
                continue;
            }
            $this->get('sylius.manager.product_variant')->remove($coupon);
            $this->get('sylius.manager.product_variant')->flush();
        }
        $url = $this->get(AdminUrlGenerator::class)->setController(get_class($this))->setEntityId($coupon->getPromotion()->getId())->setAction("manageVariants")->generateUrl();
        return $this->redirect($url);
    }

    public function generateVariants(AdminContext $context): Response
    {
        $product = $context->getEntity()->getInstance();
        $form = $this->createForm(ProductGenerateVariantsType::class, $product);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();
            $this->get('sylius.manager.product')->persist($product);
            $this->get('sylius.manager.product')->flush();

            $url = $this->get(AdminUrlGenerator::class)->setController(get_class($this))->setEntityId($context->getEntity()->getPrimaryKeyValue())->setAction("manageVariants")->generateUrl();
            return $this->redirect($url);
        }

        return $this->render('@EasyShop/crud/variant/generate_variants.html.twig', [
            'product' => $context->getEntity()->getInstance(),
            'form' => $form->createView()
        ]);
    }

    public function manageStock(AdminContext $context): Response
    {
        /** @var Pagerfanta $tracked */
        $tracked =$this->get('sylius.repository.product_variant')->createPaginator([
            "tracked" => true
        ]);
        $tracked->setMaxPerPage(25);
        $tracked->setCurrentPage($context->getRequest()->query->get("page", 1));

        return $this->render('@EasyShop/crud/variant/stocks.html.twig', [
            'tracked' => $tracked,
        ]);
    }
}
