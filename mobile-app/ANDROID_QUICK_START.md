# Android Quick Start Guide

## Why Start with Android?

- ✅ No need to install 15GB Xcode
- ✅ Faster setup
- ✅ Works on macOS, Windows, and Linux
- ✅ Easier emulator setup

## Prerequisites

You need either:
1. **Android Studio** (Recommended - includes everything)
2. **Android SDK** + Android Emulator

## Quick Setup with Android Studio

### 1. Install Android Studio

Download from: https://developer.android.com/studio

### 2. Configure Android SDK

1. Open Android Studio
2. Go to: **Settings/Preferences** → **Appearance & Behavior** → **System Settings** → **Android SDK**
3. Install:
   - ✅ Android SDK Platform 34 (or latest)
   - ✅ Android SDK Build-Tools
   - ✅ Android Emulator
   - ✅ Android SDK Platform-Tools

### 3. Set Environment Variables

Add to your `~/.zshrc` (or `~/.bash_profile`):

```bash
export ANDROID_HOME=$HOME/Library/Android/sdk
export PATH=$PATH:$ANDROID_HOME/emulator
export PATH=$PATH:$ANDROID_HOME/platform-tools
export PATH=$PATH:$ANDROID_HOME/tools
export PATH=$PATH:$ANDROID_HOME/tools/bin
```

Apply changes:
```bash
source ~/.zshrc  # or source ~/.bash_profile
```

### 4. Create an Android Virtual Device (AVD)

1. Open Android Studio
2. Go to: **Tools** → **Device Manager**
3. Click **"Create Device"**
4. Select a device (e.g., **Pixel 5**)
5. Download and select a system image (e.g., **API 34**)
6. Click **Finish**

### 5. Configure Backend API

Edit: `/Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE/src/config/api.ts`

```typescript
// For Android Emulator, use this special IP that maps to host's localhost
export const API_BASE_URL = 'http://10.0.2.2:8080/api';
```

**Important:** `10.0.2.2` is a special alias to your host machine's `localhost` from the Android emulator.

### 6. Start Backend Server

```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE
docker-compose up -d

# Verify services are running
docker-compose ps
```

### 7. Run the App

```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE

# Terminal 1: Start Metro bundler
npm start

# Terminal 2: Run on Android
npm run android
```

The app will automatically:
1. Start the Android Emulator (if not running)
2. Build the app
3. Install on the emulator
4. Launch the app

## Alternative: Use Physical Android Device

### 1. Enable Developer Mode on Your Phone

1. Go to **Settings** → **About Phone**
2. Tap **Build Number** 7 times
3. Go back to **Settings** → **Developer Options**
4. Enable **USB Debugging**

### 2. Connect Device via USB

```bash
# Check if device is detected
adb devices

# You should see:
# List of devices attached
# XXXXXXXXXXXXXX    device
```

### 3. Update API Configuration

Since your phone needs to access the backend on your Mac, use your Mac's local IP:

```bash
# Find your Mac's IP address
ifconfig | grep "inet " | grep -v 127.0.0.1
# Example output: inet 192.168.1.100 netmask 0xffffff00 broadcast 192.168.1.255
```

Edit `src/config/api.ts`:
```typescript
export const API_BASE_URL = 'http://192.168.1.100:8080/api';  // Use your actual IP
```

### 4. Run on Physical Device

```bash
npm run android
```

## Troubleshooting

### Error: "SDK location not found"

Create `android/local.properties`:
```properties
sdk.dir=/Users/YOUR_USERNAME/Library/Android/sdk
```

### Error: "Unable to load script"

```bash
# Clear cache and restart
npm start -- --reset-cache
```

### Error: "Could not connect to development server"

1. Make sure Metro bundler is running (`npm start`)
2. Shake device/emulator to open dev menu
3. Select **"Settings"** → **"Change Bundle Location"**
4. Enter: `localhost:8081` (or your Mac's IP for physical device)

### Error: Gradle build fails

```bash
cd android
./gradlew clean
cd ..
npm run android
```

### App crashes on launch

Check Metro bundler terminal for errors. Common issues:
- Backend server not running
- Wrong API URL
- Network permissions not granted

## Verify Everything Works

### 1. Backend Health Check

From your Mac:
```bash
curl http://localhost:8080/api/health
# Should return: {"status":"ok"}
```

From Android Emulator:
```bash
adb shell
curl http://10.0.2.2:8080/api/health
```

### 2. Test in Mobile App

Once the app is running:
1. Check if login screen appears
2. Try logging in with your credentials
3. Check Metro bundler logs for network requests

## Performance Tips

### Speed up builds:

1. **Enable Hermes** (JavaScript engine) - Already enabled in React Native 0.83.0

2. **Increase Gradle memory** - Edit `android/gradle.properties`:
```properties
org.gradle.jvmargs=-Xmx4096m -XX:MaxPermSize=512m -XX:+HeapDumpOnOutOfMemoryError -Dfile.encoding=UTF-8
org.gradle.daemon=true
org.gradle.parallel=true
```

3. **Use Metro cache:**
```bash
npm start
# Metro will cache bundles for faster reloads
```

## Next Steps After Successful Run

1. ✅ Verify app launches successfully
2. ✅ Test API connectivity
3. ✅ Implement Login screen
4. ✅ Build Home screen with upload functionality
5. ✅ Add photo picker
6. ✅ Implement background sync

## Development Workflow

```bash
# 1. Keep Metro bundler running
npm start

# 2. Make changes to code
# 3. App will hot-reload automatically
# 4. For major changes, press 'r' in Metro terminal to reload

# 5. View logs
npm run android -- --verbose
```

## Helpful Commands

```bash
# List connected devices
adb devices

# View device logs
adb logcat

# Install APK manually
adb install android/app/build/outputs/apk/debug/app-debug.apk

# Uninstall app
adb uninstall com.avinasheye

# Restart ADB server
adb kill-server && adb start-server

# Check React Native environment
npx react-native doctor
```

---

**Estimated Setup Time:** 30-60 minutes (depending on download speeds)
**Result:** Fully functional React Native app running on Android emulator or device

