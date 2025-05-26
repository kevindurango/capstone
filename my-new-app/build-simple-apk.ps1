Write-Host "Stopping Gradle processes..."
cd android
.\gradlew.bat --stop
cd ..

Write-Host "Killing Java processes..."
taskkill /F /IM java.exe 2>$null

Write-Host "Cleaning build directories..."
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue .\android\.gradle
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue .\android\build
Remove-Item -Recurse -Force -ErrorAction SilentlyContinue .\android\app\build

Write-Host "Making backup of original build files..."
Copy-Item -Path .\android\settings.gradle -Destination .\android\settings.gradle.backup -Force
Copy-Item -Path .\android\app\build.gradle -Destination .\android\app\build.gradle.backup -Force

Write-Host "Creating simplified build files..."
Set-Content -Path .\android\settings.gradle.simple -Value "rootProject.name = 'FarmersApp'`ninclude ':app'"
Set-Content -Path .\android\app\build.gradle.simple -Value @"
apply plugin: "com.android.application"
apply plugin: "org.jetbrains.kotlin.android"

android {
    compileSdk 34
    buildToolsVersion "34.0.0"

    defaultConfig {
        applicationId 'com.municipalagriculturesoffice.farmersapp'
        minSdk 24
        targetSdk 34
        versionCode 1
        versionName "1.0.0"
    }

    signingConfigs {
        debug {
            storeFile file('debug.keystore')
            storePassword 'android'
            keyAlias 'androiddebugkey'
            keyPassword 'android'
        }
    }

    buildTypes {
        debug {
            signingConfig signingConfigs.debug
        }
        release {
            signingConfig signingConfigs.debug
            minifyEnabled false
            proguardFiles getDefaultProguardFile("proguard-android.txt"), "proguard-rules.pro"
        }
    }
    
    packagingOptions {
        resources {
            excludes += ['META-INF/DEPENDENCIES', 'META-INF/LICENSE', 'META-INF/LICENSE.txt', 'META-INF/license.txt', 'META-INF/NOTICE', 'META-INF/NOTICE.txt', 'META-INF/notice.txt', 'META-INF/ASL2.0', 'META-INF/*.kotlin_module']
        }
    }
    namespace 'com.municipalagriculturesoffice.farmersapp'
}

dependencies {
    implementation "androidx.appcompat:appcompat:1.6.1"
    implementation "com.google.android.material:material:1.9.0"
    implementation "androidx.constraintlayout:constraintlayout:2.1.4"
}
"@

Write-Host "Copying simplified build files..."
Copy-Item -Path .\android\settings.gradle.simple -Destination .\android\settings.gradle -Force
Copy-Item -Path .\android\app\build.gradle.simple -Destination .\android\app\build.gradle -Force

Write-Host "Building simple APK..."
cd android
.\gradlew.bat --no-daemon clean assembleRelease

Write-Host "Restoring original build files..."
cd ..
Copy-Item -Path .\android\settings.gradle.backup -Destination .\android\settings.gradle -Force
Copy-Item -Path .\android\app\build.gradle.backup -Destination .\android\app\build.gradle -Force

Write-Host "Process completed!"
Write-Host "If successful, APK can be found at: .\android\app\build\outputs\apk\release\app-release.apk"
