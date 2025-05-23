# Farmers App - Release Guide

This document outlines how to build and release your Android app using Expo and EAS Build.

## Prerequisites

- Make sure you have the Expo CLI installed:
  ```
  npm install -g expo-cli
  ```
- Install EAS CLI for building through Expo's build service:
  ```
  npm install -g eas-cli
  ```

## Android App Signing Configuration

### Current Configuration

The app has been set up with the following configuration:

- **Package Name**: `com.municipalagriculturesoffice.farmersapp`
- **Keystore Path**: `android/app/release.keystore`
- **Keystore Alias**: `my-key-alias`
- **Keystore Password**: `kevin4567`
- **Key Password**: `kevin4567`

### Environment Variables

The `.env.production` file contains:

```
APP_ENV=production
KEYSTORE_PASSWORD=kevin4567
KEY_PASSWORD=kevin4567
```

### Making Changes to Keystore Configuration

If you need to change the keystore credentials:

1. Generate a new keystore using the keytool command:

   ```bash
   keytool -genkeypair -v -storetype PKCS12 -keystore android/app/release.keystore -alias my-key-alias -keyalg RSA -keysize 2048 -validity 10000
   ```

2. Update the `.env.production` file with new passwords

3. Run the setup script to configure the app:
   ```bash
   npm run setup:release
   ```

## Building the App

### APK Build with EAS (Recommended)

To build an APK file using EAS cloud services:

```bash
# Start a new build
npm run build:apk-simple

# Check build status
npm run build:status

# View detailed build logs
npm run build:log
```

This will:

1. Upload your app to EAS Build
2. Generate an APK using the release keystore
3. Make the APK available for download from your Expo dashboard

The link to track your build progress will be provided in the terminal output.

### Build Monitoring

You can monitor your build in several ways:

- Use `npm run build:status` to check the current build status
- Use `npm run build:log` to view detailed build logs
- Visit the Expo dashboard at https://expo.dev/accounts/kevinchris/projects/my-new-app/builds
- Click the build URL provided in the terminal output

### Alternative Build Methods

You can also use these commands for different build configurations:

```bash
# For development build
eas build --platform android --profile development

# For preview build
eas build --platform android --profile preview

# For production build
eas build --platform android --profile production
```

### Build Output

When the build completes, you'll be able to download your APK from the Expo dashboard at:
https://expo.dev/accounts/kevinchris/projects/my-new-app/builds

## Build Output

- **Local Builds**: The APK will be saved to a local directory shown in the build output
- **Cloud Builds**: Available for download from your Expo dashboard

## Publishing to Google Play Store

1. Create a Google Play Console account if you don't have one
2. Create a new application in the console with the package name `com.municipalagriculturesoffice.farmersapp`
3. Upload your APK or AAB file to the production track
4. Complete the store listing, content rating, and pricing details
5. Submit your app for review

## Troubleshooting

### Common Build Issues

- **Missing Keystore**: Ensure the keystore file exists at `android/app/release.keystore`
- **Incorrect Passwords**: Check that the passwords in `.env.production` match the ones used to create the keystore
- **Gradle Errors**: If you encounter Gradle build errors:
  - Try cleaning the build: `cd android && ./gradlew clean`
  - Make sure you have correct versions of Java and Node installed
  - Check your app.json and app.config.js for syntax errors (like trailing commas)

### Viewing Build Logs

For EAS builds:

```bash
eas build:list
eas build:view
```

## Important Notes

- **Keep Your Keystore Safe**: Back up your `release.keystore` file and passwords in a secure location. If you lose them, you won't be able to update your app on the Play Store.
- **Version Updates**: When updating your app, increment the `versionCode` in app.json
- **Environment Variables**: Never commit your `.env.production` file to a public repository.
- **Current Configuration**:
  - Package name: `com.municipalagriculturesoffice.farmersapp`
  - Keystore password: `kevin4567`
