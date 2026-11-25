<?php

/**
 * @package     FalconMedia_LinkedVariants
 * @author      Henk Valk (henk@falconmedia.nl)
 *
 * Copyright (c) Falcon Media (www.falconmedia.nl)
 */

/**
 * Runs the suite from this package, before or without installing it into vendor/.
 * Set LINKED_VARIANTS_AUTOLOAD to the vendor/autoload.php of a Magento installation; by default the package is
 * assumed to live in <magento root>/package-source/<Vendor>/<Module> or <magento root>/app/code/<Vendor>/<Module>.
 */

declare(strict_types=1);

/** @var \Composer\Autoload\ClassLoader $autoloader */
$autoloadFile = getenv('LINKED_VARIANTS_AUTOLOAD') ?: __DIR__ . '/../../../../vendor/autoload.php';
$autoloader = require $autoloadFile;

// Prepended, so this checkout wins over a copy that is already installed in vendor/.
$autoloader->addPsr4('FalconMedia\\LinkedVariants\\', __DIR__ . '/../src/', true);
$autoloader->addPsr4('FalconMedia\\LinkedVariants\\Test\\Unit\\', __DIR__ . '/Unit/', true);
