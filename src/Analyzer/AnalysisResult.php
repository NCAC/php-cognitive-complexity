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

  /**
   * Returns the severity level based on multiples of the threshold.
   *
   * ok       : score ≤ threshold
   * minor    : threshold < score ≤ 2×threshold
   * moderate : 2×threshold < score ≤ 3×threshold
   * high     : 3×threshold < score ≤ 50
   * critical : score > 50
   */
  public function severity(): Severity {
    if (!$this->hasViolation()) {
      return Severity::Ok;
    }

    if ($this->score > 50) {
      return Severity::Critical;
    }

    if ($this->score > 3 * $this->threshold) {
      return Severity::High;
    }

    if ($this->score > 2 * $this->threshold) {
      return Severity::Moderate;
    }

    return Severity::Minor;
  }

}
