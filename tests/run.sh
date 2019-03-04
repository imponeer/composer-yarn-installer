#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

if [ ! -d ${DIR}/.bunit ]; then
	pushd ${DIR}
		wget -q -O bunit.zip https://github.com/rafritts/bunit/archive/v1.0.0.zip > /dev/null
		unzip bunit.zip > /dev/null
		rm -rf bunit.zip > /dev/null
		mv bunit-* .bunit > /dev/null
	popd
fi;

source ${DIR}/.bunit/bunit.shl
source ${DIR}/helpers.shl

function testForce() {
	TEST_PATH=$(createProject "true")
	autoAssertFileExist "$TEST_PATH/vendor/bin/yarn"
	autoAssertFileExist "$TEST_PATH/vendor/bin/npm"
	autoAssertFileExist "$TEST_PATH/vendor/bin/node"
}

function testAuto() {
	NPM_INSTALLED=$(isGlobalInstalled npm)
	TEST_PATH=$(createProject "false")
	if [ "$NPM_INSTALLED" == "1" ]; then
		autoAssertFileExist "$TEST_PATH/vendor/bin/npm"
		autoAssertFileExist "$TEST_PATH/vendor/bin/node"
		autoAssertFileExist "$TEST_PATH/vendor/bin/yarn"
	else
		autoAssertFileNotExist "$TEST_PATH/vendor/bin/npm"
		autoAssertFileNotExist "$TEST_PATH/vendor/bin/node"
		autoAssertFileNotExist "$TEST_PATH/vendor/bin/yarn"
		assertEquals $(isGlobalInstalled yarn) "1"
	fi;
}

#function testTearDown() {
#	rm -rf ${DIR}/.bunit ${DIR}/.tmp
#}

runUnitTests