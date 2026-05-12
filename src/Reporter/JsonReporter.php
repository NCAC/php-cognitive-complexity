<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;

/**
 * Reports results in JSON format for programmatic consumption.
 *
 * Output example:
 * {
 *   "summary": { "violations": 2, "analyzed": 10 },
 *   "violations": [
 *     { "file": "src/Service/OrderService.php", "function": "processOrder", "score": 18, "threshold": 15, "line": 42 }
 *   ]
 * }
 */
final class JsonReporter {

  /**
   * @param list<AnalysisResult> $results
   *
   * @return bool True if any violations
   */
  public function report(array $results): bool {
    $violations = array_filter($results, static fn (AnalysisResult $r) => $r->hasViolation());

    $output = [
      'summary' => [
        'analyzed' => \count($results),
        'violations' => \count($violations),
      ],
      'violations' => array_values(array_map(
        static fn (AnalysisResult $r) => [
          'file' => $r->file,
          'function' => $r->function,
          'score' => $r->score,
          'threshold' => $r->threshold,
          'line' => $r->line,
        ],
        $violations
      )),
      'worst_offenders' => array_map(
        static fn (AnalysisResult $r) => [
          'file' => $r->file,
          'function' => $r->function,
          'score' => $r->score,
        ],
        \array_slice(
          array_reverse(
            usort($violations, static fn ($a, $b) => $a->score <=> $b->score) ? $violations : $violations
          ),
          0,
          10
        )
      ),
    ];

    echo (string) json_encode($output, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) . "\n";

    return \count($violations) > 0;
  }

}
