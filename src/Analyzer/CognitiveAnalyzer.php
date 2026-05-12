<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Analyzer;

use NCAC\CognitiveComplexity\Config\Config;
use NCAC\CognitiveComplexity\Parser\FileParser;
use Symfony\Component\Finder\Finder;

/**
 * Orchestrates the analysis of PHP files for cognitive complexity.
 *
 * Uses nikic/php-parser to build an AST, then delegates to
 * ComplexityVisitor to compute the score per method/function.
 */
final class CognitiveAnalyzer {

  private FileParser $parser;

  public function __construct(private readonly Config $config) {
    $this->parser = new FileParser();
  }

  /**
   * Analyze a path (file or directory) and return all results.
   *
   * @param string $path     Path to analyze
   * @param bool   $diff_only Restrict to git-modified files only
   *
   * @return list<AnalysisResult>
   */
  public function analyze(string $path, bool $diff_only = false): array {
    $files = $this->collectFiles($path, $diff_only);

    $results = [];
    foreach ($files as $file) {
      $file_results = $this->analyzeFile($file);
      foreach ($file_results as $result) {
        $results[] = $result;
      }
    }

    return $results;
  }

  /**
   * @return list<AnalysisResult>
   */
  private function analyzeFile(string $file_path): array {
    $ast = $this->parser->parse($file_path);

    $threshold = $this->config->getThresholdForPath($file_path);
    $visitor = new ComplexityVisitor($file_path, $threshold);
    $traverser = new \PhpParser\NodeTraverser();
    $traverser->addVisitor($visitor);
    $traverser->traverse($ast);

    return $visitor->getResults();
  }

  /**
   * @return list<string> Absolute file paths
   */
  private function collectFiles(string $path, bool $diff_only): array {
    if ($diff_only) {
      return $this->getGitModifiedFiles($path);
    }

    if (is_file($path)) {
      return [$path];
    }

    $finder = new Finder();
    $patterns = array_map(static fn (string $ext) => '*.' . $ext, $this->config->getExtensions());
    $finder->files()->name($patterns)->in($path);

    foreach ($this->config->getExcludedPaths() as $excluded) {
      $finder->exclude($excluded);
    }

    $files = [];
    foreach ($finder as $file) {
      $real = $file->getRealPath();
      if ($real !== false) {
        $files[] = $real;
      }
    }

    return $files;
  }

  /**
   * Returns only PHP files from staged changes (pre-commit) and/or
   * committed-but-unpushed changes (CI), deduplicated.
   *
   * @return list<string>
   */
  private function getGitModifiedFiles(string $base_path): array {
    // Staged files (pre-commit hook context)
    /** @psalm-suppress ForbiddenCode */
    $staged = shell_exec('git diff --cached --name-only --diff-filter=ACM 2>/dev/null');
    // Committed but not yet pushed (CI / post-commit context)
    /** @psalm-suppress ForbiddenCode */
    $committed = shell_exec('git diff --name-only --diff-filter=ACM HEAD 2>/dev/null');

    $raw = trim(($staged !== false ? ($staged ?? '') : '') . "\n" . ($committed !== false ? ($committed ?? '') : ''));
    if ($raw === '') {
      return [];
    }

    $files = array_unique(array_filter(explode("\n", $raw)));
    $extensions = $this->config->getExtensions();
    $php_files = array_filter(
      $files,
      static fn (string $f) => \in_array(pathinfo($f, \PATHINFO_EXTENSION), $extensions, true),
    );

    $base_path = rtrim($base_path, '/');
    $cwd = (string) getcwd();
    $result = [];
    foreach ($php_files as $file) {
      $absolute = $cwd . '/' . $file;
      $real = realpath($absolute);
      if ($real !== false && str_starts_with($real, $base_path)) {
        $result[] = $absolute;
      }
    }

    return $result;
  }

}
