# Script to set up Android development environment variables
Write-Host "Setting up Android development environment variables..." -ForegroundColor Green

# Common paths - update these if your installations are in different locations
$androidSdkPath = "$env:LOCALAPPDATA\Android\Sdk"
$jdkPath = "C:\Program Files\Eclipse Adoptium\jdk-17.0.9.9-hotspot" # Update this path to match your JDK installation

# Function to verify directory exists
function Test-DirectoryExists {
    param($path, $name)
    if (Test-Path $path) {
        Write-Host "✓ Found $name at: $path" -ForegroundColor Green
        return $true
    } else {
        Write-Host "✗ Could not find $name at: $path" -ForegroundColor Red
        return $false
    }
}

# Check if paths exist before setting
$sdkExists = Test-DirectoryExists $androidSdkPath "Android SDK"
$jdkExists = Test-DirectoryExists $jdkPath "Java Development Kit"

if (-not $sdkExists) {
    Write-Host "`nAndroid SDK not found. Please install Android Studio and SDK first." -ForegroundColor Yellow
    Write-Host "Download from: https://developer.android.com/studio" -ForegroundColor Yellow
}

if (-not $jdkExists) {
    Write-Host "`nJDK not found. Please install JDK 17 first." -ForegroundColor Yellow
    Write-Host "Download from: https://adoptium.net/temurin/releases/?version=17" -ForegroundColor Yellow
}

if ($sdkExists -and $jdkExists) {
    Write-Host "`nSetting environment variables..." -ForegroundColor Green

    # Set ANDROID_HOME
    [System.Environment]::SetEnvironmentVariable("ANDROID_HOME", $androidSdkPath, "User")
    Write-Host "✓ Set ANDROID_HOME to: $androidSdkPath"

    # Set ANDROID_SDK_ROOT
    [System.Environment]::SetEnvironmentVariable("ANDROID_SDK_ROOT", $androidSdkPath, "User")
    Write-Host "✓ Set ANDROID_SDK_ROOT to: $androidSdkPath"

    # Set JAVA_HOME
    [System.Environment]::SetEnvironmentVariable("JAVA_HOME", $jdkPath, "User")
    Write-Host "✓ Set JAVA_HOME to: $jdkPath"

    # Add platform-tools to PATH
    $userPath = [System.Environment]::GetEnvironmentVariable("Path", "User")
    $platformToolsPath = "$androidSdkPath\platform-tools"
    
    if ($userPath -notlike "*$platformToolsPath*") {
        [System.Environment]::SetEnvironmentVariable("Path", "$userPath;$platformToolsPath", "User")
        Write-Host "✓ Added platform-tools to Path"
    } else {
        Write-Host "✓ platform-tools already in Path"
    }

    Write-Host "`nEnvironment variables set successfully!" -ForegroundColor Green
    Write-Host "Please restart your terminal for the changes to take effect."
    
    # Verify adb is accessible
    Write-Host "`nVerifying Android Debug Bridge (adb) installation..."
    try {
        $adbVersion = & "$platformToolsPath\adb.exe" version
        Write-Host "✓ ADB is accessible: $adbVersion" -ForegroundColor Green
    } catch {
        Write-Host "✗ Could not verify ADB installation. You may need to install Android SDK Platform-tools." -ForegroundColor Yellow
    }
} else {
    Write-Host "`nPlease install the missing components and run this script again." -ForegroundColor Yellow
}
