# Gallery Troubleshooting Guide

## ✅ Verification Steps

### 1. Check if Services are Running
```bash
docker-compose ps
```
All services should be "Up" status.

### 2. Check if Images Exist in Database
```bash
docker-compose exec laravel-app php artisan tinker --execute="echo App\Models\MediaFile::where('media_type', 'image')->count();"
```
Should return a number > 0.

### 3. Check Browser Console
Open browser DevTools (F12) → Console tab
Look for any JavaScript errors, especially:
- Livewire errors
- Alpine.js errors
- 404 errors for assets

### 4. Check Network Tab
Open browser DevTools (F12) → Network tab
- Click on an image
- Look for `/livewire/message/...` requests
- Check if they return 200 OK

## 🔧 Common Issues and Fixes

### Issue 1: Buttons Not Responding
**Symptoms:** Clicking buttons does nothing

**Causes:**
1. JavaScript not loading
2. Livewire not initialized
3. Alpine.js not loaded

**Fixes:**
```bash
# Clear all caches
docker-compose exec laravel-app php artisan view:clear
docker-compose exec laravel-app php artisan cache:clear
docker-compose exec laravel-app php artisan config:clear

# Restart containers
docker-compose restart laravel-app nginx
```

**Check in Browser:**
1. Open Console (F12)
2. Type: `window.Livewire`
3. Should show an object (not undefined)
4. Type: `window.Alpine`
5. Should show an object (not undefined)

### Issue 2: Images Not Loading
**Symptoms:** Gallery shows empty or no images

**Causes:**
1. No images in database with `media_type = 'image'`
2. Images filtered out (trash/favorites)
3. Query filter issue

**Fixes:**
```bash
# Check database
docker-compose exec laravel-app php artisan tinker
>>> App\Models\MediaFile::where('media_type', 'image')->count();
>>> App\Models\MediaFile::where('media_type', 'image')->first();
```

### Issue 3: Sorting Not Working
**Symptoms:** Dropdown changes but images don't resort

**Check:**
1. Does the dropdown value change when you select it?
2. Look in browser Network tab for Livewire requests
3. Check if `updatedSortBy()` method exists in component

**Verify:**
```bash
docker-compose exec laravel-app php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo method_exists(App\Livewire\EnhancedImageGallery::class, 'updatedSortBy') ? 'EXISTS' : 'MISSING';
"
```

### Issue 4: Selection Mode Not Working
**Symptoms:** Can't select images

**Check:**
1. Click the "Select" button first
2. Should see checkboxes appear
3. Component property `$selectionMode` should toggle

**Debug:**
Add this temporarily to the view after the title:
```blade
<div class="text-sm text-gray-500">
    Selection Mode: {{ $selectionMode ? 'ON' : 'OFF' }}
    | Selected: {{ count($selectedIds) }}
</div>
```

### Issue 5: Favorite Button Not Working
**Symptoms:** Star icon doesn't toggle

**Check:**
1. Look for `wire:click="toggleFavorite(...)"` in HTML source
2. Check browser console for errors
3. Verify method exists

**Test manually:**
```bash
docker-compose exec laravel-app php artisan tinker
>>> $file = App\Models\MediaFile::where('media_type', 'image')->first();
>>> $file->is_favorite = !$file->is_favorite;
>>> $file->save();
>>> echo $file->is_favorite ? 'TRUE' : 'FALSE';
```

## 🧪 Manual Testing Checklist

### Basic Functions:
- [ ] Gallery loads and shows images
- [ ] Can scroll through images
- [ ] Images have proper thumbnails

### Click Image:
- [ ] Click on image opens lightbox/popup
- [ ] Can see full-size image
- [ ] Navigation arrows work (previous/next)
- [ ] Close button works
- [ ] ESC key closes lightbox

### Selection Mode:
- [ ] Click "Select" button
- [ ] Checkboxes appear on images
- [ ] Click images to select them
- [ ] Selection count updates
- [ ] "Select All" works
- [ ] "Deselect All" works
- [ ] Bulk actions appear

### Sorting:
- [ ] Dropdown shows options
- [ ] Selecting "Date taken" resorts images
- [ ] Selecting "Recently added" resorts images
- [ ] Selecting "Favorites first" resorts images

### Favorite:
- [ ] Star icon in lightbox
- [ ] Click star toggles favorite
- [ ] Icon fills when favorited
- [ ] Filter by favorites button works

### Delete:
- [ ] Delete button in lightbox
- [ ] Moves image to trash
- [ ] Toggle trash view works
- [ ] Restore button appears in trash
- [ ] Restore works

## 📊 Expected Behavior

### Normal State:
- Gallery shows masonry grid of images
- Hover shows subtle highlight
- Click opens lightbox
- Scroll is smooth

### Selection Mode State:
- Checkboxes visible on each image
- Click toggles selection (not open)
- Bulk action bar appears at top
- "Cancel" or "X" button exits mode

### Lightbox State:
- Full-screen overlay with black background
- Large image in center
- Navigation arrows on sides
- Action buttons at top-right
- Details panel can slide in from right

## 🔍 Debug Mode

Add this to `EnhancedImageGallery.php` mount() method:
```php
public function mount()
{
    \Log::info('Gallery mounted', [
        'images_count' => MediaFile::where('media_type', 'image')->count(),
        'user_id' => auth()->id(),
    ]);
    
    // existing mount code...
}
```

Then check logs:
```bash
docker-compose exec laravel-app tail -f storage/logs/laravel.log
```

## 🆘 Still Not Working?

### Hard Reset:
```bash
# Stop everything
docker-compose down

# Clear all caches
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*

# Restart
docker-compose up -d

# Clear Laravel caches
docker-compose exec laravel-app php artisan optimize:clear
docker-compose exec laravel-app php artisan view:clear
docker-compose exec laravel-app php artisan cache:clear
```

### Check Livewire Version:
```bash
docker-compose exec laravel-app composer show livewire/livewire
```
Should be version 3.x

### Reinstall Livewire:
```bash
docker-compose exec laravel-app composer require livewire/livewire
docker-compose exec laravel-app php artisan livewire:publish --config
```

## 📝 What to Report

If still not working, please provide:

1. **Browser Console Errors** (F12 → Console)
2. **Network Tab** (F12 → Network, filter by "livewire")
3. **Laravel Logs**: `docker-compose exec laravel-app tail -50 storage/logs/laravel.log`
4. **Container Status**: `docker-compose ps`
5. **Image Count**: `docker-compose exec laravel-app php artisan tinker --execute="echo App\Models\MediaFile::where('media_type', 'image')->count();"`
6. **Specific action that's not working**
7. **Expected behavior vs actual behavior**

