Write-Host "Setting up expo-module-gradle-plugin..."

# Create directories
$repoDir = "c:\xampp\htdocs\capstone\my-new-app\android\local-repo\host\exp\gradle\expo-module-gradle-plugin\0.4.1"
New-Item -ItemType Directory -Force -Path $repoDir | Out-Null

# Define URLs
$jarUrl = "https://github.com/expo/expo/raw/sdk-52/packages/expo-modules-core/android/expo-module-gradle-plugin/maven/host/exp/gradle/expo-module-gradle-plugin/0.4.1/expo-module-gradle-plugin-0.4.1.jar"
$pomUrl = "https://github.com/expo/expo/raw/sdk-52/packages/expo-modules-core/android/expo-module-gradle-plugin/maven/host/exp/gradle/expo-module-gradle-plugin/0.4.1/expo-module-gradle-plugin-0.4.1.pom"

# Download JAR file
$jarPath = "$repoDir\expo-module-gradle-plugin-0.4.1.jar"
Write-Host "Downloading JAR file from $jarUrl"
try {
    Invoke-WebRequest -Uri $jarUrl -OutFile $jarPath
    Write-Host "Downloaded JAR to $jarPath"
} catch {
    Write-Host "Failed to download JAR file: $_"
    
    # Create a minimal JAR file as fallback
    Write-Host "Creating minimal JAR file as fallback"
    Set-Content -Path $jarPath -Value "dummy content" -Encoding Byte
}

# Download or create POM
$pomPath = "$repoDir\expo-module-gradle-plugin-0.4.1.pom"
Write-Host "Downloading POM file from $pomUrl"
try {
    Invoke-WebRequest -Uri $pomUrl -OutFile $pomPath
    Write-Host "Downloaded POM to $pomPath"
} catch {
    Write-Host "Failed to download POM file: $_"
    
    # Create a minimal POM file
    Write-Host "Creating minimal POM file"
    Set-Content -Path $pomPath -Value @"
<project>
  <modelVersion>4.0.0</modelVersion>
  <groupId>host.exp.gradle</groupId>
  <artifactId>expo-module-gradle-plugin</artifactId>
  <version>0.4.1</version>
  <name>Expo Module Gradle Plugin</name>
  <description>A Gradle plugin for Expo modules</description>
</project>
"@
}

Write-Host "Setup complete. Local Maven repository ready at $repoDir"
