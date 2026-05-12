<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\CLI;

use NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer;
use NCAC\CognitiveComplexity\Config\Config;
use NCAC\CognitiveComplexity\Config\ConfigLoader;
use NCAC\CognitiveComplexity\Reporter\RichConsoleReporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * "analyse" command: developer-experience oriented analysis.
 *
 * Designed for interactive use — coloured output, severity levels,
 * full function listing. Always exits 0 (exploration, not a gate).
 *
 * Usage:
 *   bin/cognitive-complexity analyse src/
 *   bin/cognitive-complexity analyse src/ --all
 *   bin/cognitive-complexity analyse src/ --sort=score
 *   bin/cognitive-complexity analyse src/ --ext=php,module,inc
 *   bin/cognitive-complexity analyse src/ --max=20
 */
#[AsCommand(name: 'analyse', description: 'Interactive cognitive complexity report with severity levels')]
final class AnalyseCommand extends Command {

  /** @override */
  protected function configure(): void {
    $this
      ->addArgument('path', InputArgument::REQUIRED, 'Path to analyse (file or directory)')
      ->addOption('max', null, InputOption::VALUE_REQUIRED, 'Complexity threshold', 15)
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to cognitive.yaml config file', null)
      ->addOption('all', null, InputOption::VALUE_NONE, 'Show all functions, including those below threshold')
      ->addOption('sort', null, InputOption::VALUE_REQUIRED, 'Sort order: file (default) or score', 'file')
      ->addOption('ext', null, InputOption::VALUE_REQUIRED, 'Comma-separated file extensions to analyse', 'php');
  }

  /** @override */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    /** @var string $path */
    $path = $input->getArgument('path');
    $max = (int) $input->getOption('max');
    /** @var string|null $config_file */
    $config_file = $input->getOption('config');
    $show_all = (bool) $input->getOption('all');
    $sort_by_score = $input->getOption('sort') === 'score';

    /** @var string $ext_option */
    $ext_option = $input->getOption('ext');
    $extensions = array_filter(array_map('trim', explode(',', $ext_option)));

    $config = $this->buildConfig($config_file, $max, array_values($extensions));
    $analyzer = new CognitiveAnalyzer($config);
    $results = $analyzer->analyze($path);

    $reporter = new RichConsoleReporter();
    $reporter->report($output, $results, $show_all, $sort_by_score);

    return Command::SUCCESS;
  }

  /**
   * @param list<string> $extensions
   */
  private function buildConfig(?string $config_file, int $max, array $extensions): Config {
    $base = ConfigLoader::load($config_file, $max);

    return new Config(
      $base->getDefaultMax(),
      [],
      $base->getExcludedPaths(),
      $extensions,
    );
  }

}
