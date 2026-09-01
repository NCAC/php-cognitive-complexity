<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Config;

/**
 * Compiles a single glob-style pattern into an anchored regular expression
 * and matches it against project-root-relative, "/"-separated paths.
 *
 * Grammar (see docs/rfc/0001-project-relative-path-matching.md §4):
 *
 *   *    any run of characters except "/"
 *   **   any run of characters including "/" (spans directories)
 *   ?    exactly one character except "/"
 *   ** / zero or more leading directory segments (no space)
 *
 * The pattern is anchored at both ends. Naming a directory implicitly covers
 * its whole subtree, so `vendor/` matches `vendor` and `vendor/autoload.php`.
 * To match at any depth, prefix the pattern with `** /` (no space).
 */
final class PathMatcher {

  private readonly string $raw;

  /** @var non-empty-string */
  private readonly string $regex;

  /** Number of literal (non-wildcard) characters — used for specificity. */
  private readonly int $literalCount;

  public function __construct(string $pattern) {
    $this->raw = $pattern;
    $normalized = self::normalize($pattern);
    $this->regex = self::compile($normalized);
    $this->literalCount = \strlen(str_replace(['**/', '**', '*', '?'], '', $normalized));
  }

  private static function normalize(string $pattern): string {
    $pattern = str_replace('\\', '/', trim($pattern));
    if (str_starts_with($pattern, './')) {
      $pattern = substr($pattern, 2);
    }

    return trim($pattern, '/');
  }

  /**
   * @return non-empty-string
   */
  private static function compile(string $pattern): string {
    if ($pattern === '') {
      // Match nothing.
      return '#^(?!)$#';
    }

    $out = '';
    $len = \strlen($pattern);

    for ($i = 0; $i < $len; $i++) {
      $char = $pattern[$i];

      if ($char === '*') {
        if (($pattern[$i + 1] ?? '') === '*') {
          $i++;
          if (($pattern[$i + 1] ?? '') === '/') {
            $i++;
            $out .= '(?:.*/)?';
          } else {
            $out .= '.*';
          }
        } else {
          $out .= '[^/]*';
        }

        continue;
      }

      if ($char === '?') {
        $out .= '[^/]';

        continue;
      }

      $out .= preg_quote($char, '#');
    }

    return '#^' . $out . '(?:/.*)?$#';
  }

  public function matches(string $relative_path): bool {
    $path = trim(str_replace('\\', '/', $relative_path), '/');

    return preg_match($this->regex, $path) === 1;
  }

  /**
   * Length of the literal portion of the pattern, ignoring wildcard tokens.
   * Higher means more specific.
   */
  public function literalLength(): int {
    return $this->literalCount;
  }

  public function pattern(): string {
    return $this->raw;
  }

}
