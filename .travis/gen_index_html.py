#!/usr/bin/env python
# coding:utf-8

import sys
import os
import time
import math
import re
import posixpath

sys.dont_write_bytecode = True

import mistune

TEMPLATE = '''<!DOCTYPE html>
<meta charset="utf-8">
<title>Index of {{ CURRENT_FOLDER }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta property="generator" content="phuslu">
<script>if(top != self) top.location.replace(location);</script>

<link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<style>
<!--
.table-condensed>thead>tr>th,
.table-condensed>tbody>tr>th,
.table-condensed>tfoot>tr>th,
.table-condensed>thead>tr>td,
.table-condensed>tbody>tr>td,
.table-condensed>tfoot>tr>td {
    padding: 3px;
}
body {
  font-family: Tahoma, "Microsoft Yahei", Arial, Serif;
}
-->
</style>

<style>
.octicon {
background-position: center left;
background-repeat: no-repeat;
padding-left: 16px;
}
.reply {
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='14px' height='16px' viewBox='0 0 14 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M6,3.5 C9.92,3.94 14,6.625 14,13.5 C11.688,8.438 9.25,7.5 6,7.5 L6,11 L0.5,5.5 L6,0 L6,3.5 Z' fill='#7D94AE' /></svg>");
}
.file {
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='12px' height='16px' viewBox='0 0 12 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M6,5 L2,5 L2,4 L6,4 L6,5 L6,5 Z M2,8 L9,8 L9,7 L2,7 L2,8 L2,8 Z M2,10 L9,10 L9,9 L2,9 L2,10 L2,10 Z M2,12 L9,12 L9,11 L2,11 L2,12 L2,12 Z M12,4.5 L12,14 C12,14.55 11.55,15 11,15 L1,15 C0.45,15 0,14.55 0,14 L0,2 C0,1.45 0.45,1 1,1 L8.5,1 L12,4.5 L12,4.5 Z M11,5 L8,2 L1,2 L1,14 L11,14 L11,5 L11,5 Z' fill='#7D94AE' /></svg>");
}
.file-media {
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='12px' height='16px' viewBox='0 0 12 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M6,5 L8,5 L8,7 L6,7 L6,5 L6,5 Z M12,4.5 L12,14 C12,14.55 11.55,15 11,15 L1,15 C0.45,15 0,14.55 0,14 L0,2 C0,1.45 0.45,1 1,1 L8.5,1 L12,4.5 L12,4.5 Z M11,5 L8,2 L1,2 L1,13 L4,8 L6,12 L8,10 L11,13 L11,5 L11,5 Z' fill='#7D94AE' /></svg>");
}
.file-directory {
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='14px' height='16px' viewBox='0 0 14 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M13,4 L7,4 L7,3 C7,2.34 6.69,2 6,2 L1,2 C0.45,2 0,2.45 0,3 L0,13 C0,13.55 0.45,14 1,14 L13,14 C13.55,14 14,13.55 14,13 L14,5 C14,4.45 13.55,4 13,4 L13,4 Z M6,4 L1,4 L1,3 L6,3 L6,4 L6,4 Z' fill='#7D94AE' /></svg>");
}
.bookmark {
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg width='10px' height='16px' viewBox='0 0 10 16' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'><path d='M9,0 L1,0 C0.27,0 0,0.27 0,1 L0,16 L5,12.91 L10,16 L10,1 C10,0.27 9.73,0 9,0 L9,0 Z M8.22,4.25 L6.36,5.61 L7.08,7.77 C7.14,7.99 7.06,8.05 6.88,7.94 L5,6.6 L3.12,7.94 C2.93,8.05 2.87,7.99 2.92,7.77 L3.64,5.61 L1.78,4.25 C1.61,4.09 1.64,4.02 1.87,4.02 L4.17,3.99 L4.87,1.83 L5.12,1.83 L5.82,3.99 L8.12,4.02 C8.35,4.02 8.39,4.1 8.21,4.25 L8.22,4.25 Z' id='Shape' fill='#7D94AE' /></svg>");
}
.external {
background-position: center right;
background-repeat: no-repeat;
background-image: linear-gradient(transparent,transparent),url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12'><path fill='#fff' stroke='#06c' d='M1.5 4.518h5.982V10.5H1.5z'/><path d='M5.765 1H11v5.39L9.427 7.937l-1.31-1.31L5.393 9.35l-2.69-2.688 2.81-2.808L4.2 2.544z' fill='#06f'/><path d='M9.995 2.004l.022 4.885L8.2 5.07 5.32 7.95 4.09 6.723l2.882-2.88-1.85-1.852z' fill='#fff'/></svg>");
padding-right: 13px;
}
</style>

<script>if (!/WebKit/.test(navigator.userAgent)) document.write('<link href="//phuslu.github.io/css/octicons-png.css" rel="stylesheet">');</script>

<div class="container">
<table class="table table-striped table-bordered table-hover table-condensed">
  <tr><th colspan="3">Index of {{ CURRENT_FOLDER }}</th></tr>
</table>

<table class="table table-striped table-bordered table-hover table-condensed">
<tr><td><a href="../" class="octicon-reply">..</a></td><td></td><td>-</td></tr>
{{ FILE_LIST_HTML }}
</table>

{{ README_HTML }}

</div>
'''

README_TEMPLATE = '''
<table class="table table-striped table-bordered table-hover table-condensed">
  <tr><th colspan="3">{{ README_FILENAME }}</th></tr>
  <tr><td colspan="3">
    <div id="readme" class="markdown-body">{{ README_MARKDOWN }}</div>
  </td></tr>
</table>

<link href="https://cdn.bootcss.com/github-markdown-css/2.6.0/github-markdown.min.css" rel="stylesheet">
<style>
.markdown-body {
  float: left;
  font-family: "ubuntu", "Tahoma", "Microsoft YaHei", arial,sans-serif;
}
</style>
'''

def render(template, vars):
    return re.sub(r'(?is){{ (\w+) }}', lambda m: str(vars.get(m.group(1), '')), template)

def getmtime(filename, use_git=False):
    timestamp = 0
    if use_git:
        timestamp = os.popen("git log -1 --format='%%ct' '%s'" % filename).read()
    if not timestamp:
        timestamp = os.path.getmtime(filename)
    return float(timestamp)


def human_filesize(size):
    assert isinstance(size, (int, long))
    size = float(size)
    base = 1024
    for x in 'BKMGTP':
        if -base < size < base:
            break
        size /= base
    a = re.sub(r'0+$', '', str(size)[:3]).rstrip('.')
    b = '' if x == 'B' else x
    return a + b


def main():
    target_dir = posixpath.normpath(posixpath.join('/', sys.argv[1]))
    os.chdir(sys.argv[1])
    if os.path.exists('index.html'):
        with open('index.html', 'rb') as fp:
            text = fp.read()
        if '<meta property="generator" content="phuslu">' not in text:
            print(os.getcwd() + '/index.html already exists, abort')
            return
    names = os.listdir('.')
    names = sorted(names, key=lambda x:(-os.path.isdir(x), x))
    git_dir = os.popen('git rev-parse --show-toplevel 2>/dev/null').read().strip()
    use_git = bool(git_dir)
    if use_git:
        github_user, github_repo = os.popen("git remote -v | awk '{print $2;exit}'").read().split('/')[-2:]
        github_repo = github_repo.strip().rstrip('.git')
        lfs_files = [x.split(None, 2)[-1].strip() for x in os.popen("git lfs ls-files").read().strip().splitlines()]
    CURRENT_FOLDER = target_dir
    FILE_LIST_HTML = ''
    README_FILENAME = ''
    for name in names:
        fullname = os.path.join(target_dir, name).strip('/')
        if name.startswith('.') or name == 'index.html':
            continue
        if name.lower() == 'readme.md':
            README_FILENAME = name
        is_url = name.endswith('.url')
        is_media = name.endswith(('jpg','png','bmp','gif','ico','webp','flv','mp4','mkv','avi','mkv'))
        is_dir = os.path.isdir(name)
        fsize = human_filesize(os.path.getsize(name)) if not is_dir else '-'
        mtime = time.strftime('%d-%b-%Y %H:%M', time.gmtime(getmtime(name, use_git=use_git)+8*3600))
        display_name = name
        if is_dir:
            link = name + '/'
            link_class = 'octicon file-directory'
        elif is_url:
            link = re.search(r'(?i)url\s*=\s*(\S+)', open(name, 'rb').read()).group(1)
            link_class = 'octicon bookmark'
            fsize = '-'
            display_name = os.path.splitext(display_name)[0]
        elif use_git:
            if fullname in lfs_files:
                link = 'https://media.githubusercontent.com/media/%s/%s/master/%s' % (github_user, github_repo, fullname)
                info = dict(x.split(None, 1) for x in open(name) if x.strip())
                fsize = human_filesize(int(info['size'].strip())) if 'size' in info else '-'
            else:
                link = 'https://raw.githubusercontent.com/%s/%s/master/%s' % (github_user, github_repo, fullname)
            link_class = 'octicon file-media' if is_media else 'octicon file'
        else:
            link = name
            link_class = 'octicon file-media' if is_media else 'octicon file'
        link_target = 'target="_blank"' if is_url else ''
        link_postfix = '<span class="external"/>' if is_url else ''
        FILE_LIST_HTML += render('<tr><td><a href="{{ link }}" class="{{ link_class }}" {{ link_target }} rel="noreferrer">{{ display_name }}</a>{{ link_postfix }}</td><td>{{ mtime }}</td><td>{{ fsize }}</td></tr>\n''', locals())
    README_HTML = ''
    if README_FILENAME:
        README_MARKDOWN = mistune.markdown(open(README_FILENAME, 'rb').read())
        README_HTML = render(README_TEMPLATE, locals())
    html = render(TEMPLATE, locals())
    with open('index.html', 'wb') as fp:
        fp.write(html)


if __name__ == '__main__':
    main()

