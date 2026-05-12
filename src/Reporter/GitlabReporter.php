<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;

/**
 * Reports results in GitLab Code Quality format.
 *
 * @see https://docs.gitlab.com/ee/ci/testing/code_quality.html#implement-a-custom-tool
 *
 * Output: JSON array of issue objects to be used as a GitLab artifact.
 *
 * Usage in .gitlab-ci.yml:
 *   artifacts:
 *     reports:
 *       codequality: gl-code-quality-report.json
 */
final class GitlabReporter {

  /**
   * @param list<AnalysisResult> $results
   *
   * @return bool True if any violations
   */
  public function report(array $results): bool {
    $violations = array_filter($results, static fn (AnalysisResult $r) => $r->hasViolation());

    $issues = array_values(array_map(static fn (AnalysisResult $r) => [
      'type' => 'issue',
      'check_name' => 'CognitiveComplexity',
      'description' => \sprintf(
        'Cognitive complexity of %s is %d, exceeds threshold %d.',
        $r->function,
        $r->score,
        $r->threshold
      ),
      'categories' => ['Complexity'],
      'severity' => $r->score > ($r->threshold * 2) ? 'critical' : 'major',
      'location' => [
        'path' => $r->file,
        'lines' => ['begin' => $r->line],
      ],
      'fingerprint' => md5($r->file . '::' . $r->function),
    ], $violations));

    echo (string) json_encode($issues, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) . "\n";

    return \count($violations) > 0;
  }

}
