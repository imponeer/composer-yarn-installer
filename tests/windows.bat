@echo off

set SRC_PATH=%CD%

set COMPOSER_COMMAND=install
set IS_LOCAL=true
call :do_test

set COMPOSER_COMMAND=update
set IS_LOCAL=true
call :do_test

set COMPOSER_COMMAND=install
set IS_LOCAL=false
call :do_test

set COMPOSER_COMMAND=update
set IS_LOCAL=false
call :do_test

echo All test completed sucessfully.
exit 0

:do_test
	setlocal
		call :generate_test_path
		pushd %TEST_PATH%
			call :create_composer_json
			call :run_composer_command
			call :check_if_file_exists
			call :check_if_file_executed
		popd
		call :delete_test_path
	endlocal
exit /b

:delete_test_path
	rd /s /q %TEST_PATH%
exit /b

:run_composer_command
	composer %COMPOSER_COMMAND% --no-interaction --no-dev --no-suggest --optimize-autoloader
exit /b

:check_if_file_executed
	vendor\bin\yarn.bat --help
	if not errorlevel 0 goto :error_execute_yarn
	vendor\bin\yarnpkg.bat --help
    if not errorlevel 0 goto :error_execute_yarnpkg
exit /b

:check_if_file_exists
	if not exist vendor\bin\yarn.bat goto :error_no_yarn
	if not exist vendor\bin\yarnpkg.bat goto :error_no_yarnpkg
exit /b

:generate_test_path
	set TEST_PATH=c:\%RANDOM%-%IS_LOCAL%.tmp
	mkdir %TEST_PATH%
	attrib +h %TEST_PATH% /s /d
exit /b

:create_composer_json
(
{
   "name":"test-%RANDOM%-%IS_LOCAL%",
   "description":"Just a dummy composer plugin for testing",
   "license":"PDDL-1.0",
   "type":"project",
   "authors":[
      {
         "name":"Some bot",
         "email":"Get.it@i.am.not.real"
      }
   ],
   "repositories":[
      {
         "type":"path",
         "url":"%SRC_PATH%",
         "options":{
            "symlink":false
         }
      }
   ],
   "require":{
      "imponeer\/composer-yarn-installer":"*"
   },
   "minimum-stability":"dev",
   "prefer-stable":false,
   "extra":{
      "mouf":{
         "nodejs":{
            "forceLocal":%IS_LOCAL%
         }
      }
   }
}
) > composer.json
exit /b

:error_no_yarn
echo ERROR: no yarn.bat found
exit /b 1

:error_no_yarnpkg
echo ERROR: no yarnpkg.bat found
exit /b 2

:error_execute_yarn
echo ERROR: can't correctly execute yarn.bat
exit /b 3

:error_execute_yarnpkg
echo ERROR: can't correctly execute yarnpkg.bat
exit /b 4