<?php

namespace Adeliom\EasyShopBundle\Form\Admin;


use Adeliom\EasyEditorBundle\Form\EditorCollectionType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAssociationEntityType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAssociationsCollectionType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAttributesCollectionType;
use App\Entity\Shop\Product\Product;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

class ProductAttributesField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ALLOW_DRAG = 'allowDrag';
    public const OPTION_ALLOW_ADD = 'allowAdd';
    public const OPTION_ALLOW_DELETE = 'allowDelete';
    public const OPTION_ENTRY_IS_COMPLEX = 'entryIsComplex';
    public const OPTION_ENTRY_TYPE = 'entryType';
    public const OPTION_SHOW_ENTRY_LABEL = 'showEntryLabel';
    public const OPTION_RENDER_EXPANDED = 'renderExpanded';

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        $field = (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/collection')
            ->setFormType(ProductAttributesCollectionType::class)
            ->addCssClass('field-collection field-collection_sortable')
            ->addJsFiles('bundles/easyshop/field-produts-attributes.js')
            ->setDefaultColumns('col-12')
            ->setCustomOption(self::OPTION_ALLOW_DRAG, false)
            ->setCustomOption(self::OPTION_ALLOW_ADD, true)
            ->setCustomOption(self::OPTION_ALLOW_DELETE, true)
            ->setCustomOption(self::OPTION_ENTRY_IS_COMPLEX, null)
            ->setCustomOption(self::OPTION_SHOW_ENTRY_LABEL, false)
            ->setCustomOption(self::OPTION_RENDER_EXPANDED, false)
        ;

        return $field;
    }

    public function allowDrag(bool $allow = true): self
    {
        //$this->setCustomOption(self::OPTION_ALLOW_DRAG, $allow);

        return $this;
    }

    public function allowAdd(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_ADD, $allow);

        return $this;
    }

    public function allowDelete(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_DELETE, $allow);

        return $this;
    }

    /**
     * Set this option to TRUE if the collection items are complex form types
     * composed of several form fields (EasyAdmin applies a special rendering to make them look better).
     */
    public function setEntryIsComplex(bool $isComplex): self
    {
        $this->setCustomOption(self::OPTION_ENTRY_IS_COMPLEX, $isComplex);

        return $this;
    }

    public function showEntryLabel(bool $showLabel = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_ENTRY_LABEL, $showLabel);

        return $this;
    }

    public function renderExpanded(bool $renderExpanded = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_EXPANDED, $renderExpanded);

        return $this;
    }
}
