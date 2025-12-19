# iOS Setup Instructions

## Step 1: Install CocoaPods (REQUIRED)

Open your terminal and run:

```bash
sudo gem install cocoapods
```

Enter your Mac password when prompted.

---

## Step 2: Install iOS Dependencies

```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE/ios
pod install
```

This will take 2-5 minutes and create a `Pods` folder.

---

## Step 3: Build and Run

```bash
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE
npm run ios
```

---

## Alternative: Build with Xcode (if npm run ios still fails)

1. Open Xcode:
```bash
open /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE/ios/AvinashEYE.xcworkspace
```

2. **⚠️ IMPORTANT:** Open the `.xcworkspace` file, NOT the `.xcodeproj` file!

3. In Xcode:
   - Select your iPhone from the device dropdown (top toolbar)
   - Click the ▶️ play button to build and run

---

## Troubleshooting

### If you get signing errors:

1. Click on "AvinashEYE" in the left sidebar (blue icon)
2. Select "Signing & Capabilities" tab
3. Check "Automatically manage signing"
4. Select your Apple ID team from the dropdown
   - If you don't have a team, click "Add Account" and sign in with your Apple ID (free)

### If your iPhone isn't detected:

1. Make sure your iPhone is unlocked
2. Trust this computer on your iPhone (popup should appear)
3. In Xcode: Window → Devices and Simulators → Check if your phone appears

---

## Quick Commands Reference

```bash
# Install CocoaPods
sudo gem install cocoapods

# Install iOS dependencies
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE/ios && pod install

# Run the app
cd /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE && npm run ios

# Or open in Xcode
open /Users/avinash/PhpstormProjects/Avinash-EYE/mobile-app/AvinashEYE/ios/AvinashEYE.xcworkspace
```

---

## What Happens Next?

Once you complete these steps:
1. ✅ The app will install on your iPhone
2. ✅ You'll see the login screen
3. ✅ You can test photo upload and background sync
4. ✅ Hot reload will work for development

