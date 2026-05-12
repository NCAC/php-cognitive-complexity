<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Config;

/**
 * Resolved configuration for the analyzer.
 */
final class Config {

  /** @var list<string> */
  private const DEFAULT_EXTENSIONS = ['php'];

  /**
   * @param int                   $default_max        Default complexity threshold
   * @param array<string, int>    $path_thresholds   Per-path overrides (path => max)
   * @param list<string>          $excluded_paths    Paths to exclude from analysis
   * @param list<string>          $extensions        File extensions to analyse
   */
  public function __construct(
    private readonly int $default_max,
    private readonly array $path_thresholds = [],
    private readonly array $excluded_paths = [],
    private readonly array $extensions = self::DEFAULT_EXTENSIONS,
  ) {
  }

  public function getDefaultMax(): int {
    return $this->default_max;
  }

  /**
   * Returns the threshold applicable to a given file path.
   * The most specific (longest) matching prefix wins.
   */
  public function getThresholdForPath(string $file_path): int {
    $best = null;
    $best_length = 0;

    foreach ($this->path_thresholds as $prefix => $threshold) {
      if (str_starts_with($file_path, $prefix) && \strlen($prefix) > $best_length) {
        $best = $threshold;
        $best_length = \strlen($prefix);
      }
    }

    return $best ?? $this->default_max;
  }

  /**
   * @return list<string>
   */
  public function getExcludedPaths(): array {
    return $this->excluded_paths;
  }

  /**
   * @return list<string>
   */
  public function getExtensions(): array {
    return $this->extensions;
  }

}
