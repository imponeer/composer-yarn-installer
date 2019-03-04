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
}

function testAuto() {
	TEST_PATH=$(createProject "false")
}

#function testTearDown() {
#	rm -rf ${DIR}/.bunit ${DIR}/.tmp
#}

runUnitTests