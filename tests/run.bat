@echo off
mkdir c:\test.tmp
set old_temp=%TEMP%
set old_tmp=%TMP%
set TEMP=c:\test.tmp
set TMP=c:\test.tmp
pushd %~dp0\..\
vendor\bin\phpunit -v --debug
popd
set OLD_PATH=
set TEMP=%old_temp%
set TMP=%old_tmp%
del /q /s /f C:\test.tmp
FOR /D %%p IN ("C:\test.tmp\*.*") DO rmdir "%%p" /s /q
del /q /s /f C:\test.tmp