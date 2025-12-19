# Next Steps to Run the Mobile App

## ✅ Completed
- React Native project created
- All npm dependencies installed (884 packages, 0 vulnerabilities)
- React Native CLI installed and configured

## 📱 To Run the App

### ⭐ Recommended: Start with Android (Easier & Faster)

**Why Android First?**
- ✅ No need to install 15GB Xcode
- ✅ Faster setup (30-60 minutes vs 2-3 hours)
- ✅ Easier debugging
- ✅ Works on macOS, Windows, and Linux

**Requirements:**
- Android Studio installed
- Android SDK configured
- Android emulator or physical device

**Quick Start:**
See detailed guide: [ANDROID_QUICK_START.md](./ANDROID_QUICK_START.md)

**Steps:**
```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE

# Start the Metro bundler in one terminal
npm start

# In another terminal, run the app
npm run android
```

### Option 2: iOS (Requires Full Xcode Installation)

**⚠️ Important:** You need the **full Xcode app** from the Mac App Store, not just Command Line Tools.

**Requirements:**
- macOS with **Xcode 14+** installed from App Store (~15GB download)
- CocoaPods installed
- ~2-3 hours for complete setup

**Steps:**

1. **Install Xcode from App Store** (Required - 15GB download):
   - Open Mac App Store
   - Search for "Xcode"
   - Download and install (takes 30-60 minutes)

2. **Configure Xcode:**
```bash
sudo xcode-select --switch /Applications/Xcode.app/Contents/Developer
sudo xcodebuild -runFirstLaunch
```

3. **Install CocoaPods:**
```bash
sudo gem install cocoapods
```

4. **Install iOS dependencies:**
```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE/ios
pod install
cd ..
```

5. **Run the app:**
```bash
# Start the Metro bundler in one terminal
npm start

# In another terminal, run the app
npm run ios
```

## 🔧 Before Running

### 1. Configure Backend URL

Edit `src/config/api.ts` and update the API URL:

```typescript
export const API_BASE_URL = 'http://YOUR_IP_ADDRESS:8080/api';
```

**Important Notes:**
- Don't use `localhost` - it won't work on real devices or emulators
- For iOS Simulator: Use your Mac's local IP (e.g., `http://192.168.1.100:8080/api`)
- For Android Emulator: Use `http://10.0.2.2:8080/api` (special IP that maps to host's localhost)
- For Physical Devices: Use your server's IP address on the same network

**Find your local IP:**
```bash
# macOS/Linux
ifconfig | grep "inet " | grep -v 127.0.0.1

# Windows
ipconfig | findstr IPv4
```

### 2. Ensure Backend is Running

Make sure your Avinash-EYE backend is running:
```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE
docker-compose ps  # All services should be "Up"
```

## 🎯 Current Project Status

### What's Ready
- ✅ React Native 0.83.0 project initialized
- ✅ All dependencies installed
- ✅ React Native CLI configured
- ✅ TypeScript setup
- ✅ Project structure established

### What Needs Development
- 🔨 Screen components (Login, Home, Gallery, Settings)
- 🔨 Sync service implementation
- 🔨 Background upload logic
- 🔨 API integration
- 🔨 Photo picker integration
- 🔨 Status tracking UI

## 🚀 Development Workflow

### Recommended Approach

1. **Start with Login Screen**
   ```bash
   # Create/edit: src/screens/LoginScreen.tsx
   # Connect to Laravel API: POST /api/login
   ```

2. **Test API Connection**
   - Verify backend is accessible from emulator/simulator
   - Test authentication endpoint
   - Store auth token in AsyncStorage

3. **Build Home Screen**
   - Display sync statistics
   - Show recent uploads
   - Add manual upload button

4. **Implement Photo Picker**
   - Request camera roll permissions
   - Select photos to upload
   - Show upload progress

5. **Add Background Sync**
   - Configure background fetch
   - Implement queue system
   - Handle network errors

## 📖 Helpful Commands

```bash
# Clear Metro cache if you encounter issues
npm start -- --reset-cache

# Check React Native environment
npx react-native doctor

# View all available scripts
npm run

# Lint code
npm run lint

# Run tests
npm test
```

## 🐛 Troubleshooting

### Metro Bundler Won't Start
```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE
npm start -- --reset-cache
```

### Android Build Fails
```bash
cd android
./gradlew clean
cd ..
npm run android
```

### iOS Build Fails
```bash
cd ios
pod deintegrate
pod install
cd ..
npm run ios
```

### Can't Connect to Backend
- Check if backend is running: `docker-compose ps`
- Verify API_BASE_URL is correct
- Try pinging the server from your device
- Check firewall settings
- For Android emulator, use `10.0.2.2` instead of `localhost`

## 📚 Resources

- [React Native Documentation](https://reactnative.dev/docs/getting-started)
- [React Navigation](https://reactnavigation.org/docs/getting-started)
- [React Native Background Fetch](https://github.com/transistorsoft/react-native-background-fetch)
- [Avinash-EYE API Documentation](../README.md)

## 🎉 Quick Start (If Everything is Ready)

```bash
# Terminal 1: Start backend
cd /Users/avinash/PhpstormProjects/Avinash-EYE
docker-compose up -d

# Terminal 2: Start mobile app
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE
npm start

# Terminal 3: Run on platform of choice
npm run android  # or npm run ios
```

---

**Current Status**: Project initialized, ready for feature development
**Last Updated**: December 15, 2025

