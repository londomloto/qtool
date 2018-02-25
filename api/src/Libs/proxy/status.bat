@echo off
tasklist /FI "IMAGENAME eq openvpn.exe" 2>NUL | find /I /N "openvpn.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo running
) else (
    echo stopped
)