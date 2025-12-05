# TCT YMS Branding Kit

## Brand Overview

**Company Name**: TCT Yard Management System (TCT YMS)
**Tagline**: "Transform Your Yard Operations"
**Secondary Tagline**: "See Every Trailer. Optimize Every Move."

### Brand Personality

- **Professional**: Enterprise-grade solution, not a hobby project
- **Approachable**: Local, personal service — not a faceless corporation
- **Efficient**: We value time and productivity
- **Modern**: Current technology without unnecessary complexity
- **Reliable**: Dependable, trustworthy, consistent

### Target Audience

- Operations managers at cross-docks and 3PLs
- Yard supervisors and dispatchers
- Business owners in logistics/warehousing
- Decision-makers focused on operational efficiency

---

## Logo

### Primary Logo

```
┌─────────────────────────────────────┐
│                                     │
│   ┌─────┐                           │
│   │ 🚛  │  TCT YMS                  │
│   └─────┘  Yard Management System   │
│                                     │
└─────────────────────────────────────┘
```

**Logo Concept**: A simplified truck/trailer icon within a rounded square, representing the yard and the trailers within it.

### Logo Variations

1. **Full Logo**: Icon + "TCT YMS" + "Yard Management System"
2. **Standard Logo**: Icon + "TCT YMS"
3. **Icon Only**: Square icon with trailer silhouette
4. **Text Only**: "TCT YMS" wordmark (when icon space is limited)

### Logo Usage Guidelines

- **Minimum Size**: 120px wide for digital, 1" for print
- **Clear Space**: Maintain padding equal to the height of "TCT" on all sides
- **Background**: Use on white, light gray, or dark blue backgrounds only
- **Don't**: Stretch, rotate, add effects, or change colors outside brand palette

---

## Color Palette

### Primary Colors

| Color | Hex | RGB | Usage |
|-------|-----|-----|-------|
| **Navy Blue** | `#1C4E80` | 28, 78, 128 | Primary brand color, headers, buttons |
| **Ocean Blue** | `#2563EB` | 37, 99, 235 | Accent color, links, highlights |
| **White** | `#FFFFFF` | 255, 255, 255 | Backgrounds, text on dark |

### Secondary Colors

| Color | Hex | RGB | Usage |
|-------|-----|-----|-------|
| **Slate Gray** | `#64748B` | 100, 116, 139 | Secondary text, borders |
| **Light Gray** | `#F1F5F9` | 241, 245, 249 | Backgrounds, cards |
| **Dark Gray** | `#1E293B` | 30, 41, 59 | Dark mode background |

### Status Colors

| Color | Hex | RGB | Usage |
|-------|-----|-----|-------|
| **Success Green** | `#10B981` | 16, 185, 129 | Success states, completed |
| **Warning Amber** | `#F59E0B` | 245, 158, 11 | Warnings, attention needed |
| **Error Red** | `#EF4444` | 239, 68, 68 | Errors, critical alerts |
| **Info Blue** | `#3B82F6` | 59, 130, 246 | Information, tips |

### Trailer Status Colors (In-App)

| Status | Color | Hex |
|--------|-------|-----|
| Arrived | Blue | `#3B82F6` |
| Staged | Gray | `#6B7280` |
| At Door | Purple | `#8B5CF6` |
| Loading | Amber | `#F59E0B` |
| Loaded | Green | `#10B981` |
| Departed | Slate | `#475569` |

---

## Typography

### Primary Font: Inter

**Why Inter**: Clean, modern, highly legible on screens, excellent for data-heavy interfaces.

```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
```

### Font Weights

| Weight | Usage |
|--------|-------|
| 400 (Regular) | Body text, descriptions |
| 500 (Medium) | Labels, secondary headings |
| 600 (Semibold) | Primary headings, buttons |
| 700 (Bold) | Hero text, emphasis |

### Type Scale

| Element | Size | Weight | Line Height |
|---------|------|--------|-------------|
| H1 | 36px | 700 | 1.2 |
| H2 | 30px | 600 | 1.3 |
| H3 | 24px | 600 | 1.4 |
| H4 | 20px | 600 | 1.4 |
| Body Large | 18px | 400 | 1.6 |
| Body | 16px | 400 | 1.6 |
| Body Small | 14px | 400 | 1.5 |
| Caption | 12px | 500 | 1.4 |

### Monospace Font (Data/Code)

```css
font-family: 'JetBrains Mono', 'Fira Code', monospace;
```

Used for: Trailer numbers, timestamps, IDs, technical data

---

## Iconography

### Icon Style

- **Style**: Outline icons with 1.5-2px stroke
- **Source**: Heroicons (heroicons.com) — matches Tailwind ecosystem
- **Size**: 20px (small), 24px (default), 32px (large)

### Common Icons

| Concept | Icon Name | Usage |
|---------|-----------|-------|
| Dashboard | `home` | Main dashboard |
| Gate | `arrow-right-on-rectangle` | Gate operations |
| Trailer | `truck` | Trailer management |
| Yard Map | `map` | Yard visualization |
| Dock | `square-3-stack-3d` | Dock doors |
| Move | `arrows-right-left` | Move tasks |
| Spotter | `device-phone-mobile` | Spotter app |
| Reports | `chart-bar` | Analytics/reports |
| Settings | `cog-6-tooth` | Configuration |
| User | `user-circle` | Profile/users |
| Search | `magnifying-glass` | Search function |
| Add | `plus` | Create new |
| Edit | `pencil` | Modify |
| Delete | `trash` | Remove |
| Success | `check-circle` | Confirmation |
| Warning | `exclamation-triangle` | Alert |
| Error | `x-circle` | Error state |

---

## UI Components

### Buttons

**Primary Button**
```
Background: #1C4E80
Text: White
Hover: #163d66
Border Radius: 8px
Padding: 12px 24px
```

**Secondary Button**
```
Background: White
Text: #1C4E80
Border: 1px solid #1C4E80
Hover: #F1F5F9 background
```

**Danger Button**
```
Background: #EF4444
Text: White
Hover: #DC2626
```

### Cards

```
Background: White
Border: 1px solid #E2E8F0
Border Radius: 12px
Shadow: 0 1px 3px rgba(0,0,0,0.1)
Padding: 24px
```

### Form Inputs

```
Background: White
Border: 1px solid #D1D5DB
Border Radius: 8px
Padding: 12px 16px
Focus: Border #2563EB, Ring 2px #2563EB/20%
```

### Status Badges

```
Border Radius: 9999px (pill shape)
Padding: 4px 12px
Font Size: 12px
Font Weight: 500
```

---

## Voice & Tone

### Writing Principles

1. **Clear**: Say what you mean directly. No jargon unless necessary.
2. **Concise**: Respect the reader's time. Get to the point.
3. **Helpful**: Guide users toward success.
4. **Professional**: Confident but not arrogant.
5. **Human**: Warm and approachable, not robotic.

### Do's and Don'ts

**Do:**
- "Your trailer has been checked in successfully."
- "Move task assigned to John."
- "3 trailers need attention."

**Don't:**
- "The trailer entity has been successfully persisted to the database."
- "Task assignment operation completed without errors."
- "WARNING: Multiple trailers have exceeded dwell thresholds!!!"

### Error Messages

**Good Error Messages:**
- "Trailer number is required."
- "That dock door is currently occupied. Choose another or wait for it to be available."
- "Unable to save changes. Please check your connection and try again."

**Bad Error Messages:**
- "Error: NULL_VALUE_EXCEPTION"
- "Something went wrong."
- "FATAL ERROR CODE 500"

### Button Labels

| Action | Good | Avoid |
|--------|------|-------|
| Create | "Add Trailer" | "Submit" |
| Save | "Save Changes" | "OK" |
| Delete | "Remove Trailer" | "Delete" |
| Confirm | "Yes, Complete Move" | "Confirm" |
| Cancel | "Cancel" | "No" |

---

## Photography & Imagery

### Photo Style

- **Subject**: Real logistics operations, yards, trailers, workers
- **Tone**: Professional, active, authentic
- **Lighting**: Natural or well-lit industrial
- **Composition**: Show technology in use, people working efficiently

### Image Don'ts

- Avoid generic stock photos of people pointing at screens
- Avoid overly staged or artificial poses
- Avoid outdated equipment or technology
- Avoid empty or abandoned-looking facilities

### Illustrations

When photos aren't available:
- Use simple, flat illustrations
- Stick to brand color palette
- Keep it professional, not cartoonish
- Use for concepts that can't be photographed

---

## Application Examples

### Email Signature

```
John Smith
Sales Representative
TCT Yard Management System

📧 john@tctyms.com
📞 (956) XXX-XXXX
🌐 www.tctyms.com

Transform Your Yard Operations
```

### Business Card

```
┌─────────────────────────────────────┐
│                                     │
│  [Logo]  TCT YMS                    │
│                                     │
│  John Smith                         │
│  Sales Representative               │
│                                     │
│  📧 john@tctyms.com                 │
│  📞 (956) XXX-XXXX                  │
│  🌐 www.tctyms.com                  │
│                                     │
└─────────────────────────────────────┘
```

### Social Media Profile

- **Profile Image**: Logo icon (square)
- **Cover Image**: Yard/logistics imagery with logo overlay
- **Bio**: "Modern yard management for cross-docks & 3PLs. See every trailer. Optimize every move. #YardManagement #Logistics #Laredo"

### Presentation Slides

- **Title Slides**: Navy blue background, white text, centered logo
- **Content Slides**: White background, navy headers, gray body text
- **Accent**: Ocean blue for highlights and callouts
- **Footer**: Small logo + "www.tctyms.com"

---

## Brand Assets Checklist

### Digital Assets Needed

- [ ] Logo files (SVG, PNG, ICO)
- [ ] Favicon (16x16, 32x32, 180x180)
- [ ] Social media profile images
- [ ] Social media cover images
- [ ] Email header/footer templates
- [ ] Presentation template
- [ ] Document templates (Word, Google Docs)

### Print Assets Needed

- [ ] Business cards
- [ ] Letterhead
- [ ] Envelope design
- [ ] Brochure template
- [ ] Trade show banner
- [ ] Vehicle decal (if applicable)

### Website Assets

- [ ] Open Graph image (1200x630)
- [ ] Twitter card image (1200x600)
- [ ] App screenshot mockups
- [ ] Feature icons
- [ ] Hero images/illustrations

---

## Contact

For brand questions or asset requests:
- Email: brand@tctyms.com
- Brand assets folder: [Internal link]

---

*Last Updated: December 2024*
*Version: 1.0*
