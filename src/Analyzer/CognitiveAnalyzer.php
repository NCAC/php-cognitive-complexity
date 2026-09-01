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
 *
 * Paths handed to reporters and used for `exclude:` / `paths:` matching are
 * relative to the configured project root (see Config::getProjectRoot()).
 */
final class CognitiveAnalyzer {

  private FileParser $parser;

  public function __construct(private readonly Config $config) {
    $this->parser = new FileParser();
  }

  /**
   * Analyze a path (file or directory) and return all results.
   *
   * @param string $path      Path to analyze
   * @param bool   $diff_only Restrict to git-modified files only
   *
   * @return list<AnalysisResult>
   */
  public function analyze(string $path, bool $diff_only = false): array {
    $files = $this->collectFiles($path, $diff_only);

    $results = [];
    foreach ($files as $file) {
      foreach ($this->analyzeFile($file, $this->toRelative($file)) as $result) {
        $results[] = $result;
      }
    }

    return $results;
  }

  /**
   * @return list<AnalysisResult>
   */
  private function analyzeFile(string $absolute_path, string $relative_path): array {
    $ast = $this->parser->parse($absolute_path);

    $threshold = $this->config->getThresholdForPath($relative_path);
    $visitor = new ComplexityVisitor($relative_path, $threshold);
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
      // An explicitly named file is always analysed, exclude patterns aside.
      return [$path];
    }

    $finder = new Finder();
    $patterns = array_map(static fn (string $ext) => '*.' . $ext, $this->config->getExtensions());
    $finder->files()->name($patterns)->in($path);

    // One closure both filters excluded files and prunes excluded directories
    // during the walk, so large generated trees (e.g. Drupal's sites/*/files)
    // are never descended into.
    $finder->filter(
      fn (\SplFileInfo $info): bool => !$this->config->isExcluded($this->toRelative($info->getPathname())),
      true,
    );

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
   * Returns only source files from staged changes (pre-commit) and/or
   * committed-but-unpushed changes (CI), deduplicated and filtered through
   * the configured exclude patterns.
   *
   * @return list<string>
   */
  private function getGitModifiedFiles(string $base_path): array {
    /** @psalm-suppress ForbiddenCode */
    $staged = shell_exec('git diff --cached --name-only --diff-filter=ACM 2>/dev/null');
    /** @psalm-suppress ForbiddenCode */
    $committed = shell_exec('git diff --name-only --diff-filter=ACM HEAD 2>/dev/null');

    $raw = trim(($staged !== false ? ($staged ?? '') : '') . "\n" . ($committed !== false ? ($committed ?? '') : ''));
    if ($raw === '') {
      return [];
    }

    $files = array_unique(array_filter(explode("\n", $raw)));
    $extensions = $this->config->getExtensions();
    $cwd = (string) getcwd();
    $base_real = realpath($base_path) ?: rtrim($base_path, '/');

    $result = [];
    foreach ($files as $file) {
      if (!\in_array(pathinfo($file, \PATHINFO_EXTENSION), $extensions, true)) {
        continue;
      }

      $real = realpath($cwd . '/' . $file);
      if ($real === false) {
        continue;
      }
      if (!str_starts_with($real, $base_real)) {
        continue;
      }
      if ($this->config->isExcluded($this->toRelative($real))) {
        continue;
      }

      $result[] = $real;
    }

    return $result;
  }

  /**
   * Convert an absolute (or CWD-relative) path to a project-root-relative,
   * "/"-separated path.
   */
  private function toRelative(string $path): string {
    $real = realpath($path);
    $real = $real !== false ? $real : $path;
    $real = str_replace('\\', '/', $real);

    $root = $this->config->getProjectRoot();
    if ($root !== '') {
      $root_real = realpath($root);
      $root = str_replace('\\', '/', $root_real !== false ? $root_real : $root);
      $root = rtrim($root, '/');

      if ($real === $root) {
        return '';
      }
      if (str_starts_with($real, $root . '/')) {
        return substr($real, \strlen($root) + 1);
      }
    }

    return ltrim($real, '/');
  }

}
