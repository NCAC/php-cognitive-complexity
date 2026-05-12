<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\CLI;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * Main CLI application entry point.
 */
final class Application extends BaseApplication {

  private const VERSION = '1.1.0';

  private const NAME = 'PHP Cognitive Complexity';

  public function __construct() {
    parent::__construct(self::NAME, self::VERSION);

    $this->add(new CheckCommand());
    $this->add(new BaselineCommand());
    $this->add(new AnalyseCommand());
    $this->setDefaultCommand('check', false);
  }

}
