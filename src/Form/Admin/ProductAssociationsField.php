<?php

namespace Adeliom\EasyShopBundle\Form\Admin;


use Adeliom\EasyEditorBundle\Form\EditorCollectionType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAssociationEntityType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAssociationsCollectionType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAssociationsType;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAttributesCollectionType;
use App\Entity\Shop\Product\Product;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;

class ProductAssociationsField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ENTRY_IS_COMPLEX = 'entryIsComplex';
    public const OPTION_ENTRY_TYPE = 'entryType';
    public const OPTION_SHOW_ENTRY_LABEL = 'showEntryLabel';
    public const OPTION_RENDER_EXPANDED = 'renderExpanded';
    public const OPTION_AUTOCOMPLETE_ENDPOINT_URL = 'data-ea-autocomplete-endpoint-url';

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        $field = (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/collection')
            ->setFormType(ProductAssociationsType::class)
            ->setFormTypeOption(self::OPTION_AUTOCOMPLETE_ENDPOINT_URL, '')
            ->addCssClass('field-collection')
            ->addJsFiles('bundles/easyshop/field-produts-attributes.js')
            ->setDefaultColumns('col-12')
            ->setCustomOption(self::OPTION_ENTRY_IS_COMPLEX, null)
            ->setCustomOption(self::OPTION_SHOW_ENTRY_LABEL, false)
            ->setCustomOption(self::OPTION_RENDER_EXPANDED, false)
        ;

        return $field;
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

    public function setAutocompleteEndointUrl(string $url)
    {
        $this->setFormTypeOption(self::OPTION_AUTOCOMPLETE_ENDPOINT_URL, $url);

        return $this;
    }
}
