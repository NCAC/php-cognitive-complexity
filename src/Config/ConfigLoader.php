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
 *   - "**\/files/php/"
 * extensions:
 *   - php
 *   - module
 *   - inc
 *
 * Both `paths:` keys and `exclude:` entries are glob patterns matched against
 * each file's path RELATIVE TO THE PROJECT ROOT — the directory that holds the
 * config file, or the `root:` key if given, or the current working directory
 * when no config file is used. The path passed to `check` / `analyse` only
 * narrows what is walked; it never changes how patterns match.
 * See docs/rfc/0001-project-relative-path-matching.md.
 */
final class ConfigLoader {

  /**
   * Load config from a YAML file (or return defaults if no file given).
   *
   * @throws ConfigException When an explicit $config_file does not exist or the
   *                         YAML cannot be parsed.
   */
  public static function load(?string $config_file, int $default_max = 15): Config {
    $config_file = self::resolveConfigFile($config_file);

    if ($config_file === null) {
      return new Config($default_max, [], [], Config::DEFAULT_EXTENSIONS, self::cwd());
    }

    try {
      /** @var array<string, mixed> $data */
      $data = Yaml::parseFile($config_file) ?? [];
    } catch (\Throwable $e) {
      throw new ConfigException(
        \sprintf('Cannot parse config file "%s": %s', $config_file, $e->getMessage()),
        0,
        $e,
      );
    }

    $max = isset($data['max_complexity']) ? (int) $data['max_complexity'] : $default_max;
    $project_root = self::resolveProjectRoot($data, \dirname($config_file));

    return new Config(
      $max,
      self::parsePathThresholds($data),
      self::parseExcludedPaths($data),
      self::parseExtensions($data),
      $project_root,
    );
  }

  private static function resolveConfigFile(?string $config_file): ?string {
    if ($config_file !== null) {
      if (!is_file($config_file)) {
        throw new ConfigException(\sprintf('Config file not found: "%s"', $config_file));
      }

      return realpath($config_file) ?: $config_file;
    }

    $candidate = self::cwd() . '/cognitive.yaml';

    return is_file($candidate) ? $candidate : null;
  }

  /**
   * @param array<string, mixed> $data
   */
  private static function resolveProjectRoot(array $data, string $config_dir): string {
    if (isset($data['root']) && \is_string($data['root']) && $data['root'] !== '') {
      $root = $data['root'];
      if (!self::isAbsolute($root)) {
        $root = $config_dir . '/' . $root;
      }

      return realpath($root) ?: $root;
    }

    return realpath($config_dir) ?: $config_dir;
  }

  private static function isAbsolute(string $path): bool {
    return str_starts_with($path, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;
  }

  private static function cwd(): string {
    return getcwd() ?: '.';
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
