// scripts/setup-release.js
const fs = require("fs");
const path = require("path");
const readline = require("readline");
const { execSync } = require("child_process");

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
});

// Function to validate the application ID (package name)
function isValidPackageName(name) {
  return /^([a-z][a-z0-9_]*\.)+[a-z][a-z0-9_]*$/.test(name);
}

// Main function
async function setupRelease() {
  console.log("======================================");
  console.log("Android Release Keystore Setup");
  console.log("======================================\n");

  const appConfigPath = path.join(process.cwd(), "app.config.js");
  const appJsonPath = path.join(process.cwd(), "app.json");
  const envPath = path.join(process.cwd(), ".env.production");

  // Check if keystore exists
  const keystorePath = path.join(
    process.cwd(),
    "android",
    "app",
    "release.keystore"
  );
  const keystoreExists = fs.existsSync(keystorePath);

  if (keystoreExists) {
    console.log("Keystore already exists at:", keystorePath);
    console.log(
      "If you want to create a new one, please backup and remove the existing one first.\n"
    );
  } else {
    console.log("No existing keystore found. We will create a new one.\n");
  }

  // Get the package name
  let packageName = await new Promise((resolve) => {
    let appConfig;
    try {
      const appJson = JSON.parse(fs.readFileSync(appJsonPath, "utf8"));
      appConfig = appJson?.expo?.android?.package;
    } catch (e) {
      // Ignore errors
    }

    rl.question(
      `Enter your Android package name (e.g., com.company.appname) ${appConfig ? `[${appConfig}]` : ""}: `,
      (answer) => {
        const packageName = answer || appConfig;
        if (!packageName || !isValidPackageName(packageName)) {
          console.error(
            "Invalid package name! It should be in format like com.company.appname"
          );
          process.exit(1);
        }
        resolve(packageName);
      }
    );
  });

  // Get keystore password
  const keystorePassword = await new Promise((resolve) => {
    rl.question("Enter a strong password for your keystore: ", (answer) => {
      if (!answer || answer.length < 6) {
        console.error("Password must be at least 6 characters!");
        process.exit(1);
      }
      resolve(answer);
    });
  });

  // Key alias name
  const keyAlias = "my-key-alias";

  // Create .env.production file
  const envContent = `APP_ENV=production
KEYSTORE_PASSWORD=${keystorePassword}
KEY_PASSWORD=${keystorePassword}`;

  fs.writeFileSync(envPath, envContent);
  console.log("\nCreated .env.production file with your credentials");

  // Update app.json with package name
  try {
    const appJson = JSON.parse(fs.readFileSync(appJsonPath, "utf8"));
    if (!appJson.expo.android) {
      appJson.expo.android = {};
    }
    appJson.expo.android.package = packageName;
    fs.writeFileSync(appJsonPath, JSON.stringify(appJson, null, 2));
    console.log(`Updated ${appJsonPath} with package name: ${packageName}`);
  } catch (e) {
    console.error("Failed to update app.json:", e.message);
  }
  console.log("\n======================================");
  console.log("Setup completed! Follow these steps:");
  console.log("\n1. Build your APK:");
  console.log("   npm run build:apk-simple");
  console.log("\n2. Check build status:");
  console.log("   npm run build:status");
  console.log("\n3. Download the APK:");
  console.log("   npm run download:apk");
  console.log("\nOr monitor your build at:");
  console.log(
    "https://expo.dev/accounts/kevinchris/projects/my-new-app/builds"
  );
  console.log("======================================");

  rl.close();
}

setupRelease().catch(console.error);
