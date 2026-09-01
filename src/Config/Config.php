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

  /** @var array<string, PathMatcher> pattern => compiled matcher */
  private array $thresholdMatchers;

  /** @var list<PathMatcher> */
  private array $excludeMatchers;

  /**
   * @param int                $default_max      Default complexity threshold
   * @param array<string, int> $path_thresholds  Per-pattern overrides (glob => max)
   * @param list<string>       $excluded_paths   Glob patterns to exclude from analysis
   * @param list<string>       $extensions       File extensions to analyse
   * @param string             $project_root     Absolute path all patterns/paths are relative to
   */
  public function __construct(
    private readonly int $default_max,
    private readonly array $path_thresholds = [],
    private readonly array $excluded_paths = [],
    private readonly array $extensions = self::DEFAULT_EXTENSIONS,
    private readonly string $project_root = '',
  ) {
    $this->thresholdMatchers = [];
    foreach (array_keys($path_thresholds) as $pattern) {
      $this->thresholdMatchers[(string) $pattern] = new PathMatcher((string) $pattern);
    }

    $this->excludeMatchers = array_map(
      static fn (string $pattern) => new PathMatcher($pattern),
      $excluded_paths,
    );
  }

  public function getDefaultMax(): int {
    return $this->default_max;
  }

  public function getProjectRoot(): string {
    return $this->project_root;
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
        $best = $this->path_thresholds[$pattern];
        $best_literal = $literal;
        $best_length = $length;
      }
    }

    return $best ?? $this->default_max;
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
    return $this->excluded_paths;
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
      $this->default_max,
      $this->path_thresholds,
      $this->excluded_paths,
      $extensions,
      $this->project_root,
    );
  }

}
