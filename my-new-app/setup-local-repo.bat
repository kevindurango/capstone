@echo off
echo Creating expo-modules directory...
if not exist "c:\xampp\htdocs\capstone\my-new-app\node_modules\expo-modules" mkdir "c:\xampp\htdocs\capstone\my-new-app\node_modules\expo-modules"

echo Downloading expo-module-gradle-plugin...
curl -L -o "c:\xampp\htdocs\capstone\my-new-app\node_modules\expo-modules\expo-module-gradle-plugin-0.4.1.jar" "https://raw.githubusercontent.com/expo/expo/sdk-52/packages/expo-modules-core/android/expo-module-gradle-plugin/maven/host/exp/gradle/expo-module-gradle-plugin/0.4.1/expo-module-gradle-plugin-0.4.1.jar"
echo Download completed.

echo Creating local Maven repo structure...
if not exist "c:\xampp\htdocs\capstone\my-new-app\android\local-repo\host\exp\gradle\expo-module-gradle-plugin\0.4.1" (
    mkdir "c:\xampp\htdocs\capstone\my-new-app\android\local-repo\host\exp\gradle\expo-module-gradle-plugin\0.4.1"
)
copy /Y "c:\xampp\htdocs\capstone\my-new-app\node_modules\expo-modules\expo-module-gradle-plugin-0.4.1.jar" "c:\xampp\htdocs\capstone\my-new-app\android\local-repo\host\exp\gradle\expo-module-gradle-plugin\0.4.1\"
echo Creating minimal POM file...
echo ^<project^>^<modelVersion^>4.0.0^</modelVersion^>^<groupId^>host.exp.gradle^</groupId^>^<artifactId^>expo-module-gradle-plugin^</artifactId^>^<version^>0.4.1^</version^>^</project^> > "c:\xampp\htdocs\capstone\my-new-app\android\local-repo\host\exp\gradle\expo-module-gradle-plugin\0.4.1\expo-module-gradle-plugin-0.4.1.pom"
echo Local Maven repo setup completed.
