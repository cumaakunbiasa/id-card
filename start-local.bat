@echo off
setlocal
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 goto missingphp
if not exist .env goto missingenv
if not exist vendor\autoload.php goto missingdeps
if not exist public\build\manifest.json goto missingbuild
php artisan config:clear
if errorlevel 1 goto failed
php artisan route:clear
if errorlevel 1 goto failed
php artisan view:clear
if errorlevel 1 goto failed
php artisan installation:check
if errorlevel 1 goto failed
echo Buka http://127.0.0.1:8000 - hentikan dengan Ctrl+C.
php artisan serve --host=127.0.0.1 --port=8000
if errorlevel 1 goto failed
exit /b 0
:missingphp
echo PHP tidak ditemukan di PATH. Gunakan PHP 8.3 atau lebih baru sesuai lockfile.
goto failed
:missingenv
echo File .env belum ada. Ikuti README.md untuk instalasi baru.
goto failed
:missingdeps
echo Jalankan composer install terlebih dahulu.
goto failed
:missingbuild
echo Jalankan npm ci lalu npm run build terlebih dahulu.
goto failed
:failed
echo Proses berhenti. Periksa pesan error di atas.
pause
exit /b 1
