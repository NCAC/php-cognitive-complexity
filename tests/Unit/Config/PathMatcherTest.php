<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Config;

use NCAC\CognitiveComplexity\Config\PathMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathMatcher::class)]
final class PathMatcherTest extends TestCase {

  /**
   * @return iterable<string, array{string, string, bool}>
   */
  public static function cases(): iterable {
    yield 'plain dir matches itself'            => ['vendor/', 'vendor', true];
    yield 'plain dir matches subtree'           => ['vendor/', 'vendor/autoload.php', true];
    yield 'plain dir is anchored'               => ['vendor/', 'app/vendor/autoload.php', false];
    yield 'no trailing slash, same result'      => ['vendor', 'vendor/x.php', true];
    yield 'globstar prefix, any depth'          => ['**/files/php/', 'web/sites/a/files/php/x.php', true];
    yield 'globstar prefix, at root'            => ['**/files/php/', 'files/php/x.php', true];
    yield 'globstar prefix, no false segment'   => ['**/files/php/', 'web/sites/a/files/phpstan.php', false];
    yield 'globstar in the middle'              => ['sites/**/files/php/', 'sites/aaa/files/php/twig/x.php', true];
    yield 'globstar middle, zero segments'      => ['sites/**/files/php/', 'sites/files/php/x.php', true];
    yield 'trailing bare ** spans segments'     => ['sites/**', 'sites/aaa/files/x.php', true];
    yield 'trailing bare ** needs a segment'    => ['sites/**', 'sites', false];
    yield 'lone ** matches everything'          => ['**', 'a/b/c/deep.php', true];
    yield 'bare ** inside a segment'            => ['a**b', 'axxb', true];
    yield 'single star stays in segment'        => ['src/*/Legacy/', 'src/Billing/Legacy/X.php', true];
    yield 'single star does not cross slash'    => ['src/*/Legacy/', 'src/Billing/Sub/Legacy/X.php', false];
    yield 'question mark, one char'             => ['config?.php', 'config1.php', true];
    yield 'question mark, not two chars'        => ['config?.php', 'config12.php', false];
    yield 'suffix glob on files'                => ['**/*.blade.php', 'resources/views/home.blade.php', true];
    yield 'literal dot is escaped'              => ['a.b/', 'axb/c.php', false];
    yield 'leading ./ is ignored'               => ['./vendor/', 'vendor/x.php', true];
    yield 'leading slash is ignored'            => ['/vendor/', 'vendor/x.php', true];
    yield 'backslashes normalised'              => ['vendor/', 'vendor\\pkg\\x.php', true];
  }

  #[DataProvider('cases')]
  public function testMatches(string $pattern, string $path, bool $expected): void {
    self::assertSame($expected, (new PathMatcher($pattern))->matches($path));
  }

  public function testEmptyPatternMatchesNothing(): void {
    self::assertFalse((new PathMatcher(''))->matches(''));
    self::assertFalse((new PathMatcher('/'))->matches('anything/x.php'));
  }

  public function testLiteralLengthIgnoresWildcards(): void {
    self::assertSame(\strlen('src/Domain/Legacy'), (new PathMatcher('src/Domain/Legacy/'))->literalLength());
    self::assertGreaterThan(
      (new PathMatcher('src/'))->literalLength(),
      (new PathMatcher('src/Domain/Legacy/'))->literalLength(),
    );
    self::assertSame(
      (new PathMatcher('**/files/php'))->literalLength(),
      \strlen('files/php'),
    );
  }

  public function testPatternAccessorReturnsRawInput(): void {
    self::assertSame('  vendor/  ', (new PathMatcher('  vendor/  '))->pattern());
  }

}
