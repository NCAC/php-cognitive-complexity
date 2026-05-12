#!/bin/bash

cd /workspace

# Ensure this folder is a trusted git repository
git config --global safe.directory '*'

# Allow git to track files renamed with different case
git config --local core.ignorecase false

# Ignore git track filemode differences (Docker environments)
git config --local core.filemode false

# Install Node.js packages (release-it, husky)
pnpm i

# Install PHP dependencies
composer install
composer dump-autoload

# Initialize Husky hooks
pnpm exec husky || true

# Ensure hooks are executable
chmod +x .husky/commit-msg 2>/dev/null || true
chmod +x .husky/pre-commit 2>/dev/null || true

echo "✅ Post-create command script finished."
# end of script
