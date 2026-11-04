#!/bin/bash
# Run on Save de VS Code (emeraldwalk.runonsave) : formate le fichier sauvegardé
# au standard NCAC (PHP-CS-Fixer, PHPCBF, puis validation PHPCS).
# ncac-format accepte les chemins absolus et détecte seul la racine du projet.
cd "$(dirname "$0")/.." || exit 3
exec vendor/bin/ncac-format -- "$1"
