Write-Host "Stopping Gradle Daemon..."
cd android
.\gradlew.bat --stop

Write-Host "Killing any Java processes that might be locking Gradle files..."
taskkill /F /IM java.exe

Write-Host "Deleting Gradle caches..."
cd ..
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue .\android\.gradle
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue .\android\app\build
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue $env:USERPROFILE\.gradle\caches\modules-2\files-2.1\com.facebook.react
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue $env:USERPROFILE\.gradle\caches\transforms-3

Write-Host "Gradle cache cleaned!"
