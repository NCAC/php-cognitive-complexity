<?php

declare(strict_types=1);

use PhpCsFixer\Finder;

// Delegate to the NCAC standard config and override the Finder
// to target only this project's source files.
$config = require __DIR__ . '/vendor/ncac/phpcs-standard/.php-cs-fixer.dist.php';

$finder = (new Finder())
  ->in(__DIR__ . '/src')
  ->in(__DIR__ . '/tests')
  ->name('*.php');

return $config->setFinder($finder);
