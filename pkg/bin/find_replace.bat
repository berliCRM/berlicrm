echo off
for /F "tokens=1,* delims==" %%a in (%1) do (
if "%%b"=="%2;" (
echo inside if
echo %%a=%3;>> %1.tmp
) else (
echo inside else
if "%%b"=="" (
echo inside else if
echo %%a>> %1.tmp
) else (
echo inside last else
echo %%a=%%b>> %1.tmp
))
)
move /Y %1.tmp %1
