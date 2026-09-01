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
 * extensions:
 *   - php
 *   - module
 *   - inc
 *
 * Both `paths:` keys and `exclude:` entries are prefixes matched against each
 * file's path RELATIVE to the analysed argument (the path given to `check` /
 * `analyse`), not relative to the project root. Plain directory paths only —
 * no globs. Note: `--diff` mode does not apply `exclude:`.
 */
final class ConfigLoader {

  /**
   * Load config from a YAML file (or return defaults if no file given).
   */
  public static function load(?string $config_file, int $default_max = 15): Config {
    $config_file = self::resolveConfigFile($config_file);

    if ($config_file === null) {
      return new Config($default_max);
    }

    /** @var array<string, mixed> $data */
    $data = Yaml::parseFile($config_file);

    $max = isset($data['max_complexity']) ? (int) $data['max_complexity'] : $default_max;

    return new Config($max, self::parsePathThresholds($data), self::parseExcludedPaths($data), self::parseExtensions($data));
  }

  private static function resolveConfigFile(?string $config_file): ?string {
    if ($config_file !== null) {
      return file_exists($config_file) ? $config_file : null;
    }

    $candidate = (getcwd() ?: '') . '/cognitive.yaml';

    return file_exists($candidate) ? $candidate : null;
  }

  /**
   * @param array<string, mixed> $data
   * @return array<string, int>
   */
  private static function parsePathThresholds(array $data): array {
    $thresholds = [];

    if (isset($data['paths']) && \is_array($data['paths'])) {
      foreach ($data['paths'] as $path => $threshold) {
        $thresholds[(string) $path] = (int) $threshold;
      }
    }

    return $thresholds;
  }

  /**
   * @param array<string, mixed> $data
   * @return list<string>
   */
  private static function parseExcludedPaths(array $data): array {
    if (isset($data['exclude']) && \is_array($data['exclude'])) {
      return array_values(array_map('strval', $data['exclude']));
    }

    return [];
  }

  /**
   * @param array<string, mixed> $data
   * @return list<string>
   */
  private static function parseExtensions(array $data): array {
    if (isset($data['extensions']) && \is_array($data['extensions'])) {
      $parsed = array_values(array_filter(array_map('strval', $data['extensions'])));
      if ($parsed !== []) {
        return $parsed;
      }
    }

    return Config::DEFAULT_EXTENSIONS;
  }

}
