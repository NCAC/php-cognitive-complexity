<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Config;

/**
 * Resolved configuration for the analyzer.
 *
 * All path-keyed settings (`exclude:` and `paths:`) are glob patterns matched
 * against each file's path relative to the project root. See
 * docs/rfc/0001-project-relative-path-matching.md.
 */
final class Config {

  /** @var list<string> */
  public const DEFAULT_EXTENSIONS = ['php'];

  private readonly int $defaultMax;

  /** @var array<string, int> */
  private readonly array $pathThresholds;

  /** @var list<string> */
  private readonly array $excludePatterns;

  /** @var list<string> */
  private readonly array $extensions;

  private readonly string $projectRoot;

  /** @var array<string, PathMatcher> pattern => compiled matcher */
  private readonly array $thresholdMatchers;

  /** @var list<PathMatcher> */
  private readonly array $excludeMatchers;

  /**
   * @param int                $default_max     Default complexity threshold
   * @param array<string, int> $path_thresholds Per-pattern overrides (glob => max)
   * @param list<string>       $excluded_paths  Glob patterns to exclude from analysis
   * @param list<string>       $extensions      File extensions to analyse
   * @param string             $project_root    Absolute path all patterns/paths are relative to
   */
  public function __construct(
    int $default_max,
    array $path_thresholds = [],
    array $excluded_paths = [],
    array $extensions = self::DEFAULT_EXTENSIONS,
    string $project_root = '',
  ) {
    $this->defaultMax = $default_max;
    $this->pathThresholds = $path_thresholds;
    $this->excludePatterns = $excluded_paths;
    $this->extensions = $extensions;
    $this->projectRoot = $project_root;

    $threshold_matchers = [];
    foreach (array_keys($path_thresholds) as $pattern) {
      $threshold_matchers[(string) $pattern] = new PathMatcher((string) $pattern);
    }
    $this->thresholdMatchers = $threshold_matchers;

    $this->excludeMatchers = array_map(
      static fn (string $pattern): PathMatcher => new PathMatcher($pattern),
      $this->excludePatterns,
    );
  }

  public function getDefaultMax(): int {
    return $this->defaultMax;
  }

  public function getProjectRoot(): string {
    return $this->projectRoot;
  }

  /**
   * Returns the threshold applicable to a given project-root-relative path.
   * The most specific pattern wins: greatest number of literal characters,
   * ties broken by the longer raw pattern.
   */
  public function getThresholdForPath(string $relative_path): int {
    $best = null;
    $best_literal = -1;
    $best_length = -1;

    foreach ($this->thresholdMatchers as $pattern => $matcher) {
      if (!$matcher->matches($relative_path)) {
        continue;
      }

      $literal = $matcher->literalLength();
      $length = \strlen($pattern);
      if ($literal > $best_literal || ($literal === $best_literal && $length > $best_length)) {
        $best = $this->pathThresholds[$pattern];
        $best_literal = $literal;
        $best_length = $length;
      }
    }

    return $best ?? $this->defaultMax;
  }

  /**
   * True when the given project-root-relative path matches any exclude pattern.
   */
  public function isExcluded(string $relative_path): bool {
    foreach ($this->excludeMatchers as $matcher) {
      if ($matcher->matches($relative_path)) {
        return true;
      }
    }

    return false;
  }

  /**
   * @return list<string>
   */
  public function getExcludePatterns(): array {
    return $this->excludePatterns;
  }

  /**
   * @return list<string>
   */
  public function getExtensions(): array {
    return $this->extensions;
  }

  /**
   * @param list<string> $extensions
   */
  public function withExtensions(array $extensions): self {
    return new self(
      $this->defaultMax,
      $this->pathThresholds,
      $this->excludePatterns,
      $extensions,
      $this->projectRoot,
    );
  }

}
