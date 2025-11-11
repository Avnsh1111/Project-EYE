# Settings Persistence Fix

## ✅ What Was Fixed

### The Problem:
Boolean settings (Ollama Enabled, Face Detection) were being saved as strings `'true'`/`'false'` instead of actual boolean values, causing checkboxes to become unchecked after page refresh.

### The Solution:
1. ✅ Updated `save()` method to save actual boolean values
2. ✅ Updated `loadSettings()` method to handle both string and boolean values
3. ✅ Updated `AiService` to properly parse boolean settings
4. ✅ Cleared Laravel cache

## How to Apply the Fix

### Step 1: Clear Cache (Already Done)
```bash
php artisan cache:clear
```

### Step 2: Re-save Your Settings
Go to `/settings` and:
1. Check ✅ **Enable Ollama** (if you want it)
2. Check ✅ **Enable Face Detection** (if you want it)
3. Select your models
4. Click **"Save Settings"**
5. **Refresh the page** - checkboxes should stay checked! ✨

### Step 3: Test
1. Enable both checkboxes
2. Click "Save Settings"
3. Refresh the page (F5 or Cmd+R)
4. Both checkboxes should remain checked ✅

## Technical Details

### Before Fix:
```php
// Saved as strings
Setting::set('ollama_enabled', 'true');  // Stored as string
Setting::set('face_detection_enabled', 'false');  // Stored as string
```

### After Fix:
```php
// Saved as actual booleans
Setting::set('ollama_enabled', true);  // Stored as boolean
Setting::set('face_detection_enabled', false);  // Stored as boolean
```

### Loading Logic:
```php
// Handles both old string values and new boolean values
$ollamaEnabled = Setting::get('ollama_enabled', false);
$this->ollama_enabled = is_bool($ollamaEnabled) 
    ? $ollamaEnabled 
    : ($ollamaEnabled === 'true' || $ollamaEnabled === true);
```

## Files Modified

1. ✅ `app/Livewire/Settings.php` - Fixed save() and loadSettings() methods
2. ✅ `app/Services/AiService.php` - Fixed boolean handling in analyzeImage()

## What Settings Are Now Persistent

✅ **Captioning Model** - Saves and loads correctly
✅ **Embedding Model** - Saves and loads correctly
✅ **Face Detection** - ✨ NOW FIXED - stays checked/unchecked
✅ **Ollama Enabled** - ✨ NOW FIXED - stays checked/unchecked
✅ **Ollama Model** - Saves and loads correctly

## Verification

### Check Database:
```bash
php artisan tinker
```

Then run:
```php
use App\Models\Setting;

// Check current values
$settings = Setting::whereIn('key', ['face_detection_enabled', 'ollama_enabled'])->get();
foreach ($settings as $s) {
    echo $s->key . ": " . json_encode($s->value) . " (type: " . gettype($s->value) . ")\n";
}

// Should show:
// face_detection_enabled: true (type: boolean)
// ollama_enabled: false (type: boolean)
```

### Check UI:
1. Go to `/settings`
2. Enable both checkboxes
3. Click "Save Settings"
4. **Hard refresh** (Ctrl+Shift+R or Cmd+Shift+R)
5. Checkboxes should be checked ✅

## Troubleshooting

### If checkboxes still uncheck after refresh:

**Option 1: Manual Database Fix**
```bash
php artisan tinker
```

Then:
```php
use App\Models\Setting;

// Fix face detection
Setting::set('face_detection_enabled', true);

// Fix ollama
Setting::set('ollama_enabled', false); // or true if you want it enabled

// Verify
$settings = Setting::whereIn('key', ['face_detection_enabled', 'ollama_enabled'])->get();
dd($settings->pluck('value', 'key'));
```

**Option 2: Delete Old Settings**
```bash
php artisan tinker
```

Then:
```php
use App\Models\Setting;

// Delete old string-based settings
Setting::whereIn('key', ['face_detection_enabled', 'ollama_enabled'])->delete();

// Clear cache
cache()->flush();

// Now go to settings page and set them again
```

**Option 3: Check Browser Cache**
- Hard refresh: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
- Or clear browser cache completely

## Expected Behavior After Fix

### When You Enable Face Detection:
1. ✅ Checkbox shows as checked
2. ✅ Click "Save Settings" - success message shows
3. ✅ Refresh page - checkbox STAYS checked
4. ✅ Next image upload will detect faces

### When You Enable Ollama:
1. ✅ Checkbox shows as checked
2. ✅ Model dropdown appears
3. ✅ Click "Save Settings" - success message shows
4. ✅ Refresh page - checkbox STAYS checked, model STAYS selected
5. ✅ Next image upload will use Ollama (if installed)

## Status

✅ **Fix Applied**: Yes
✅ **Cache Cleared**: Yes
✅ **Tested**: Ready for user testing
✅ **Backward Compatible**: Yes (handles old string values too)

---

**Try it now!** Go to `/settings` → Enable checkboxes → Save → Refresh → They should stay checked! 🎉

