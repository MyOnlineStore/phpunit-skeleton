#!/bin/bash

FILES=$(git diff-tree --no-commit-id --name-only --diff-filter=A -r $1 -- '*.php')

for file in $FILES
do
    if ! echo "$file" | grep -qi "Interface"; then
        ~/.composer/vendor/bin/phpunit-skeleton.phar create $file;
    fi
done
