# PHP Cognitive Complexity

[![CI](https://github.com/ncac/php-cognitive-complexity/actions/workflows/ci.yml/badge.svg)](https://github.com/ncac/php-cognitive-complexity/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/ncac/php-cognitive-complexity/graph/badge.svg)](https://codecov.io/gh/ncac/php-cognitive-complexity)
[![Packagist Version](https://img.shields.io/packagist/v/ncac/php-cognitive-complexity)](https://packagist.org/packages/ncac/php-cognitive-complexity)
[![PHP Version](https://img.shields.io/packagist/php-v/ncac/php-cognitive-complexity)](https://packagist.org/packages/ncac/php-cognitive-complexity)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

> A modern PHP CLI tool for measuring **cognitive complexity** of PHP code — ISO SonarQube (G. Ann Campbell's specification).

Designed to integrate seamlessly into CI/CD pipelines and Husky pre-commit hooks.

---

## Why cognitive complexity?

Cyclomatic complexity counts paths. **Cognitive complexity** measures how hard code is to *read*. It penalises deep nesting and rewards early returns — much closer to human perception of code quality.

This tool implements the [SonarSource specification](https://www.sonarsource.com/docs/CognitiveComplexity.pdf) for PHP.

---

## Installation

```bash
composer require --dev ncac/php-cognitive-complexity
```

---

## Usage

### Basic check

```bash
php vendor/bin/cognitive-complexity check src/ --max=15
# Exit 0 if OK, Exit 1 if threshold exceeded
```

### With a config file

```bash
php vendor/bin/cognitive-complexity check src/ --config=cognitive.yaml
```

### Analyze only git-modified files (perfect for pre-commit)

```bash
php vendor/bin/cognitive-complexity check src/ --max=15 --diff
```

### JSON output (for programmatic consumption)

```bash
php vendor/bin/cognitive-complexity check src/ --format=json | jq '.worst_offenders'
```

### GitLab Code Quality artifact

```bash
php vendor/bin/cognitive-complexity check src/ --format=gitlab > gl-code-quality-report.json
```

### Generate a baseline (ignore pre-existing violations)

```bash
php vendor/bin/cognitive-complexity baseline src/ --output=baseline.json
php vendor/bin/cognitive-complexity check src/ --baseline=baseline.json
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
```

---

## Console output example

```
✗ src/Service/OrderService.php::processOrder → 18 (max: 15) [line 42]
✗ src/Gateway/PaymentGateway.php::validate → 16 (max: 15) [line 87]

2 violation(s) found. Cognitive complexity threshold exceeded.
```

---

## Husky integration (pre-commit)

```json
// package.json
{
  "husky": {
    "hooks": {
      "pre-commit": "php vendor/bin/cognitive-complexity check src/ --max=15"
    }
  }
}
```

Or with Husky v9:

```sh
# .husky/pre-commit
php vendor/bin/cognitive-complexity check src/ --max=15
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

| Structure | Increment |
|---|---|
| `if`, `else if`, `else` | +1 |
| `for`, `foreach`, `while`, `do-while` | +1 |
| `switch` | +1 |
| `catch` | +1 |
| Ternary `?:` | +1 |
| `&&`, `\|\|` (logical sequence changes) | +1 |
| **Each nested level** | +level bonus |

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
