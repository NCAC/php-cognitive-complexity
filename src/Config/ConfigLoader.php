<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Loads a cognitive.yaml configuration file.
 *
 * Example cognitive.yaml:
 *
 * max_complexity: 15
 * paths:
 *   src/Controller/: 10
 *   src/Service/: 12
 *   tests/: 20
 * exclude:
 *   - vendor/
 *   - cache/
 */
final class ConfigLoader {

  /**
   * Load config from a YAML file (or return defaults if no file given).
   */
  public static function load(?string $config_file, int $default_max = 15): Config {
    if ($config_file === null) {
      // Auto-discover cognitive.yaml in cwd
      $candidate = (getcwd() ?: '') . '/cognitive.yaml';
      if (file_exists($candidate)) {
        $config_file = $candidate;
      }
    }

    if ($config_file === null || !file_exists($config_file)) {
      return new Config($default_max);
    }

    /** @var array<string, mixed> $data */
    $data = Yaml::parseFile($config_file);

    $max = isset($data['max_complexity']) ? (int) $data['max_complexity'] : $default_max;

    /** @var array<string, int> $pathThresholds */
    $path_thresholds = [];
    if (isset($data['paths']) && \is_array($data['paths'])) {
      foreach ($data['paths'] as $path => $threshold) {
        $path_thresholds[(string) $path] = (int) $threshold;
      }
    }

    /** @var list<string> $excludedPaths */
    $excluded_paths = [];
    if (isset($data['exclude']) && \is_array($data['exclude'])) {
      $excluded_paths = array_values(array_map('strval', $data['exclude']));
    }

    return new Config($max, $path_thresholds, $excluded_paths);
  }

}
