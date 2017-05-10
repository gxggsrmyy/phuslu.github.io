#!/bin/bash

set -ex

pushd /tmp
wget https://github.com/git-lfs/git-lfs/releases/download/v2.1.0/git-lfs-linux-amd64-2.1.0.tar.gz
tar xvf git-lfs-linux-amd64-2.1.0.tar.gz
export PATH=$PATH:$(pwd)/git-lfs-2.1.0/
popd

rootdir=$(git rev-parse --show-toplevel)
cd ${rootdir}
git lfs update
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

if test -n "$MIRROR_REMOTE_URL" ; then
    rm -rf .git*
    git init
    git add --all .
    git commit -m "import github pages" -s -a
    git push "$MIRROR_REMOTE_URL" master -f
fi
