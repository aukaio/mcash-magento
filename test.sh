#!/bin/sh
set -e
set -o xtrace # Echo out the command before running it
find . -name "*.php" -not -path "./vendor/*"| xargs -n 1 php -l
set +e


set -e

php --version

for f in `find . -name "*test.php" -not -path "./vendor/*"`
do
    phpunit --verbose $f
done
