#!/bin/bash

BASE_DIR=$(dirname "$0")
VENV_DIR="$BASE_DIR/venv"
DOCS_DIR="$BASE_DIR/../../Documentation"

function usage()
{
    cat << EOF
Usage: sphinx.sh <command> [options]

Commands:
    i, install  Install Sphinx in a virtualenv
    s, serve    Serve documentation. Options are forwarded to sphinx-autobuild.
        -H <host>   Server host
        -p <port>   Server port
        -a          Write all files (from sphinx-build)
        -E          Don't use a saved environment (from sphinx-build)
EOF

    exit
}

function use_sphinx()
{
    source "$VENV_DIR/bin/activate"
}

COMMAND=$1
shift

case $COMMAND in
    i|install)
        if [ ! -e "$VENV_DIR" ]; then
            # t3fieldlisttable doesn't seem to work with Python 3
            virtualenv -p python2 "$VENV_DIR"
        fi

        use_sphinx

        pip install sphinx-autobuild
        pip install t3fieldlisttable
        pip install sphinx_typo3_theme

        ;;

    s|serve)
        use_sphinx
        sphinx-autobuild -c "$BASE_DIR" "$@" "$DOCS_DIR" "$BASE_DIR/_build"
        ;;

    *)
        usage
        ;;
esac
