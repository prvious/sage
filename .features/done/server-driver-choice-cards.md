---
name: server-driver-choice-cards
description: Convert server driver radio buttons to visual choice cards
depends_on: artisan-server-driver, project-pages-centered-layout
---

## Feature Description

Replace the traditional radio button inputs for the "Server Driver" field on project creation and edit pages with visually appealing choice cards. Each card will display the server driver option as a clickable card with an icon, title, description, and visual selection state.

This improves the user experience by:

- **Visual Clarity**: Cards are larger and easier to distinguish than radio buttons
- **More Information**: Each card can display a description, use case, and icon
- **Better Selection Feedback**: Visual hover and selected states
- **Professional Appearance**: Modern card-based UI pattern
- **Accessibility**: Maintains keyboard navigation and screen reader support

The implementation will use Shadcn UI's Field, FieldLabel, and RadioItem components (or RadioGroup) to create accessible, keyboard-navigable choice cards.

## Implementation Plan

### Frontend Components

**Pages to Modify:**

- `resources/js/pages/projects/create.tsx` - Replace radio buttons with choice cards
- `resources/js/pages/projects/edit.tsx` - Replace radio buttons with choice cards

**Components to Create:**

- `resources/js/components/forms/server-driver-selector.tsx` - Reusable server driver choice cards component
- `resources/js/components/forms/choice-card.tsx` - Generic choice card component (optional, for reuse)

**Shadcn Components to Install:**

- `pnpm dlx shadcn@latest add radio-group` - For accessible radio group
- May already have `label` component

**Icons from Lucide:**

- `Server` or `Zap` - Icon for Caddy
- `Server` or `Box` - Icon for Nginx
- `Code2` or `Terminal` - Icon for Artisan Server

**Component Structure:**

```tsx
// resources/js/components/forms/server-driver-selector.tsx
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Server, Box, Terminal } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ServerDriverSelectorProps {
    value: 'caddy' | 'nginx' | 'artisan';
    onChange: (value: 'caddy' | 'nginx' | 'artisan') => void;
    error?: string;
}

const serverDrivers = [
    {
        value: 'caddy',
        label: 'Caddy',
        description: 'Modern web server with automatic HTTPS',
        icon: Server,
        recommended: true,
    },
    {
        value: 'nginx',
        label: 'Nginx',
        description: 'High-performance production web server',
        icon: Box,
    },
    {
        value: 'artisan',
        label: 'Artisan Server',
        description: 'Lightweight PHP development server',
        icon: Terminal,
        badge: 'Development Only',
    },
];

export function ServerDriverSelector({ value, onChange, error }: ServerDriverSelectorProps) {
    return (
        <div className='space-y-2'>
            <Label>Server Driver</Label>
            <RadioGroup value={value} onValueChange={onChange} className='grid gap-4 sm:grid-cols-3'>
                {serverDrivers.map((driver) => {
                    const Icon = driver.icon;
                    const isSelected = value === driver.value;

                    return (
                        <label
                            key={driver.value}
                            className={cn(
                                'relative flex cursor-pointer flex-col items-start gap-3 rounded-lg border-2 p-4 transition-all hover:border-primary/50',
                                isSelected ? 'border-primary bg-primary/5' : 'border-border bg-card hover:bg-accent/50',
                            )}
                        >
                            <RadioGroupItem value={driver.value} id={driver.value} className='sr-only' />

                            {/* Icon */}
                            <div className={cn('rounded-md p-2', isSelected ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground')}>
                                <Icon className='h-5 w-5' />
                            </div>

                            {/* Title and Badge */}
                            <div className='flex w-full items-center justify-between gap-2'>
                                <span className='font-semibold'>{driver.label}</span>
                                {driver.recommended && (
                                    <Badge variant='secondary' className='text-xs'>
                                        Recommended
                                    </Badge>
                                )}
                                {driver.badge && (
                                    <Badge variant='outline' className='text-xs'>
                                        {driver.badge}
                                    </Badge>
                                )}
                            </div>

                            {/* Description */}
                            <p className='text-sm text-muted-foreground'>{driver.description}</p>

                            {/* Selected Indicator */}
                            {isSelected && (
                                <div className='absolute right-3 top-3'>
                                    <div className='flex h-5 w-5 items-center justify-center rounded-full bg-primary'>
                                        <svg className='h-3 w-3 text-primary-foreground' fill='currentColor' viewBox='0 0 12 12'>
                                            <path d='M10.28 2.28L3.989 8.575 1.695 6.28A1 1 0 00.28 7.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 2.28z' />
                                        </svg>
                                    </div>
                                </div>
                            )}
                        </label>
                    );
                })}
            </RadioGroup>
            {error && <p className='text-sm text-destructive'>{error}</p>}
        </div>
    );
}
```

### Integration with Forms

**In Create/Edit Pages:**

```tsx
// resources/js/pages/projects/create.tsx
import { ServerDriverSelector } from '@/components/forms/server-driver-selector';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        path: '',
        server_driver: 'caddy' as 'caddy' | 'nginx' | 'artisan',
        base_url: '',
    });

    return (
        <CenteredCardLayout>
            <CardHeader>
                <CardTitle>Create Project</CardTitle>
                <CardDescription>Configure your Laravel project settings</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className='space-y-6'>
                    {/* Other fields */}

                    {/* Server Driver Choice Cards */}
                    <ServerDriverSelector value={data.server_driver} onChange={(value) => setData('server_driver', value)} error={errors.server_driver} />

                    {/* Other fields */}
                </form>
            </CardContent>
        </CenteredCardLayout>
    );
}
```

### Alternative: Custom Radio with Base UI

If using @base-ui/react (which is already in dependencies):

```tsx
import { Field, Label, Radio, RadioGroup } from '@base-ui/react/radio-group';

<Field>
    <Label>Server Driver</Label>
    <RadioGroup value={value} onValueChange={onChange}>
        {/* Choice cards */}
    </RadioGroup>
</Field>;
```

### Responsive Design

**Mobile (< 640px):**

- Stack cards vertically (1 column)
- Full width cards
- Reduced padding

**Tablet (640px - 1024px):**

- 2 columns grid
- Comfortable spacing

**Desktop (> 1024px):**

- 3 columns grid (one card per driver)
- Optimal card width

```tsx
<RadioGroup value={value} onValueChange={onChange} className='grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'>
    {/* Cards */}
</RadioGroup>
```

### Styling Details

**Card States:**

1. **Default State:**
    - Border: `border-border`
    - Background: `bg-card`
    - Text: Normal contrast

2. **Hover State:**
    - Border: `hover:border-primary/50`
    - Background: `hover:bg-accent/50`
    - Cursor: `cursor-pointer`

3. **Selected State:**
    - Border: `border-primary` (2px, bold)
    - Background: `bg-primary/5` (subtle tint)
    - Checkmark icon in top-right corner

4. **Focus State (Keyboard):**
    - Ring: `focus-visible:ring-2 ring-ring`
    - Outline offset for clarity

### Icon Selection

**Caddy:**

- `Server` (generic server)
- `Zap` (fast/modern)
- `Shield` (secure/HTTPS)

**Nginx:**

- `Server` (generic server)
- `Box` (stable/container)
- `Database` (production-ready)

**Artisan Server:**

- `Terminal` (CLI-based)
- `Code2` (development)
- `Hammer` (build/dev tool)

### Card Content Layout

```
┌─────────────────────────────────┐
│ [Icon]              [✓ Selected]│
│                                 │
│ Title            [Badge]        │
│                                 │
│ Description text goes here...   │
└─────────────────────────────────┘
```

**Vertical Spacing:**

- Icon: Top of card
- Title + Badge: Below icon
- Description: Below title
- Total height: Auto-fit content

## Acceptance Criteria

- [ ] ServerDriverSelector component is created
- [ ] Choice cards display on project create page
- [ ] Choice cards display on project edit page
- [ ] Three cards display: Caddy, Nginx, Artisan Server
- [ ] Each card shows icon, title, description
- [ ] Caddy card has "Recommended" badge
- [ ] Artisan card has "Development Only" badge
- [ ] Clicking a card selects that driver
- [ ] Selected card shows visual indication (border + checkmark)
- [ ] Hover state provides visual feedback
- [ ] Keyboard navigation works (Tab, Arrow keys, Space/Enter)
- [ ] Screen readers announce options correctly
- [ ] Error message displays below cards if validation fails
- [ ] Cards are responsive (1/2/3 columns on mobile/tablet/desktop)
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location:** `tests/Browser/Projects/ServerDriverChoiceCardsTest.php`

**Key test cases:**

- Test three server driver cards are visible on create page
- Test clicking Caddy card selects Caddy driver
- Test clicking Nginx card selects Nginx driver
- Test clicking Artisan card selects Artisan driver
- Test selected card shows visual indication
- Test Caddy card displays "Recommended" badge
- Test Artisan card displays "Development Only" badge
- Test keyboard navigation works (Tab through cards)
- Test Space/Enter key selects focused card
- Test hover state provides visual feedback
- Test cards are responsive on mobile (375px width)
- Test cards are responsive on tablet (768px width)
- Test cards are responsive on desktop (1280px width)
- Test validation error displays below cards

### Component Tests (Optional)

**Test file location:** `tests/Frontend/Components/ServerDriverSelectorTest.tsx` (if using Vitest)

**Key test cases:**

- Test component renders three cards
- Test onChange callback fires with correct value
- Test error prop displays error message
- Test selected card has proper styling

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Design Inspiration

**Modern Choice Card Patterns:**

- Stripe's payment method selection
- Vercel's framework selection
- GitHub's repository visibility selection

**Key Design Principles:**

- Large clickable area (entire card)
- Clear visual hierarchy (icon → title → description)
- Subtle but clear selected state
- Professional badges for context

### Accessibility

**ARIA Attributes:**

```tsx
<label className='...' role='radio' aria-checked={isSelected} aria-label={`${driver.label}: ${driver.description}`}>
    {/* Card content */}
</label>
```

**Keyboard Navigation:**

- Tab: Move between cards
- Arrow keys: Navigate options (if using RadioGroup)
- Space/Enter: Select focused card
- Escape: Blur focus (standard behavior)

**Screen Reader:**

- Announces: "Server Driver, radio group"
- For each option: "Caddy, Modern web server with automatic HTTPS, recommended"
- Selection state: "Checked" or "Not checked"

### Optional Enhancements

**Hover Effects:**
Add subtle scale/shadow on hover:

```tsx
className={cn(
  'transition-all hover:scale-[1.02] hover:shadow-md',
  // ... other classes
)}
```

**Icons with Color:**
Different colored icons per driver:

```tsx
<div
    className={cn(
        'rounded-md p-2',
        isSelected && driver.value === 'caddy' && 'bg-blue-500/10 text-blue-600',
        isSelected && driver.value === 'nginx' && 'bg-green-500/10 text-green-600',
        isSelected && driver.value === 'artisan' && 'bg-orange-500/10 text-orange-600',
        !isSelected && 'bg-muted text-muted-foreground',
    )}
>
    <Icon className='h-5 w-5' />
</div>
```

**Feature Highlights:**
Add bullet points or feature tags:

```tsx
<div className='mt-2 flex flex-wrap gap-1'>
    <Badge variant='outline' className='text-xs'>
        Automatic HTTPS
    </Badge>
    <Badge variant='outline' className='text-xs'>
        Zero Config
    </Badge>
</div>
```

**Performance Badge:**
Show relative performance indicators:

```tsx
// For Caddy/Nginx
<div className="flex items-center gap-1 text-xs text-muted-foreground">
  <Zap className="h-3 w-3" />
  <span>High Performance</span>
</div>

// For Artisan
<div className="flex items-center gap-1 text-xs text-muted-foreground">
  <Turtle className="h-3 w-3" />
  <span>Development Speed</span>
</div>
```

### Reusable Choice Card Component

For other parts of the app that need choice cards:

```tsx
// resources/js/components/ui/choice-card.tsx
interface ChoiceCardProps {
    value: string;
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    description: string;
    badge?: string;
    recommended?: boolean;
    selected?: boolean;
    onClick?: () => void;
}

export function ChoiceCard({ icon: Icon, label, description, badge, recommended, selected, onClick }: ChoiceCardProps) {
    return (
        <button
            type='button'
            onClick={onClick}
            className={cn(
                'relative flex w-full cursor-pointer flex-col items-start gap-3 rounded-lg border-2 p-4 text-left transition-all',
                selected ? 'border-primary bg-primary/5' : 'border-border bg-card hover:border-primary/50 hover:bg-accent/50',
            )}
        >
            {/* Icon */}
            <div className={cn('rounded-md p-2', selected ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground')}>
                <Icon className='h-5 w-5' />
            </div>

            {/* Title and Badge */}
            <div className='flex w-full items-center justify-between gap-2'>
                <span className='font-semibold'>{label}</span>
                {recommended && <Badge variant='secondary'>Recommended</Badge>}
                {badge && <Badge variant='outline'>{badge}</Badge>}
            </div>

            {/* Description */}
            <p className='text-sm text-muted-foreground'>{description}</p>

            {/* Selected Checkmark */}
            {selected && (
                <div className='absolute right-3 top-3'>
                    <CheckCircle2 className='h-5 w-5 fill-primary text-primary-foreground' />
                </div>
            )}
        </button>
    );
}
```

### Alternative Layouts

**Compact Cards (if 3 options feels too wide):**

```tsx
className = 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3';
```

**Vertical Stack (if descriptions are long):**

```tsx
className = 'flex flex-col gap-4';
```

**Horizontal Cards (mobile-first):**

```tsx
<label className='flex items-center gap-4 rounded-lg border-2 p-4'>
    <div className='shrink-0'>
        <Icon className='h-8 w-8' />
    </div>
    <div className='flex-1'>
        <div className='font-semibold'>{label}</div>
        <p className='text-sm text-muted-foreground'>{description}</p>
    </div>
    {selected && <CheckCircle2 className='h-5 w-5 text-primary' />}
</label>
```

### Dependencies

This feature depends on:

- `artisan-server-driver`: Adds the third server driver option
- `project-pages-centered-layout`: Uses the updated form layout

**Implementation order:**

1. Complete `artisan-server-driver` (adds artisan option)
2. Complete `project-pages-centered-layout` (modernizes forms)
3. Then implement `server-driver-choice-cards` (visual upgrade)
