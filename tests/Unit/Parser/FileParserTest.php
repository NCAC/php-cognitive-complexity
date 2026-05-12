<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Tests\Unit\Parser;

use NCAC\CognitiveComplexity\Parser\FileParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileParser::class)]
final class FileParserTest extends TestCase {

  private FileParser $parser;

  private string $tmpDir;

  public function testParseReturnsAstForValidFile(): void {
    $file = $this->tmpDir . '/valid.php';
    file_put_contents($file, '<?php function foo(): void {}');

    $stmts = $this->parser->parse($file);

    self::assertNotEmpty($stmts);
  }

  public function testParseReturnsEmptyArrayForEmptyFile(): void {
    $file = $this->tmpDir . '/empty.php';
    file_put_contents($file, '<?php ');

    $stmts = $this->parser->parse($file);

    self::assertSame([], $stmts);
  }

  public function testParseThrowsRuntimeExceptionForNonExistentFile(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/Cannot read file/');

    $this->parser->parse($this->tmpDir . '/nonexistent.php');
  }

  public function testParseThrowsRuntimeExceptionForInvalidPhp(): void {
    $file = $this->tmpDir . '/invalid.php';
    file_put_contents($file, '<?php function foo( { }');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/Parse error/');

    $this->parser->parse($file);
  }

  protected function setUp(): void {
    $this->parser = new FileParser();
    $this->tmpDir = sys_get_temp_dir() . '/php-cc-parser-tests-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
  }

  protected function tearDown(): void {
    array_map('unlink', glob($this->tmpDir . '/*') ?: []);
    rmdir($this->tmpDir);
  }

}
