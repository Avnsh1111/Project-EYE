# Avinash-EYE Mobile App

A React Native mobile application for syncing photos from Android and iOS devices to the Avinash-EYE backend.

## Features

- 📸 **Background Photo Sync**: Automatically sync photos from your device's camera roll
- 🔄 **Real-time Processing Status**: View upload and processing progress in real-time
- 🔐 **Secure Authentication**: Login with your Avinash-EYE credentials
- 📊 **Upload Statistics**: Track sync history and status
- 🌙 **Background Operation**: Continues syncing even when the app is in the background

## Tech Stack

- React Native 0.83.0
- React 19.2.0
- React Navigation 6
- Axios for API calls
- AsyncStorage for local data
- React Native Camera Roll for photo access
- React Native Background Fetch for background sync
- TypeScript for type safety

## Prerequisites

- Node.js >= 18
- npm or yarn
- For iOS: Xcode 14+ and CocoaPods
- For Android: Android Studio and Android SDK

## Installation

1. Navigate to the mobile app directory:
```bash
cd mobile-app/AvinashEYE
```

2. Install dependencies:
```bash
npm install --legacy-peer-deps
```

3. For iOS, install CocoaPods dependencies:
```bash
cd ios && pod install && cd ..
```

## Configuration

Before running the app, update the API configuration:

1. Open `src/config/api.ts`
2. Update `API_BASE_URL` with your Laravel backend URL:
```typescript
export const API_BASE_URL = 'http://YOUR_SERVER_IP:8080/api';
```

## Running the App

### Development Mode

Start the Metro bundler:
```bash
npm start
```

### Run on Android
```bash
npm run android
```

### Run on iOS
```bash
npm run ios
```

## Project Structure

```
AvinashEYE/
├── android/              # Android native code
├── ios/                  # iOS native code
├── src/
│   ├── components/       # Reusable React components
│   ├── screens/          # App screens
│   ├── services/         # API and sync services
│   ├── config/           # Configuration files
│   ├── types/            # TypeScript type definitions
│   └── App.tsx           # Main app component
├── package.json          # Dependencies
└── README.md            # This file
```

## Backend API Endpoints

The mobile app communicates with these Laravel API endpoints:

- `POST /api/login` - User authentication
- `POST /api/upload-photo` - Upload photo with metadata
- `POST /api/upload-status` - Get processing status
- `GET /api/sync-status` - Get overall sync statistics

## Permissions Required

### iOS (Info.plist)
- `NSPhotoLibraryUsageDescription` - Access to photo library
- `NSCameraUsageDescription` - Camera access
- `NSPhotoLibraryAddUsageDescription` - Save photos

### Android (AndroidManifest.xml)
- `READ_EXTERNAL_STORAGE` - Read photos
- `READ_MEDIA_IMAGES` - Read media images (Android 13+)
- `INTERNET` - Network access
- `ACCESS_NETWORK_STATE` - Check network status

## Background Sync

The app uses React Native Background Fetch to periodically sync photos even when the app is not in the foreground. Sync frequency and behavior can be configured in the app settings.

## Troubleshooting

### Common Issues

**Metro bundler not starting:**
```bash
npm start -- --reset-cache
```

**Gradle build errors (Android):**
```bash
cd android && ./gradlew clean && cd ..
```

**CocoaPods issues (iOS):**
```bash
cd ios && pod deintegrate && pod install && cd ..
```

**Network request failed:**
- Ensure your backend server is running
- Check the API_BASE_URL in `src/config/api.ts`
- For iOS simulator, use your machine's IP address (not localhost)
- For Android emulator, use `http://10.0.2.2:8080` to access localhost

## Development

### Adding New Features

1. Create components in `src/components/`
2. Add screens in `src/screens/`
3. Update navigation in `src/navigation/`
4. Add API calls in `src/services/api.ts`
5. Define types in `src/types/index.ts`

### Testing

Run tests:
```bash
npm test
```

### Linting

Run ESLint:
```bash
npm run lint
```

## Building for Production

### Android

1. Generate release APK:
```bash
cd android && ./gradlew assembleRelease
```

2. Find APK at: `android/app/build/outputs/apk/release/app-release.apk`

### iOS

1. Open `ios/AvinashEYE.xcworkspace` in Xcode
2. Select "Product" > "Archive"
3. Follow the App Store submission process

## License

This project is part of the Avinash-EYE media analysis platform.

## Support

For issues and questions, please refer to the main project README or create an issue in the repository.
