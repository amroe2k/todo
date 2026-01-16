@echo off
REM Script untuk Git Push
echo ====================================
echo Git Push Script
echo ====================================
echo.

REM 1. Cek status perubahan
echo [1/4] Mengecek status perubahan...
git status
echo.

REM 2. Tambahkan perubahan
echo [2/4] Menambahkan perubahan...
git add .
echo.

REM 3. Commit perubahan
echo [3/4] Melakukan commit...
set /p commit_message="Masukkan pesan commit: "
if "%commit_message%"=="" (
    set commit_message=Update Files
)
git commit -m "%commit_message%"
echo.

REM 4. Push ke GitHub
echo [4/4] Push ke GitHub...
git push origin main
echo.

echo ====================================
echo Selesai!
echo ====================================
pause
