# Changelog

# [2.0.0](https://github.com/ncac/php-cognitive-complexity/compare/v1.2.0...v2.0.0) (2026-09-01)


### chore

* better readability and ncac/phpcs-standard compliant ([](https://github.com/ncac/php-cognitive-complexity/commit/4fd41d769abb90ef92b11b8a31db45cef28c2f5f))


### docs

* RFC 0001 — project-relative path matching for v2 ([](https://github.com/ncac/php-cognitive-complexity/commit/e28ab7b52e6c37f6b437b50f89d5366ba308abe6))


### feat

* **config:** project-relative glob path matching in cognitive.yaml (v2) ([](https://github.com/ncac/php-cognitive-complexity/commit/8b1b65b3caa8810873ab63e96b4fd396f3c38312)), closes [#7](https://github.com/ncac/php-cognitive-complexity/issues/7)
* update composer packages ([](https://github.com/ncac/php-cognitive-complexity/commit/541b06fcb5e0f7526b48ea6b911aa7b69cd4384b))


### refactor

* **config:** drop unreachable branches flagged by patch coverage ([](https://github.com/ncac/php-cognitive-complexity/commit/59a7020611bbb04f0d7f21d0cc422982db1cc2b8)), closes [#7](https://github.com/ncac/php-cognitive-complexity/issues/7)


### test

* **config:** close v2 path-matching coverage gaps ([](https://github.com/ncac/php-cognitive-complexity/commit/82ac572641ed36650e1afe0b6ebbd2403b805643)), closes [#7](https://github.com/ncac/php-cognitive-complexity/issues/7)
* **config:** cover v2 path-matching behaviour across CLI + reporters ([](https://github.com/ncac/php-cognitive-complexity/commit/3444b68defeb63f0e890b7172432de03ff6215ed)), closes [#7](https://github.com/ncac/php-cognitive-complexity/issues/7)


### BREAKING CHANGE

* **config:** exclude:/paths: keys are matched relative to the project
root (the cognitive.yaml directory, or the new root: key) instead of the
CLI argument, and are now anchored glob patterns (*, **, ?). Reported
paths and baseline keys become project-root-relative; a missing --config
file is a hard error (exit 2).

- add PathMatcher (glob -> anchored regex, ** spans dirs, **/ = any depth)
- Config: projectRoot, isExcluded(), glob-aware getThresholdForPath(),
  getExcludedPaths() -> getExcludePatterns()
- ConfigLoader: resolve project root (root: key / config dir / cwd),
  throw ConfigException on missing explicit --config or bad YAML
- CognitiveAnalyzer: root-relative paths everywhere; one Finder prune
  closure filters + prunes excluded dirs; --diff honours exclude:
- reporters: print AnalysisResult::file verbatim (already root-relative)
- AnalyseCommand: stop discarding paths: overrides
- Application: VERSION 2.0.0
- docs: RFC 0001, MIGRATION.md, README + cognitive.yaml.example, CHANGELOG
- tests: PathMatcherTest, exclude/diff/scan-arg-independence coverage

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
