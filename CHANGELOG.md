# Changelog

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

