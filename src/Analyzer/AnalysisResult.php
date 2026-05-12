<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Analyzer;

/**
 * Value object representing the cognitive complexity analysis result
 * for a single method or function.
 */
final class AnalysisResult {

  public function __construct(
    public readonly string $file,
    public readonly string $function,
    public readonly int $score,
    public readonly int $threshold,
    public readonly int $line,
  ) {
  }

  public function hasViolation(): bool {
    return $this->score > $this->threshold;
  }

}
