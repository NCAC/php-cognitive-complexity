<?php

declare(strict_types=1);

namespace NCAC\CognitiveComplexity\Reporter;

use NCAC\CognitiveComplexity\Analyzer\AnalysisResult;
use NCAC\CognitiveComplexity\Analyzer\Severity;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Developer-experience oriented reporter.
 *
 * Features:
 *  - Results grouped by file
 *  - Colour-coded severity levels (ok / minor / moderate / high / critical)
 *  - Shows all functions when $show_all=true, violations-only otherwise
 *  - Sorted by score descending when $sort_by_score=true
 *  - Summary line with violation / function / file counts
 *  - Always returns false (exit 0) — exploration tool, not a gate
 */
final class RichConsoleReporter {

  /** ANSI colour codes indexed by Severity */
  private const COLORS = [
    Severity::Ok->value       => "\033[0;32m",  // green
    Severity::Minor->value    => "\033[0;33m",  // yellow
    Severity::Moderate->value => "\033[0;33m",  // yellow-orange
    Severity::High->value     => "\033[0;31m",  // red
    Severity::Critical->value => "\033[1;31m",  // bold red
  ];

  private const RESET = "\033[0m";

  private const DIM = "\033[90m";

  /**
   * @param list<AnalysisResult> $results
   * @param list<string>         $parse_errors  ['file:message', ...]
   */
  public function report(
    OutputInterface $output,
    array $results,
    bool $show_all = false,
    bool $sort_by_score = false,
    array $parse_errors = [],
  ): void {
    if ($sort_by_score) {
      usort($results, static fn (AnalysisResult $a, AnalysisResult $b) => $b->score <=> $a->score);
    }

    $cwd = (string) getcwd();
    $current_file = '';
    $violation_count = 0;
    $function_count = 0;
    $file_set = [];
    $threshold = 15;

    foreach ($results as $result) {
      $threshold = $result->threshold;
      $is_violation = $result->hasViolation();

      if (!$show_all && !$is_violation) {
        continue;
      }

      $function_count++;
      if ($is_violation) {
        $violation_count++;
      }

      $file_set[$result->file] = true;

      if (!$sort_by_score && $result->file !== $current_file) {
        $current_file = $result->file;
        $output->writeln('');
        $output->writeln(self::DIM . '── ' . str_replace($cwd . '/', '', $current_file) . self::RESET);
      }

      $this->writeLine($output, $result, $sort_by_score, $cwd);
    }

    foreach ($parse_errors as $error) {
      $output->writeln(self::COLORS[Severity::High->value] . 'Parse error' . self::RESET . ' ' . $error);
    }

    $output->writeln('');
    $this->writeSummary($output, $violation_count, $function_count, \count($file_set), $threshold);
  }

  private function writeLine(OutputInterface $output, AnalysisResult $result, bool $sort_by_score, string $cwd): void {
    $severity = $result->severity();
    $color = self::COLORS[$severity->value];
    $score_fmt = sprintf('[%3d]', $result->score);
    $prefix = $sort_by_score
      ? self::DIM . str_replace($cwd . '/', '', $result->file) . ':' . self::RESET
      : '';

    if ($result->hasViolation()) {
      $output->writeln(sprintf(
        '  %s%s%s  %s%s:%d — %s',
        $color, $score_fmt, self::RESET,
        $prefix, $result->function, $result->line,
        $severity->label(),
      ));
    } else {
      $output->writeln(sprintf(
        '  %s%s%s  %s%s:%d',
        $color, $score_fmt, self::RESET,
        $prefix, $result->function, $result->line,
      ));
    }
  }

  private function writeSummary(
    OutputInterface $output,
    int $violations,
    int $functions,
    int $files,
    int $threshold,
  ): void {
    if ($violations === 0) {
      $output->writeln(
        "\033[1;32m✓ No violations\033[0m"
        . " — {$functions} function(s) in {$files} file(s), threshold={$threshold}"
      );

      return;
    }

    $output->writeln(
      "\033[1;31m✗ {$violations} violation(s)\033[0m"
      . " / {$functions} function(s) in {$files} file(s)"
      . " — threshold={$threshold}"
    );
  }

}
