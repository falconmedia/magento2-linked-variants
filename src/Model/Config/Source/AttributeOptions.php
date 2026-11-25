<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

/**
 * User defined product attributes that hold options (dropdown, multiple select), for choosing one in the admin.
 *
 * Configured per field as a virtual type in adminhtml/di.xml, each with its own label for the empty option.
 */
class AttributeOptions implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param string $emptyLabel untranslated label of the option that selects no attribute
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly string $emptyLabel = ''
    ) {
    }

    /**
     * Returns the attribute codes with their admin labels, with the empty option first.
     *
     * @return array<int, array{value: string, label: Phrase|string}>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __($this->emptyLabel)]];
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('frontend_input', ['in' => ['select', 'multiselect']])
            ->addFieldToFilter('is_user_defined', ['eq' => 1])
            ->setOrder('frontend_label', 'ASC');
        foreach ($collection as $attribute) {
            $code = (string) $attribute->getAttributeCode();
            $label = trim((string) $attribute->getFrontendLabel());
            $options[] = ['value' => $code, 'label' => ($label !== '' ? $label . ' ' : '') . '(' . $code . ')'];
        }

        return $options;
    }
}
