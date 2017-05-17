#!/bin/bash

set -ex

rootdir=$(git rev-parse --show-toplevel)
cd ${rootdir}

if test -n "$MIRROR_URL" ; then
    pushd goproxy
    rm -rf $(ls goproxy_*.url | grep -v "$(ls -1 goproxy_*.url | sort | tail -1 | grep -o 'r[0-9][0-9][0-9][0-9]')")
    for FILE in goproxy_*.url
    do
        SIZE=$(cat ${FILE} | awk -F= '/^SIZE=/{print $2;exit}')
        cat <<EOF > ${FILE}
[InternetShortcut]
URL=https://coding.net/u/phuslu/p/phuslu.coding.me/git/raw/master/goproxy/${FILE%.*}
SIZE=${SIZE}
EOF
    done
    popd
fi

find . -type d | grep -vE '^(\./\.)' | xargs -n1 -i python .travis/gen_index_html.py {}
find . -type f -name "index.html" -or -name "*.md.html" -exec git add "{}" \;

test -z "$(git diff HEAD)" && exit 0
git config user.name "travis-ci-build"
git config user.email "travis.ci.build@gmail.com"
git branch -D master || true
git checkout -b master
git commit -m "[skip ci]: gen index.html" -s -a

set +ex
git push https://phuslu:${GITHUB_TOKEN}@github.com/phuslu/phuslu.github.io.git master
set -ex

if test -n "$MIRROR_URL" ; then
    cd goproxy
    for FILE in goproxy_*.url
    do
        RELEASE=$(echo ${FILE} | grep -oP 'r\K[1-9][0-9][0-9][0-9]')
        URL=https://github.com/phuslu/goproxy-ci/releases/download/r${RELEASE}/${FILE%.*}
        wget ${URL}
    done

    xz -d goproxy_linux_amd64-r${RELEASE}.tar.xz
    mkdir -p goproxy && cp gae.user.json goproxy/
    tar rvf goproxy_linux_amd64-r${RELEASE}.tar goproxy/gae.user.json
    rm -rf goproxy
    xz -9 goproxy_linux_amd64-r${RELEASE}.tar

    bzip2 -d goproxy_macos_app-r${RELEASE}.tar.bz2
    mkdir -p GoProxy.app/Contents/MacOS && cp gae.user.json GoProxy.app/Contents/MacOS/
    tar rvf goproxy_macos_app-r${RELEASE}.tar GoProxy.app/Contents/MacOS/gae.user.json
    rm -rf GoProxy.app/Contents/MacOS
    bzip2 -9 goproxy_macos_app-r${RELEASE}.tar

    7za a -t7z -mmt -mx9 -y goproxy_windows_amd64-r${RELEASE}.7z gae.user.json

    git add --all .
    git commit -m "import github pages" -s -a
    set +ex
    git push "$MIRROR_URL" master -f
    set -ex
fi
