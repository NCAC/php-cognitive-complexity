<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\CLI;

use NCAC\CognitiveComplexity\Analyzer\CognitiveAnalyzer;
use NCAC\CognitiveComplexity\Config\ConfigLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates a baseline file from the current codebase, so future runs
 * only report new violations (not pre-existing ones).
 *
 * Usage:
 *   bin/cognitive-complexity baseline src/ > baseline.json
 *   bin/cognitive-complexity check src/ --baseline=baseline.json
 */
#[AsCommand(name: 'baseline', description: 'Generate a complexity baseline file')]
final class BaselineCommand extends Command {

  /** @override */
  protected function configure(): void {
    $this
      ->setDescription('Generate a baseline of current complexity violations')
      ->addArgument('path', InputArgument::REQUIRED, 'Path to analyze')
      ->addOption('max', null, InputOption::VALUE_REQUIRED, 'Global complexity threshold', 15)
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to cognitive.yaml config file', null)
      ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path (default: stdout)', null);
  }

  /** @override */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    /** @var string $path */
    $path = $input->getArgument('path');
    $max = (int) $input->getOption('max');
    /** @var string|null $configFile */
    $config_file = $input->getOption('config');
    /** @var string|null $outputFile */
    $output_file = $input->getOption('output');

    $config = ConfigLoader::load($config_file, $max);
    $analyzer = new CognitiveAnalyzer($config);
    $results = $analyzer->analyze($path, false);

    $baseline = [];
    foreach ($results as $result) {
      $baseline[$result->file][$result->function] = $result->score;
    }

    $json = (string) json_encode($baseline, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) . "\n";

    if ($output_file !== null) {
      file_put_contents($output_file, $json);
      $output->writeln("<info>Baseline written to {$output_file}</info>");
    } else {
      $output->write($json);
    }

    return Command::SUCCESS;
  }

}
