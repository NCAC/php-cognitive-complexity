<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Parser;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\ParserFactory;

/**
 * Parses a PHP file into an AST using nikic/php-parser.
 */
final class FileParser {

  private \PhpParser\Parser $parser;

  public function __construct() {
    $factory = new ParserFactory();
    $this->parser = $factory->createForNewestSupportedVersion();
  }

  /**
   * Parse a PHP file and return its AST.
   *
   * @return list<Node\Stmt>
   *
   * @throws \RuntimeException When the file cannot be read or parsed
   */
  public function parse(string $file_path): array {
    $code = @file_get_contents($file_path);
    if ($code === false) {
      throw new \RuntimeException(\sprintf('Cannot read file: %s', $file_path));
    }

    try {
      $stmts = $this->parser->parse($code);
    } catch (Error $e) {
      throw new \RuntimeException(
        \sprintf('Parse error in %s: %s', $file_path, $e->getMessage()),
        0,
        $e
      );
    }

    return array_values($stmts ?? []);
  }

}
