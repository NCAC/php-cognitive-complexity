# PHP Cognitive Complexity

[![CI](https://github.com/ncac/php-cognitive-complexity/actions/workflows/ci.yml/badge.svg)](https://github.com/ncac/php-cognitive-complexity/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/ncac/php-cognitive-complexity/graph/badge.svg)](https://codecov.io/gh/ncac/php-cognitive-complexity)
[![Packagist Version](https://img.shields.io/packagist/v/ncac/php-cognitive-complexity)](https://packagist.org/packages/ncac/php-cognitive-complexity)
[![PHP Version](https://img.shields.io/packagist/php-v/ncac/php-cognitive-complexity)](https://packagist.org/packages/ncac/php-cognitive-complexity)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

> A modern PHP CLI tool for measuring **cognitive complexity** of PHP code — ISO SonarQube (G. Ann Campbell's specification).

Two commands under one binary: **`check`** for CI/hooks (strict gate, exit 1 on violation), **`analyse`** for interactive exploration (coloured output, severity levels, always exit 0).

---

## Why cognitive complexity?

Cyclomatic complexity counts paths. **Cognitive complexity** measures how hard code is to _read_. It penalises deep nesting and rewards early returns — much closer to human perception of code quality.

This tool implements the [SonarSource specification](https://www.sonarsource.com/docs/CognitiveComplexity.pdf) for PHP.

---

## Installation

```bash
composer require --dev ncac/php-cognitive-complexity
```

---

## Usage

### `check` — CI gate

Strict mode: exits 1 on any violation. Designed for pre-commit hooks and CI pipelines.

```bash
# Basic check
vendor/bin/cognitive-complexity check src/ --max=15

# With a config file
vendor/bin/cognitive-complexity check src/ --config=cognitive.yaml

# Only git-modified files (pre-commit)
vendor/bin/cognitive-complexity check src/ --max=15 --diff

# JSON output
vendor/bin/cognitive-complexity check src/ --format=json

# GitLab Code Quality artifact
vendor/bin/cognitive-complexity check src/ --format=gitlab > gl-code-quality-report.json

# Drupal/multi-extension projects
vendor/bin/cognitive-complexity check web/ --ext=php,module,inc,theme,install
```

### `analyse` — interactive exploration

Developer-experience mode: coloured output grouped by file, severity labels, always exits 0.

```bash
# Show only violations (default)
vendor/bin/cognitive-complexity analyse src/

# Show all functions including those below threshold
vendor/bin/cognitive-complexity analyse src/ --all

# Sort by score descending (prioritise refactoring targets)
vendor/bin/cognitive-complexity analyse src/ --sort=score

# Custom threshold
vendor/bin/cognitive-complexity analyse src/ --max=20

# Drupal/multi-extension projects
vendor/bin/cognitive-complexity analyse web/ --ext=php,module,inc,theme,install
```

**Example output:**

```
── src/Service/OrderService.php
  [ 18]  processOrder:42 — minor
  [  4]  validateItems:91
  [  0]  formatResult:112

── src/Gateway/PaymentGateway.php
  [ 32]  validate:87 — moderate

✗ 2 violation(s) / 4 function(s) in 2 file(s) — threshold=15
```

**Severity levels** (multiples of threshold, default 15):

| Level      | Score range                 |
| ---------- | --------------------------- |
| `minor`    | threshold+1 → 2×threshold   |
| `moderate` | 2×threshold+1 → 3×threshold |
| `high`     | 3×threshold+1 → 50          |
| `critical` | > 50                        |

### Generate a baseline (ignore pre-existing violations)

```bash
vendor/bin/cognitive-complexity baseline src/ --output=baseline.json
vendor/bin/cognitive-complexity check src/ --baseline=baseline.json
```

---

## Configuration file

Create a `cognitive.yaml` at the root of your project:

```yaml
# Default threshold
max_complexity: 15

# Per-path threshold overrides (glob patterns, most specific wins)
paths:
  src/Controller/: 10
  src/Service/: 12
  tests/: 20

# Paths to exclude from analysis (glob patterns)
exclude:
  - vendor/
  - "**/files/php/" # Drupal compiled-Twig cache, any site
  - "**/files/styles/"

# File extensions to scan (default: php)
# CLI --ext overrides this list
extensions:
  - php
  - module
  - inc
  - theme
  - install

# Optional: override the project root (defaults to this file's directory)
# root: ..
```

### How `exclude:` and `paths:` are matched

> **v2 change.** In v1 these keys were matched relative to the path you passed on
> the CLI. As of **v2.0.0** they are matched relative to the **project root** and
> support glob wildcards. See [`MIGRATION.md`](MIGRATION.md).

- **Project root** = the directory containing `cognitive.yaml` (or the `root:`
  key, or the current directory when no config file is used). Every pattern and
  every reported path is relative to it, so `check web/`, `check web/sites/` and
  `check .` all match config the same way — the CLI argument only narrows what is
  walked.
- **Anchored.** `tests/` matches the top-level `tests/` directory, not
  `vendor/phpunit/phpunit/tests/`. Prefix with `**/` to match at any depth.
- **Wildcards:** `*` (within a path segment), `**` (spans directories),
  `?` (one character). Naming a directory covers its whole subtree.

| Pattern | Matches | Does not match |
| --- | --- | --- |
| `vendor/` | `vendor/autoload.php` | `app/vendor/x.php` |
| `**/files/php/` | `web/sites/aaa/files/php/twig/x.php` | `web/sites/aaa/files/phpstan.php` |
| `src/*/Legacy/` | `src/Billing/Legacy/X.php` | `src/Billing/Sub/Legacy/X.php` |
| `**/*.blade.php` | `resources/views/home.blade.php` | — |

**Notes:**

- `--diff` mode **honours** `exclude:` (it did not in v1).
- A `--config` path that does not exist is now an **error** (exit code 2), not a
  silent fallback to defaults.
- Reported paths (console, `--format=json`, `--format=gitlab`) and baseline file
  keys are project-root-relative. Regenerate baselines when upgrading from v1.

---

## Console output example

```
✗ src/Service/OrderService.php::processOrder → 18 (max: 15) [line 42]
✗ src/Gateway/PaymentGateway.php::validate → 16 (max: 15) [line 87]

2 violation(s) found. Cognitive complexity threshold exceeded.
```

This is the output of `check`. For richer output, use [`analyse`](#analyse--interactive-exploration).

---

## Integration in your project

Add convenience scripts to your `composer.json` for fixed targets:

```json
{
  "scripts": {
    "cc": "cognitive-complexity check src/ --max=15",
    "cc:analyse": "cognitive-complexity analyse src/ --all --sort=score",
    "cc:baseline": "cognitive-complexity baseline src/ --max=15 --output=cognitive-baseline.json"
  }
}
```

```bash
composer cc              # CI gate — exit 1 on violations
composer cc:analyse      # interactive report sorted by score
composer cc:baseline     # regenerate the baseline after a refactoring
```

For ad-hoc analysis on a specific path, call `cc` — the short wrapper installed alongside the main binary:

```bash
# cc defaults to 'check' when no sub-command is given
vendor/bin/cc src/
vendor/bin/cc src/ --max=20
vendor/bin/cc src/ --diff                        # pre-commit: git-modified files only

# Explicit sub-commands
vendor/bin/cc analyse web/modules/custom/ --all
vendor/bin/cc analyse web/modules/custom/ --sort=score --ext=php,module,inc
vendor/bin/cc baseline src/ --output=cognitive-baseline.json
```

To ignore pre-existing violations during onboarding, generate a baseline first:

```bash
composer cc:baseline
# then add --baseline to your cc script:
# "cc": "cognitive-complexity check src/ --max=15 --baseline=cognitive-baseline.json"
```

---

```sh
# .husky/pre-commit
vendor/bin/cognitive-complexity check src/ --max=15
```

---

## GitLab CI/CD integration

```yaml
# .gitlab-ci.yml
cognitive_complexity:
  stage: quality
  script:
    - php vendor/bin/cognitive-complexity check src/ --format=gitlab > gl-code-quality-report.json
  artifacts:
    reports:
      codequality: gl-code-quality-report.json
```

---

## Algorithm

Based on G. Ann Campbell's Cognitive Complexity specification:

| Structure                               | Increment    |
| --------------------------------------- | ------------ |
| `if`, `else if`, `else`                 | +1           |
| `for`, `foreach`, `while`, `do-while`   | +1           |
| `switch`                                | +1           |
| `catch`                                 | +1           |
| Ternary `?:`                            | +1           |
| `&&`, `\|\|` (logical sequence changes) | +1           |
| **Each nested level**                   | +level bonus |

---

## Development

```bash
git clone https://github.com/ncac/php-cognitive-complexity.git
cd php-cognitive-complexity
composer install

# Run tests
composer test

# Run all checks
composer check

# Coverage
composer test-coverage
```

---

## License

MIT © [Nicolas Catrice-Auzon Cape](https://gravatar.com/devncac)
