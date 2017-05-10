#!/bin/bash

set -ex
rootdir=$(git rev-parse --show-toplevel)
cd ${rootdir}
find . -type d | grep -vE '^(\./\.)' | xargs -n1 -i python .travis/gen_index_html.py {}
find . -type f -name "index.html" -exec git add "{}" \;

test -z "$(git diff HEAD)" && exit 0
git config user.name "travis-ci-build"
git config user.email "travis.ci.build@gmail.com"
git branch -D master || true
git checkout -b master
git commit -m "[skip ci]: gen index.html" -s -a
set +ex
git push https://phuslu:${GITHUB_TOKEN}@github.com/phuslu/phuslu.github.io.git master
test -n "$MIRROR_REMOTE_URL" && git push "$MIRROR_REMOTE_URL" master -f
