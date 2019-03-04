#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [ ! -d ${DIR}/.bunit ]; then
	pushd ${DIR} > /dev/null
		wget -q -O .bashunit.bash https://raw.githubusercontent.com/djui/bashunit/master/bashunit.bash
	popd > /dev/null
fi;

source ${DIR}/helpers.shl
source ${DIR}/tests.shl
source ${DIR}/.bashunit.bash