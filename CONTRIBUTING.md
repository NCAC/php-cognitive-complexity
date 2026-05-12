# Contributing to ncac/php-cognitive-complexity

Thank you for considering a contribution! 🎉

## Development Setup

```bash
git clone https://github.com/ncac/php-cognitive-complexity.git
cd php-cognitive-complexity
composer install
pnpm install   # or npm install — for Husky + release-it
```

## Commit Convention

This project uses **Conventional Commits**:

```
type(scope): description
```

Valid types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `build`, `ci`, `perf`, `revert`, `release`

Examples:
- `feat(analyzer): add nesting-level bonus for switch blocks`
- `fix(reporter): correct exit code when baseline suppresses all violations`
- `test(config): add edge case for overlapping path prefixes`

## Code Quality

Before submitting a PR, make sure all checks pass:

```bash
composer check   # psalm + phpcs + phpunit
```

## Pull Request Guidelines

- One feature/fix per PR
- Add tests for any new behaviour
- Update `CHANGELOG.md` under `[Unreleased]`
- Ensure CI is green

## Releasing

Releases are handled via `release-it`:

```bash
npx release-it
```

This will:
1. Run `composer check` (Psalm + CS + Tests)
2. Bump version in `composer.json` and `package.json`
3. Update `CHANGELOG.md`
4. Create a Git tag
5. Push to GitHub (CI will notify Packagist)
