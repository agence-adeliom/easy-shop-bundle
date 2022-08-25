<?php

namespace Adeliom\EasyShopBundle\Form\Listener;

use Adeliom\EasyShopBundle\Entity\ProductAttributesCollectionEntry;
use Adeliom\EasyShopBundle\Entity\ProductAttributesCollectionEntryValues;
use Adeliom\EasyShopBundle\Form\Type\ProductBundle\ProductAttributesCollectionEntryType;
use App\Entity\Shop\Product\ProductAttributeValue;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class ProductAttributesResizeFormListener extends \Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener
{
    private $deleteEmpty;

    /**
     * @param bool          $allowAdd    Whether children could be added to the group
     * @param bool          $allowDelete Whether children could be removed from the group
     * @param bool|callable $deleteEmpty
     */
    public function __construct(protected string $type, private readonly RepositoryInterface $productAttributesTypeRepository, protected array $options = [], protected bool $allowAdd = false, protected bool $allowDelete = false, $deleteEmpty = false)
    {
        $this->deleteEmpty = $deleteEmpty;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT => ['onSubmit', 50],
            //FormEvents::POST_SUBMIT => "postSubmit",
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }


        $newArray = [];

        if (!($data instanceof PersistentCollection)) {
            return new ArrayCollection();
        }

        /** @var \Sylius\Component\Product\Model\ProductAttributeValue $entry */
        foreach ($data as $position => $entry) {
            if (!isset($newArray[$entry->getAttribute()->getCode()])) {
                $newArray[$entry->getAttribute()->getCode()] = [
                    'attribute' => $entry->getAttribute()->getCode(),
                    'position' => $position,
                ];
            }

            if ($entry->getAttribute()->isTranslatable()) {
                $newArray[$entry->getAttribute()->getCode()]["value__" . $entry->getLocaleCode()] = $entry->getValue();
            } else {
                $newArray[$entry->getAttribute()->getCode()]["value"] = $entry->getValue();
            }
        }

        $data = array_values($newArray);
        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            $attribute = $this->productAttributesTypeRepository->findOneBy(["code" => $value["attribute"]]);
            $form->add($name, ProductAttributesCollectionEntryType::class, array_replace($this->options, [
                'property_path' => '[' . $name . ']',
                'attribute' => $attribute,
                'label' => $attribute->getName(),
                //'data' => $value
            ]));
        }
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!\is_array($data)) {
            $data = [];
        }

        // Remove all empty rows
        if ($this->allowDelete) {
            foreach ($form as $name => $child) {
                if (!isset($data[$name])) {
                    $form->remove($name);
                }
            }
        }

        // Add all additional rows
        if ($this->allowAdd) {
            foreach ($data as $name => $value) {
                if (!$form->has($name)) {
                    $attribute = $this->productAttributesTypeRepository->findOneBy(["code" => $value["attribute"]]);
                    $form->add($name, ProductAttributesCollectionEntryType::class, array_replace($this->options, [
                        'property_path' => '[' . $name . ']',
                        'attribute' => $attribute,
                        'label' => $attribute->getName(),
                        //'data' => $value
                    ]));
                }
            }
        }
    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var ArrayCollection $data */
        $data = $event->getData();

        // At this point, $data is an array or an array-like object that already contains the
        // new entries, which were added by the data mapper. The data mapper ignores existing
        // entries, so we need to manually unset removed entries in the collection.

        if (null === $data) {
            $data = [];
        }

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        if ($this->deleteEmpty) {
            $previousData = $form->getData();
            /** @var FormInterface $child */
            foreach ($form as $name => $child) {
                if (!$child->isValid() || !$child->isSynchronized()) {
                    continue;
                }

                $isNew = !isset($previousData[$name]);
                $isEmpty = \is_callable($this->deleteEmpty) ? ($this->deleteEmpty)($child->getData()) : $child->isEmpty();

                // $isNew can only be true if allowAdd is true, so we don't
                // need to check allowAdd again
                if ($isEmpty && ($isNew || $this->allowDelete)) {
                    unset($data[$name]);
                    $form->remove($name);
                }
            }
        }

        // The data mapper only adds, but does not remove items, so do this
        // here
        if ($this->allowDelete) {
            $toDelete = [];

            foreach ($data as $name => $child) {
                if (!$form->has($name)) {
                    $toDelete[] = $name;
                }
            }

            foreach ($toDelete as $name) {
                unset($data[$name]);
            }
        }

        $event->setData($data);
    }
}
