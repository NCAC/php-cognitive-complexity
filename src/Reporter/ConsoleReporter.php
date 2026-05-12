<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reports results as human-readable console output.
 *
 * Example output:
 *   ✓ UserController.php (max: 12)
 *   ✗ OrderService::processOrder() → 18 (max: 15)
 *
 *   2 violations found. Complexity threshold exceeded.
 */
final class ConsoleReporter {

  public function __construct(private readonly OutputInterface $output) {
  }

  /**
   * @param list<AnalysisResult> $results
   * @param string|null          $baseline_file Path to baseline.json
   *
   * @return bool True if any violations exist (not suppressed by baseline)
   */
  public function report(array $results, ?string $baseline_file = null): bool {
    $baseline = $this->loadBaseline($baseline_file);
    $violations = 0;

    foreach ($results as $result) {
      if (!$result->hasViolation()) {
        continue;
      }

      // Suppress if already in baseline
      if (isset($baseline[$result->file][$result->function])
      && $baseline[$result->file][$result->function] >= $result->score
) {
  continue;
}

$violations++;
$relative = $this->relativePath($result->file);
$this->output->writeln(\sprintf(
  '<error> ✗ %s::%s → %d (max: %d) [line %d]</error>',
  $relative,
  $result->function,
  $result->score,
  $result->threshold,
  $result->line,
));
}

if ($violations === 0) {
  $this->output->writeln('<info>✓ No cognitive complexity violations found.</info>');

  return false;
}

$this->output->writeln('');
$this->output->writeln(\sprintf(
  '<error>%d violation(s) found. Cognitive complexity threshold exceeded.</error>',
  $violations
));

return true;
}

/**
 * @return array<string, array<string, int>>
 */
private function loadBaseline(?string $file): array {
  if ($file === null || !file_exists($file)) {
    return [];
  }

  $content = file_get_contents($file);
  if ($content === false) {
    return [];
  }

  /** @var array<string, array<string, int>> $data */
  $data = json_decode($content, true) ?? [];

  return $data;
}

private function relativePath(string $absolute): string {
  $cwd = getcwd();
  if ($cwd !== false && str_starts_with($absolute, $cwd)) {
    return ltrim(substr($absolute, \strlen($cwd)), '/');
  }

  return $absolute;
}

}
