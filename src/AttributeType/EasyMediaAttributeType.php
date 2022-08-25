<?php

declare(strict_types=1);

namespace Adeliom\EasyShopBundle\AttributeType;

use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Sylius\Component\Attribute\Model\AttributeValueInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class EasyMediaAttributeType implements AttributeTypeInterface
{
    /**
     * @var string
     */
    public const TYPE = 'easy_media';

    public function getStorageType(): string
    {
        return AttributeValueInterface::STORAGE_TEXT;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function validate(
        AttributeValueInterface $attributeValue,
        ExecutionContextInterface $context,
        array $configuration
    ): void {
        if (!isset($configuration['required']) && (!isset($configuration['media']))) {
            return;
        }

        $value = $attributeValue->getValue();

        foreach ($this->getValidationErrors($context, $value, $configuration) as $error) {
            $context
                ->buildViolation($error->getMessage())
                ->atPath('value')
                ->addViolation()
            ;
        }
    }

    private function getValidationErrors(
        ExecutionContextInterface $context,
        ?string $value,
        array $validationConfiguration
    ): ConstraintViolationListInterface {
        $validator = $context->getValidator();
        $constraints = [];

        if (isset($validationConfiguration['required'])) {
            $constraints = [new NotBlank([])];
        }

        return $validator->validate(
            $value,
            $constraints
        );
    }
}
