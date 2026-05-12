<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\CLI;

use NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer;
use NCAC\CognitiveComplexity\Config\ConfigLoader;
use NCAC\CognitiveComplexity\Reporter\ConsoleReporter;
use NCAC\CognitiveComplexity\Reporter\GitlabReporter;
use NCAC\CognitiveComplexity\Reporter\JsonReporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Main "check" command: analyze a path and exit 1 if threshold exceeded.
 *
 * Usage:
 *   bin/cognitive-complexity check src/ --max=15
 *   bin/cognitive-complexity check src/ --config=cognitive.yaml
 *   bin/cognitive-complexity check src/ --format=json --diff
 */
#[AsCommand(name: 'check', description: 'Analyze cognitive complexity of PHP files')]
final class CheckCommand extends Command {

  /** @override */
  protected function configure(): void {
    $this
      ->setDescription('Analyze PHP files for cognitive complexity violations')
      ->addArgument('path', InputArgument::REQUIRED, 'Path to analyze (file or directory)')
      ->addOption('max', null, InputOption::VALUE_REQUIRED, 'Global complexity threshold', 15)
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to cognitive.yaml config file', null)
      ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format: console, json, gitlab, checkstyle', 'console')
      ->addOption('diff', null, InputOption::VALUE_NONE, 'Analyze only git-modified files')
      ->addOption('baseline', null, InputOption::VALUE_REQUIRED, 'Baseline file to ignore existing violations', null);
  }

  /** @override */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    /** @var string $path */
    $path = $input->getArgument('path');
    $max = (int) $input->getOption('max');
    /** @var string|null $configFile */
    $config_file = $input->getOption('config');
    $format = (string) $input->getOption('format');
    $diff = (bool) $input->getOption('diff');
    /** @var string|null $baseline */
    $baseline = $input->getOption('baseline');

    $config = ConfigLoader::load($config_file, $max);
    $analyzer = new CognitiveAnalyzer($config);
    $results = $analyzer->analyze($path, $diff);

    $reporter = match ($format) {
      'json' => new JsonReporter(),
      'gitlab' => new GitlabReporter(),
      default => new ConsoleReporter($output),
    };

    $has_violations = $reporter->report($results, $baseline);

    return $has_violations ? Command::FAILURE : Command::SUCCESS;
  }

}
