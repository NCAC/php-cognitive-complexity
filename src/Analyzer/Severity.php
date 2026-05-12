<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Analyzer;

/**
 * Severity levels for a cognitive complexity result.
 */
enum Severity: string {

  case Ok = 'ok';
  case Minor = 'minor';
  case Moderate = 'moderate';
  case High = 'high';
  case Critical = 'critical';

  public function label(): string {
    return $this->value;
  }

}
