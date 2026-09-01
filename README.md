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

# Per-path overrides
paths:
  src/Controller/: 10
  src/Service/: 12
  tests/: 20

# Excluded paths
exclude:
  - vendor/
  - cache/
  - legacy/

# File extensions to scan (default: php)
# CLI --ext overrides this list
extensions:
  - php
  - module
  - inc
  - theme
  - install
```

### How `exclude:` and `paths:` are matched

Both `exclude:` and the `paths:` keys are **prefixes matched against each file's path
relative to the argument you pass to `check` / `analyse`** — *not* relative to the
project root.

So if you run:

```bash
vendor/bin/cognitive-complexity check web/sites/ --config=cognitive.yaml
```

a file on disk at `web/sites/foo/files/php/twig_xxx.php` is seen by the matcher as
`foo/files/php/twig_xxx.php`. Your patterns must start from there:

| Command | Correct `exclude:` entry | Wrong (never matches) |
| --- | --- | --- |
| `check web/` | `sites/foo/files/php/` | `web/sites/foo/files/php/` |
| `check web/sites/` | `foo/files/php/` | `sites/foo/files/php/` |
| `check web/sites/foo/` | `files/php/` | `sites/foo/files/php/` |

An `exclude:` entry with a `/` in it matches that sub-path **anywhere** in the
relative path, so a single suffix covers every site in a multi-site tree:

```yaml
# Run as: check web/sites/  — excludes the compiled-Twig cache of ALL sites
exclude:
  - files/php/
  - files/styles/
```

**Limitations:**

- **No globs.** `*`, `**`, `?` are treated literally. Only plain directory paths work.
- **No leading `./`** — write `files/php/`, not `./files/php/`.
- **`--diff` mode ignores `exclude:`.** When analysing only git-modified files, the
  exclusion list is not applied; filter those paths in your hook/CI instead.
- **`--config` is resolved relative to the current working directory.** If the file
  is not found (or a key is misspelled, e.g. `excludes:`), the tool silently falls
  back to defaults with **no exclusions and no warning**. Prefer an absolute path in
  build scripts.

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
