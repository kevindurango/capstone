# This file will be executed before EAS Build to ensure the plugin is available
echo "Setting up expo-module-gradle-plugin..."

# Make directory structure
mkdir -p android/local-repo/host/exp/gradle/expo-module-gradle-plugin/0.4.1

# Create a simple POM file
cat > android/local-repo/host/exp/gradle/expo-module-gradle-plugin/0.4.1/expo-module-gradle-plugin-0.4.1.pom << 'EOF'
<project>
  <modelVersion>4.0.0</modelVersion>
  <groupId>host.exp.gradle</groupId>
  <artifactId>expo-module-gradle-plugin</artifactId>
  <version>0.4.1</version>
</project>
EOF

# Try to install needed npm packages
echo "Installing required npm packages..."
npm install --save-dev expo-modules-core

# Fix app/build.gradle if expo-module-gradle-plugin is still causing issues
if [ ! -f android/local-repo/host/exp/gradle/expo-module-gradle-plugin/0.4.1/expo-module-gradle-plugin-0.4.1.jar ]; then
  echo "Creating a fallback fix for app/build.gradle..."
  sed -i -e 's/apply plugin: "expo-module-gradle-plugin"//g' android/app/build.gradle
fi

echo "Setup complete"
