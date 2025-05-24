# Check Android environment setup
$androidHome = [System.Environment]::GetEnvironmentVariable("ANDROID_HOME", "User")
$androidSdkRoot = [System.Environment]::GetEnvironmentVariable("ANDROID_SDK_ROOT", "User")
$java_home = [System.Environment]::GetEnvironmentVariable("JAVA_HOME", "User")

Write-Host "Checking Android development environment..."
Write-Host "----------------------------------------"

$missing = @()

if (-not $androidHome) {
    $missing += "ANDROID_HOME"
}

if (-not $androidSdkRoot) {
    $missing += "ANDROID_SDK_ROOT"
}

if (-not $java_home) {
    $missing += "JAVA_HOME"
}

if ($missing.Count -gt 0) {
    Write-Host "Missing environment variables:" -ForegroundColor Red
    foreach ($var in $missing) {
        Write-Host "- $var" -ForegroundColor Red
    }
    Write-Host "`nPlease set up the following:"
    Write-Host "1. Install Android Studio from https://developer.android.com/studio"
    Write-Host "2. Install Java Development Kit (JDK) 17 or newer"
    Write-Host "3. Set environment variables:"
    Write-Host "   - ANDROID_HOME: Path to Android SDK (usually %LOCALAPPDATA%\Android\Sdk)"
    Write-Host "   - ANDROID_SDK_ROOT: Same as ANDROID_HOME"
    Write-Host "   - JAVA_HOME: Path to JDK installation"
} else {
    Write-Host "✓ Environment variables are set correctly" -ForegroundColor Green
    Write-Host "ANDROID_HOME: $androidHome"
    Write-Host "ANDROID_SDK_ROOT: $androidSdkRoot"
    Write-Host "JAVA_HOME: $java_home"
}

# Check for required Android SDK components
$sdkManager = "$androidHome\tools\bin\sdkmanager.bat"
if (Test-Path $sdkManager) {
    Write-Host "`nChecking Android SDK components..."
    Write-Host "----------------------------------------"
    & $sdkManager --list_installed
} else {
    Write-Host "`nCould not find Android SDK Manager. Please ensure Android Studio is properly installed." -ForegroundColor Yellow
}
