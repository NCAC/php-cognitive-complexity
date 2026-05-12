# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Conventional Commits](https://www.conventionalcommits.org/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### ✨ Features
- Initial implementation of `check` command with cognitive complexity analysis
- Support for `--diff` mode (git-modified files only)
- Console, JSON and GitLab Code Quality reporters
- `baseline` command to snapshot existing violations
- `cognitive.yaml` configuration with per-path thresholds
- Husky pre-commit and commit-msg hooks
- GitHub Actions CI with Psalm, PHPCS, PHPUnit and Codecov
