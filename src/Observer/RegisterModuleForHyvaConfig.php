<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

namespace FalconMedia\LinkedVariants\Observer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Registers the module in app/etc/hyva-themes.json so Hyva theme Tailwind builds scan its templates.
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    public function __construct(
        private readonly ComponentRegistrarInterface $componentRegistrar,
        private readonly DirectoryList $directoryList
    ) {
    }

    public function execute(Observer $observer): void
    {
        $config = $observer->getData('config');
        $extensions = $config->hasData('extensions') ? (array) $config->getData('extensions') : [];

        $path = (string) $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'FalconMedia_LinkedVariants');
        $root = rtrim($this->directoryList->getRoot(), '/') . '/';
        if (str_starts_with($path, $root)) {
            $path = substr($path, strlen($root));
        }

        $extensions[] = ['src' => $path];
        $config->setData('extensions', $extensions);
    }
}
