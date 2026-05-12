#!/bin/bash
# Format PHP file on save - runs NCAC formatting workflow on the saved file
FILE="$1"
PROJECT_ROOT="/workspace"

# Extract relative path from project root
if [[ "$FILE" == "$PROJECT_ROOT/"* ]]; then
  RELATIVE_PATH="${FILE#$PROJECT_ROOT/}"
else
  RELATIVE_PATH="$FILE"
fi

cd "$PROJECT_ROOT"

# Run ncac-format on the single saved file
vendor/bin/ncac-format "$RELATIVE_PATH" > /dev/null 2>&1
