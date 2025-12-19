# 🎨 Avinash-EYE UI Visual Guide

## Quick Reference for the New UI Design

---

## 🎯 Color Palette

### Primary Colors
```
Primary Blue:       #4285f4  ████ 
Primary Dark:       #1967d2  ████
Primary Light:      #e8f0fe  ████
```

### Google Colors
```
Google Blue:        #4285f4  ████
Google Red:         #ea4335  ████
Google Yellow:      #fbbc04  ████
Google Green:       #34a853  ████
```

### Neutral Colors
```
Surface:            #ffffff  ████
Surface Variant:    #f8f9fa  ████
Outline:            #dadce0  ████
Text Primary:       #202124  ████
Text Secondary:     #5f6368  ████
```

---

## 📐 Spacing Scale

```
4px   ■
8px   ■■
12px  ■■■
16px  ■■■■
24px  ■■■■■■
32px  ■■■■■■■■
48px  ■■■■■■■■■■■■
64px  ■■■■■■■■■■■■■■■■
```

---

## 🔤 Typography

### Font Families
1. **Display/Headings:** Google Sans
2. **Body Text:** Roboto
3. **Fallback:** Inter, System Fonts

### Font Sizes
```
3xl:  30px  - Page Titles
2xl:  24px  - Section Headers
xl:   20px  - Card Titles
lg:   18px  - Subheadings
base: 16px  - Body Text
sm:   14px  - Supporting Text
xs:   12px  - Labels/Captions
```

### Font Weights
```
Light:     300
Regular:   400
Medium:    500
Semibold:  600
Bold:      700
```

---

## 🎭 Component Patterns

### 1. Cards

**Standard Card:**
```
┌─────────────────────────────┐
│  Icon  Title                │
│                             │
│  Content goes here with     │
│  proper spacing and text    │
│                             │
│  [Button]  [Button]         │
└─────────────────────────────┘
```

**Stats Card:**
```
┌──────────────┐
│ 🎯  1,234    │
│              │
│ Total Files  │
└──────────────┘
```

**Media Card:**
```
┌──────────────┐
│  ┌────────┐  │
│  │ Image  │  │
│  └────────┘  │
│              │
│  Title       │
│  Subtitle    │
└──────────────┘
```

### 2. Buttons

**Primary Button:**
```css
Background: #4285f4
Text: White
Border Radius: 12px
Padding: 12px 24px
Shadow: md3-2
Hover: Lift + Shadow md3-3
```

**Secondary Button:**
```css
Background: White
Text: Gray 700
Border: 2px solid #dadce0
Border Radius: 12px
Padding: 12px 24px
Hover: Border primary, Background primary-50
```

**Icon Button:**
```css
Size: 40px circle
Hover: Background gray-100
```

### 3. Form Inputs

**Text Input:**
```
┌───────────────────────────────┐
│ placeholder text          🔍  │
└───────────────────────────────┘
Background: #f8f9fa
Border: 2px solid #dadce0
Focus: Border #4285f4, Ring primary-50
```

**Select Dropdown:**
```
┌───────────────────────────────┐
│ Select option...          ▼   │
└───────────────────────────────┘
```

**Checkbox/Toggle:**
```
☐ Unchecked
☑ Checked (Primary Blue)
```

### 4. Navigation

**Top App Bar:**
```
┌──────────────────────────────────────────────┐
│ ☰  Logo    [Search Box]    🔔 ⚙️ 👤          │
└──────────────────────────────────────────────┘
Height: 64px
Background: White
Border Bottom: 1px #dadce0
Shadow: sm
```

**Sidebar:**
```
┌──────────────────┐
│ 📷 Photos        │ ← Active (Blue bg)
│ 📁 Collections   │
│ 👥 People & Pets │
│ ⬆️ Upload        │
│ ──────────────── │
│ SYSTEM           │
│ 📊 Monitor       │
│ ⚙️ Settings      │
└──────────────────┘
Width: 256px (desktop)
Drawer on mobile
```

---

## 📱 Responsive Layouts

### Mobile (< 640px)
```
┌────────┐
│  Grid  │
│  2x??  │
│  cols  │
└────────┘
```

### Tablet (768px+)
```
┌────────────────┐
│  Grid 3-4 cols │
│  ┌───┐┌───┐... │
│  └───┘└───┘    │
└────────────────┘
```

### Desktop (1024px+)
```
┌─────┬──────────────────────┐
│ Nav │  Main Content Area   │
│ Bar │  Grid 5-6 cols       │
│     │  ┌───┐┌───┐┌───┐...  │
│     │  └───┘└───┘└───┘     │
└─────┴──────────────────────┘
```

---

## 🎬 Animations

### Duration Scale
```
Fast:    100ms  - Micro interactions
Normal:  200ms  - Standard transitions
Slow:    300ms  - Complex animations
```

### Easing Functions
```
emphasized:             cubic-bezier(0.2, 0, 0, 1)
emphasized-decelerate:  cubic-bezier(0.05, 0.7, 0.1, 1)
emphasized-accelerate:  cubic-bezier(0.3, 0, 0.8, 0.15)
```

### Animation Types
```
fade-in:     Opacity 0 → 1
slide-up:    translateY(10px) → 0
slide-down:  translateY(-10px) → 0
scale-in:    scale(0.9) → 1
bounce-in:   scale with bounce
```

---

## 🎨 Page-Specific Patterns

### Login/Register
```
┌────────────────────────────────────────────┐
│                                            │
│  ┌──────────┬─────────────────────────┐   │
│  │          │                         │   │
│  │  Brand   │  ┌─────────────────┐   │   │
│  │  Area    │  │  Form Card      │   │   │
│  │          │  │                 │   │   │
│  │  (Grad)  │  │  Email          │   │   │
│  │          │  │  Password       │   │   │
│  │          │  │                 │   │   │
│  │          │  │  [Sign In]      │   │   │
│  │          │  └─────────────────┘   │   │
│  │          │                         │   │
│  └──────────┴─────────────────────────┘   │
│                                            │
└────────────────────────────────────────────┘
```

### Photo Gallery
```
┌────────────────────────────────────────────┐
│  Photos                    Sort ☑ ⭐ 🗑️ ⬆️   │
├────────────────────────────────────────────┤
│  ┌───┐┌───┐┌───┐┌───┐┌───┐┌───┐          │
│  │ 1 ││ 2 ││ 3 ││ 4 ││ 5 ││ 6 │          │
│  └───┘└───┘└───┘└───┘└───┘└───┘          │
│  ┌───┐┌───┐┌───┐┌───┐┌───┐┌───┐          │
│  │ 7 ││ 8 ││ 9 ││10 ││11 ││12 │          │
│  └───┘└───┘└───┘└───┘└───┘└───┘          │
└────────────────────────────────────────────┘
```

### Collections
```
┌────────────────────────────────────────────┐
│  Collections                               │
│                                            │
│  ┌────┐┌────┐┌────┐    Stats              │
│  │📁1││📁2││📁3│    ┌──┐┌──┐┌──┐          │
│  └────┘└────┘└────┘    └──┘└──┘└──┘       │
│                                            │
│  👥 People                                 │
│  ┌────┐┌────┐┌────┐┌────┐                 │
│  │ 😊 ││ 😊 ││ 😊 ││ 😊 │                 │
│  └────┘└────┘└────┘└────┘                 │
│                                            │
│  🏷️ Categories                             │
│  ┌─────────┐┌─────────┐┌─────────┐        │
│  │ Nature  ││  Food   ││  Travel │        │
│  │ [imgs]  ││ [imgs]  ││ [imgs]  │        │
│  │ 42 items││ 28 items││ 156 its │        │
│  └─────────┘└─────────┘└─────────┘        │
└────────────────────────────────────────────┘
```

### System Monitor
```
┌────────────────────────────────────────────┐
│  System Monitor                    Live 🟢 │
│  Overview | Services | Queues | Database   │
├────────────────────────────────────────────┤
│  ┌──────┐┌──────┐┌──────┐┌──────┐          │
│  │ CPU  ││ MEM  ││ DISK ││  AI  │          │
│  │ 45% ││ 62% ││ 78% ││ ✓   │          │
│  │ ████ ││ ████ ││ ████ ││     │          │
│  └──────┘└──────┘└──────┘└──────┘          │
│                                            │
│  ┌─────────────────┐┌──────────────────┐   │
│  │ Queue Status    ││ Database Stats   │   │
│  │ Pending: 12     ││ Total: 1,234     │   │
│  │ Completed: 456  ││ Processed: 1,200 │   │
│  └─────────────────┘└──────────────────┘   │
└────────────────────────────────────────────┘
```

### Upload
```
┌────────────────────────────────────────────┐
│  ⚡ Instant Upload                          │
├────────────────────────────────────────────┤
│                                            │
│  ┌──────────────────────────────────────┐  │
│  │                                      │  │
│  │            ☁️                        │  │
│  │                                      │  │
│  │     Drop files here or click        │  │
│  │     to browse                       │  │
│  │                                      │  │
│  │     📷 Images  🎬 Videos  📄 Docs    │  │
│  │                                      │  │
│  └──────────────────────────────────────┘  │
│                                            │
│  Recently Uploaded                         │
│  ┌───┐┌───┐┌───┐┌───┐┌───┐┌───┐          │
│  │ ✓ ││ ✓ ││ ⏳ ││ ⏳ ││ ✓ ││ ✓ │          │
│  └───┘└───┘└───┘└───┘└───┘└───┘          │
└────────────────────────────────────────────┘
```

---

## 🎯 Icons

Using **Material Symbols Outlined**

Common Icons:
```
photo_library  - Photos
folder         - Collections
face           - People
upload         - Upload
monitoring     - System Monitor
settings       - Settings
search         - Search
close          - Close
check_circle   - Success
error          - Error
star           - Favorite
delete         - Delete
download       - Download
edit           - Edit
info           - Info
menu           - Menu
more_vert      - More options
```

---

## ✅ Status Indicators

```
🟢 Online    - Green dot + "Online"
🔴 Offline   - Red dot + "Offline"
🟡 Warning   - Yellow dot + "Warning"
⏳ Processing - Blue dot (pulse) + "Processing"
✓ Success    - Green checkmark
✗ Error      - Red X
```

---

## 🎨 Shadows & Elevation

```
Level 1 (Resting):  0 1px 2px rgba(60,64,67,0.3)
Level 2 (Raised):   0 1px 2px, 0 2px 6px
Level 3 (Elevated): 0 1px 3px, 0 4px 8px
Level 4 (High):     0 2px 3px, 0 6px 10px
Level 5 (Modal):    0 4px 4px, 0 8px 12px
```

---

## 📱 Touch Targets

```
Minimum: 44x44px (iOS) / 48x48px (Material)
Preferred: 48x48px for all interactive elements
Spacing: 8px minimum between targets
```

---

## 🎭 States

### Interactive Elements
```
Rest:     Default appearance
Hover:    Slight lift, shadow increase
Active:   Press down effect
Focus:    Blue ring (4px, primary-200)
Disabled: 50% opacity, no pointer
```

### Loading
```
Spinner:  Rotating circle
Progress: Animated bar
Skeleton: Pulsing placeholder
```

---

## 🌐 Accessibility

### Color Contrast
```
Normal Text:  4.5:1 minimum
Large Text:   3:1 minimum
UI Elements:  3:1 minimum
```

### Focus States
```
Visible outline on all interactive elements
2px blue outline with 2px offset
Skip to main content link
```

### Screen Reader
```
Descriptive alt text for images
ARIA labels on icons
Semantic HTML structure
```

---

## 🎓 Best Practices

### DO ✅
- Use consistent spacing (8px grid)
- Apply shadows for elevation
- Provide visual feedback
- Use semantic colors
- Test on multiple screen sizes
- Add loading states
- Include empty states
- Write descriptive labels

### DON'T ❌
- Mix different design systems
- Use arbitrary spacing values
- Forget hover/focus states
- Ignore accessibility
- Use colors without meaning
- Create cluttered interfaces
- Skip error handling
- Use tiny touch targets

---

## 🚀 Quick Tips

1. **Spacing:** Use multiples of 4px (4, 8, 12, 16, 24, 32, 48, 64)
2. **Typography:** Limit to 2-3 font sizes per section
3. **Colors:** Stick to the palette, use sparingly for accents
4. **Shadows:** More shadow = higher elevation
5. **Animations:** Keep under 300ms for most transitions
6. **Icons:** Always pair with text labels
7. **White Space:** Use generously for breathing room
8. **Consistency:** Repeat patterns throughout the app

---

**Remember:** The best UI is invisible - it just works! 🎯

