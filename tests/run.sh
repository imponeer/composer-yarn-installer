#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [ ! -d ${DIR}/.bunit ]; then
	pushd ${DIR} > /dev/null
		wget -q -O .bashunit.bash https://raw.githubusercontent.com/djui/bashunit/master/bashunit.bash
	popd > /dev/null
fi;

source ${DIR}/helpers.shl

function testForce() {
	TEST_PATH=$(createProject "true")
	assertInstallProject "$TEST_PATH"
	autoAssertFileExist "$TEST_PATH/vendor/bin/yarn"
	autoAssertFileExist "$TEST_PATH/vendor/bin/npm"
	autoAssertFileExist "$TEST_PATH/vendor/bin/node"
}

function testAuto() {
	NPM_INSTALLED=$(isGlobalInstalled npm)
	TEST_PATH=$(createProject "false")
	assertInstallProject "$TEST_PATH"
	if [ "$NPM_INSTALLED" == "0" ]; then
		autoAssertFileExist "$TEST_PATH/vendor/bin/npm"
		autoAssertFileExist "$TEST_PATH/vendor/bin/node"
		autoAssertFileExist "$TEST_PATH/vendor/bin/yarn"
	else
		YARN_INSTALLED=$(isGlobalInstalled yarn)
		autoAssertFileNotExist "$TEST_PATH/vendor/bin/npm"
		autoAssertFileNotExist "$TEST_PATH/vendor/bin/node"
		autoAssertFileNotExist "$TEST_PATH/vendor/bin/yarn"
		assertEqual "$YARN_INSTALLED" "1"
	fi;
}

source ${DIR}/.bashunit.bash