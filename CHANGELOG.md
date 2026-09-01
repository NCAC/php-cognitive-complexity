# Changelog

# [2.0.0](https://github.com/ncac/php-cognitive-complexity/compare/v1.2.0...v2.0.0) (unreleased)

Path matching in `cognitive.yaml` is reworked. See [`MIGRATION.md`](MIGRATION.md)
and [`docs/rfc/0001-project-relative-path-matching.md`](docs/rfc/0001-project-relative-path-matching.md).

### ⚠ BREAKING CHANGES

* **config:** `exclude:` and `paths:` keys are now matched relative to the
  **project root** (the directory holding `cognitive.yaml`, or the new `root:`
  key), not relative to the path passed to `check` / `analyse`.
* **config:** patterns are now **anchored** globs — `*`, `**`, `?` are supported;
  `tests/` no longer matches `vendor/**/tests/`. Prefix with `**/` for "any depth".
* **config:** a `--config` file that does not exist is now an error (exit code 2)
  instead of silently falling back to defaults.
* **output:** reported paths (`console`, `json`, `gitlab`) and baseline file keys
  are now project-root-relative. Existing baselines must be regenerated.
* **api:** `Config::getExcludedPaths()` renamed to `Config::getExcludePatterns()`;
  `Config::__construct()` gains a trailing `string $projectRoot` argument;
  `Config::getThresholdForPath()` / new `Config::isExcluded()` expect
  project-root-relative paths.

### feat

* **config:** glob wildcards (`*`, `**`, `?`) in `exclude:` and `paths:`.
* **config:** `root:` key to override the project root.
* **diff:** `--diff` mode now honours `exclude:` patterns.
* **config:** fail fast with a clear message when `--config` points nowhere.

### fix

* **config:** `paths:` threshold overrides now actually apply to directory scans
  (they were compared against absolute paths and never matched).
* **analyse:** the `analyse` command no longer discards `paths:` overrides.

# [1.2.0](https://github.com/ncac/php-cognitive-complexity/compare/v1.1.0...v1.2.0) (2026-06-01)


### docs

* check with ext options ([](https://github.com/ncac/php-cognitive-complexity/commit/3d679a7929db4eb04cf092a9b3db14e0927bb95e))


### feat

* **check:** add --ext option and YAML extensions support ([](https://github.com/ncac/php-cognitive-complexity/commit/89a25b6154746c23649185d7652b4a25c77ce21f))


### refactor

* **ConfigLoader:** extract helpers to reduce cognitive complexity ([](https://github.com/ncac/php-cognitive-complexity/commit/faafbfe22e28a183fac1cb60f24865720a9e08d5))


### test

* **Config:** cover withExtensions immutability and field preservation ([](https://github.com/ncac/php-cognitive-complexity/commit/87b5bc0a401ade82a8864517bf97eb9bfafdbde8))

# [1.1.0](https://github.com/ncac/php-cognitive-complexity/compare/v1.0.0...v1.1.0) (2026-05-12)


### feat

* add analyse command with severity levels, --all, --sort, --ext ([](https://github.com/ncac/php-cognitive-complexity/commit/f57d3aa3504ac4e3b96f626b841ce8414a2c34ae))
* **bin:** add cc shell wrapper with auto check default and sub-command passthrough ([](https://github.com/ncac/php-cognitive-complexity/commit/bf4650acabdddf3a17b3d105f8f2147fd94ea9a1))


### test

* cover Config::getExtensions and Application command registration ([](https://github.com/ncac/php-cognitive-complexity/commit/393ea950fb4ef53a7499787c2afb841efdc0b417))

All notable changes to this project will be documented in this file.

The format is based on [Conventional Commits](https://www.conventionalcommits.org/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-05-12

### ✨ Features

- `check` command — analyses cognitive complexity of PHP files, exits 1 on violations
- `baseline` command — snapshots existing violations to a JSON file
- Console (coloured), JSON, and GitLab Code Quality reporters
- `cognitive.yaml` configuration with global and per-path thresholds
- Baseline support to suppress pre-existing violations
- `--diff` mode to analyse only git-modified files

### 🧠 Algorithm

Full implementation of the [SonarSource Cognitive Complexity white paper](https://www.sonarsource.com/docs/CognitiveComplexity.pdf):

- Structural increments with nesting bonus (`if`, `for`, `foreach`, `while`, `do`, `switch`, `try`)
- Flat continuations +1 (`else if`, `else`, `catch`), `finally` ignored
- Logical operator family sequences (`&&` / `||` / `and` / `or`)
- Ternary operator `?:`
- Closures and arrow functions (+1 + nesting bonus, scope reset)
- Direct recursive calls (+1 flat)
- `break N` / `continue N` / `goto` (+1 flat)

### 🔧 Toolchain

- PHP 8.2+ · PHPUnit 11 · Psalm 6 · php-cs-fixer 3.64 · phing 3
- 85 tests, 174 assertions, >97% line coverage
- Husky pre-commit hook (the project validates itself)
- GitHub Actions CI (Psalm, PHPCS, PHPUnit, Codecov)

[1.0.0]: https://github.com/NCAC/php-cognitive-complexity/releases/tag/v1.0.0
