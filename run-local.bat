@echo off
REM Chay thu WordPress local (Resi) voi MySQL that.
REM Nhan doi lan file nay: tu khoi dong MySQL (neu chua chay) + PHP server.
REM Mo trinh duyet: http://localhost:8081
REM Luu y: dung chung 1 instance MySQL voi ban Van phong (propertyplus), khac database.
REM Neu ban Van phong da chay run-local.bat cua no roi thi MySQL o day se "da chay san, bo qua".

set MYSQL_DIR=C:\laragon\bin\mysql-real\mysql-8.4.3-winx64
set MYSQL_DATADIR=C:\laragon\data\mysql-real
set PHP_DIR=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64

echo === Kiem tra MySQL da chay chua ===
netstat -ano | findstr ":3306" | findstr "LISTENING" >nul
if %errorlevel%==0 (
    echo MySQL da chay san, bo qua.
) else (
    echo Dang khoi dong MySQL...
    start "MySQL - propertyplus" "%MYSQL_DIR%\bin\mysqld.exe" --basedir="%MYSQL_DIR%" --datadir="%MYSQL_DATADIR%" --port=3306
    timeout /t 5 /nobreak >nul
)

echo === Khoi dong PHP server tai http://localhost:8081 ===
echo (Giu cua so nay mo. Dong cua so = tat web. MySQL van chay o cua so rieng.)
echo.
"%PHP_DIR%\php.exe" -c "%PHP_DIR%\php.ini" -S 0.0.0.0:8081 -t "C:\laragon\www\propertyplus-resi" "C:\laragon\www\propertyplus-resi\router.php"
pause
