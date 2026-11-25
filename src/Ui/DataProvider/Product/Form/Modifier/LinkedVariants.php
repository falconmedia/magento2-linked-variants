<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Ui\DataProvider\Product\Form\Modifier;

use FalconMedia\LinkedVariants\Model\LinkType;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Related;
use Magento\Ui\Component\Form\Fieldset;

/**
 * Adds a "Linked Variants" grid to the related products section of the admin product form.
 */
class LinkedVariants extends Related
{
    private const SORT_ORDER = 40;

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive($meta, [
            static::GROUP_RELATED => [
                'children' => [
                    $this->scopePrefix . LinkType::CODE => $this->getFieldset(),
                ],
            ],
        ]);

        return $meta;
    }

    /**
     * @return string[]
     */
    protected function getDataScopes()
    {
        return [LinkType::CODE];
    }

    /**
     * @return array<string, mixed>
     */
    private function getFieldset(): array
    {
        $scope = LinkType::CODE;
        $content = __(
            'Other variants of this product. Selected products are linked to each other as one group, '
            . 'so linking A to B and C also links B and C.'
        );

        return [
            'children' => [
                'button_set' => $this->getButtonSet($content, __('Add Linked Variants'), $this->scopePrefix . $scope),
                'modal' => $this->getGenericModal(__('Add Linked Variants'), $this->scopePrefix . $scope),
                $scope => $this->getGrid($this->scopePrefix . $scope),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label' => __('Linked Variants'),
                        'collapsible' => false,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => self::SORT_ORDER,
                    ],
                ],
            ],
        ];
    }
}
