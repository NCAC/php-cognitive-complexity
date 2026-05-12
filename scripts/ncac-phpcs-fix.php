#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * NCAC Fix - Script to enforce NCAC coding standards compliance
 *
 * Usage:
 *   composer ncac-fix [path] [options]
 *
 * Options:
 *   --dry-run    Preview changes without applying them
 *   --check      Check only, without fixing
 *   --help       Display this help message
 */

// Terminal color codes
const COLOR_RESET = "\033[0m";
const COLOR_BOLD = "\033[1m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RED = "\033[31m";
const COLOR_CYAN = "\033[36m";

// Base path for the project
$base_path = \dirname(__DIR__);
$vendor_bin = $base_path . '/vendor/bin';
$ncac_script_sh = $base_path . '/vendor/ncac/phpcs-standard/scripts/ncac-fix.sh';

// Parse command line arguments
$args = \array_slice($argv, 1);
$options = [];
$path = null;

foreach ($args as $arg) {
  if (str_starts_with($arg, '--')) {
    $options[] = $arg;
  } elseif ($path === null) {
    $path = $arg;
  }
}

// Display help
if (\in_array('--help', $options)) {
  show_help();
  exit(0);
}

// Determine the target path to fix
$target_path = $path ?? '.';
$full_path = realpath($base_path . '/' . $target_path);

if (!$full_path || !file_exists($full_path)) {
  error("❌ The path '$target_path' does not exist.");
  exit(1);
}

// Verify that the ncac-fix.sh script exists
if (!file_exists($ncac_script_sh)) {
  error("❌ The NCAC fix script was not found at '$ncac_script_sh'. Please ensure you have installed the NCAC PHP CodeSniffer standard.");
  exit(1);
}

// Display header
show_header($target_path, $options);

// Ensure the script has executable permissions
if (!is_executable($ncac_script_sh)) {
  exec('chmod +x ' . escapeshellarg($ncac_script_sh));
}

// Compute the relative path from $base_path for phpcs/phpcbf
$relative_path = $full_path;
if (str_starts_with($full_path, $base_path . '/')) {
  $relative_path = substr($full_path, \strlen($base_path) + 1);
} elseif ($full_path === $base_path) {
  $relative_path = '.';
}

// Check-only mode: run phpcs without applying fixes
if (\in_array('--check', $options)) {
  info("🔍 Running code analysis (phpcs.xml)...\n");
  $cmd = \sprintf(
    'cd %s && %s --standard=phpcs.xml %s',
    escapeshellarg($base_path),
    escapeshellarg($vendor_bin . '/phpcs'),
    escapeshellarg($relative_path)
  );
  passthru($cmd, $exit_code);

  if ($exit_code === 0) {
    echo "\n";
    success('✅ No errors detected!');
  } else {
    echo "\n";
    error("❌ Errors detected (exit code: $exit_code).");
  }
  exit($exit_code);
}

// Manual NCAC workflow (replaces the .sh script which has path resolution issues)
// Determine the PHP-CS-Fixer configuration to use
$php_cs_fixer_config = $base_path . '/.php-cs-fixer.php';
if (!file_exists($php_cs_fixer_config)) {
  $php_cs_fixer_config = $base_path . '/vendor/ncac/phpcs-standard/.php-cs-fixer.dist.php';
}

$is_dry_run = \in_array('--dry-run', $options);

if ($is_dry_run) {
  // Preview mode: run PHP-CS-Fixer with --dry-run
  info("➜ Previewing changes with PHP-CS-Fixer...\n");
  $cmd = \sprintf(
    'cd %s && %s fix --dry-run --diff --config=%s --path-mode=intersection %s',
    escapeshellarg($base_path),
    escapeshellarg($vendor_bin . '/php-cs-fixer'),
    escapeshellarg($php_cs_fixer_config),
    escapeshellarg($full_path)
  );
  passthru($cmd, $exit_code);

  if ($exit_code === 0) {
    success("\n✅ Preview complete!");
    info('💡 To apply the changes, re-run without --dry-run');
  }
  exit($exit_code);
}

// Full fix mode
info("➜ Step 1/3: Applying PHP-CS-Fixer...\n");
$cmd = \sprintf(
  'cd %s && %s fix --config=%s --path-mode=intersection %s 2>&1',
  escapeshellarg($base_path),
  escapeshellarg($vendor_bin . '/php-cs-fixer'),
  escapeshellarg($php_cs_fixer_config),
  escapeshellarg($full_path)
);
passthru($cmd, $exit_code_1);

info("\n➜ Step 2/3: Applying PHPCBF (with phpcs.xml rules)...\n");
$cmd = \sprintf(
  'cd %s && %s --standard=phpcs.xml %s 2>&1 || true',
  escapeshellarg($base_path),
  escapeshellarg($vendor_bin . '/phpcbf'),
  escapeshellarg($relative_path)
);
passthru($cmd);

info("\n➜ Step 3/3: Validating with PHPCS (with phpcs.xml rules)...\n");
$cmd = \sprintf(
  'cd %s && %s --standard=phpcs.xml %s 2>&1',
  escapeshellarg($base_path),
  escapeshellarg($vendor_bin . '/phpcs'),
  escapeshellarg($relative_path)
);
passthru($cmd, $exit_code);

// Final status message
if ($exit_code === 0) {
  echo "\n";
  success('✅ Completed successfully!');
  if ($is_dry_run) {
    echo "\n" . info('💡 To apply the changes, re-run without --dry-run', false) . "\n";
  }
} else {
  echo "\n";
  error("❌ Errors occurred (exit code: $exit_code). Please review the messages above.");
}

exit($exit_code);

// === Utility functions ===

/**
 * Display help information
 */
function show_help(): void {
  echo COLOR_BOLD . "Usage:\n" . COLOR_RESET;
  echo "  composer ncac-fix [path] [options]\n\n";
  echo COLOR_BOLD . "Options:\n" . COLOR_RESET;
  echo "  --dry-run    Preview changes without applying them\n";
  echo "  --check      Check only, without fixing\n";
  echo "  --help       Display this help message\n\n";
}

/**
 * Print an info message to stdout
 */
function info(string $message, bool $print = true): string {
  $output = COLOR_BLUE . $message . COLOR_RESET;
  if ($print) {
    echo $output;
  }
  return $output;
}

/**
 * Print a success message to stdout
 */
function success(string $message): void {
  echo COLOR_GREEN . COLOR_BOLD . $message . COLOR_RESET . "\n";
}

/**
 * Print an error message to stderr
 */
function error(string $message): void {
  fwrite(STDERR, COLOR_RED . COLOR_BOLD . $message . COLOR_RESET . "\n");
}

/**
 * Display the script header
 *
 * @param array<string> $options
 */
function show_header(string $path, array $options): void {
  if (\in_array('--check', $options)) {
    $mode = 'CHECK';
    $mode_color = COLOR_CYAN;
  } elseif (\in_array('--dry-run', $options)) {
    $mode = 'PREVIEW';
    $mode_color = COLOR_YELLOW;
  } else {
    $mode = 'FIX';
    $mode_color = COLOR_GREEN;
  }
  echo "\n";
  echo COLOR_BOLD . COLOR_CYAN . "╔═══════════════════════════════════════════════════════════════╗\n" . COLOR_RESET;
  echo COLOR_BOLD . COLOR_CYAN . '║' . COLOR_RESET . COLOR_BOLD . '  NCAC Code Quality - Coding Standards Enforcement' . str_repeat(' ', 11) . COLOR_CYAN . " ║\n" . COLOR_RESET;
  echo COLOR_BOLD . COLOR_CYAN . "╚═══════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
  echo "\n";
  echo COLOR_BOLD . '📂 Target : ' . COLOR_RESET . COLOR_BLUE . $path . COLOR_RESET . "\n";
  echo COLOR_BOLD . '⚙️  Mode   : ' . COLOR_RESET . $mode_color . COLOR_BOLD . $mode . COLOR_RESET . "\n";
  echo "\n";
}
