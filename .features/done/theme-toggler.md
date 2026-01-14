---
name: theme-toggler
description: Fixed bottom-left theme switcher with system/light/dark options
depends_on: null
---

## Feature Description

Add a theme toggler component to pages using the `CenteredCardLayout` component (project list, project creation, project edit, etc.). The toggler is positioned fixed in the bottom-left corner of the screen with a link-style button that opens a dropdown menu when clicked. The dropdown provides three theme options: "System", "Light", and "Dark".

Key features:

- **Fixed Position**: Bottom-left corner with appropriate spacing (e.g., `left-4 bottom-4`)
- **Base UI Components**: Uses `@base-ui/react` dropdown menu with `render` prop pattern (not Radix UI's `asChild`)
- **Link Variant Button**: Subtle button styling that matches the application's design language
- **Theme Persistence**: Stores user's theme preference in localStorage
- **System Theme Detection**: Respects user's OS theme preference when "System" is selected
- **Dark Mode Support**: Applies `dark` class to document element for Tailwind CSS dark mode

This provides a consistent theme switching experience across all pages that use the centered card layout pattern, allowing users to customize their viewing experience.

## Implementation Plan

### Frontend Components

**Hooks to Create:**

- `resources/js/hooks/use-theme.tsx` - Custom hook for theme management
    - Manages theme state (system, light, dark)
    - Persists theme preference to localStorage
    - Listens to system theme changes
    - Applies theme to document element

**Components to Create:**

- `resources/js/components/theme-toggler.tsx` - Theme switcher component
    - Fixed position button with dropdown
    - Uses Base UI DropdownMenu components
    - Shows current theme selection
    - Icons for each theme option (Moon, Sun, Monitor)

**Components to Modify:**

- `resources/js/components/layouts/centered-card-layout.tsx` - Add ThemeToggler component

**Shadcn/Base UI Components:**

- Already have `dropdown-menu` component using Base UI

**Icons from Lucide:**

- `Moon` - Dark mode icon
- `Sun` - Light mode icon
- `Monitor` - System theme icon
- `Check` - Selected theme indicator (already in dropdown-menu)

**No Backend Changes:**

- Theme preference is stored in browser localStorage
- No server-side state or database changes needed

### useTheme Hook Implementation

```tsx
// resources/js/hooks/use-theme.tsx
import { useEffect, useState } from 'react';

type Theme = 'system' | 'light' | 'dark';

interface UseThemeReturn {
    theme: Theme;
    setTheme: (theme: Theme) => void;
    resolvedTheme: 'light' | 'dark';
}

export function useTheme(): UseThemeReturn {
    const [theme, setThemeState] = useState<Theme>(() => {
        // Initialize from localStorage or default to 'system'
        if (typeof window !== 'undefined') {
            const stored = localStorage.getItem('sage-theme') as Theme | null;
            return stored ?? 'system';
        }
        return 'system';
    });

    const [resolvedTheme, setResolvedTheme] = useState<'light' | 'dark'>('light');

    // Function to get system theme preference
    const getSystemTheme = (): 'light' | 'dark' => {
        if (typeof window === 'undefined') {
            return 'light';
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    // Update resolved theme when theme or system preference changes
    useEffect(() => {
        const updateResolvedTheme = () => {
            const resolved = theme === 'system' ? getSystemTheme() : theme;
            setResolvedTheme(resolved);

            // Apply theme to document
            const root = window.document.documentElement;
            root.classList.remove('light', 'dark');
            root.classList.add(resolved);
        };

        updateResolvedTheme();

        // Listen for system theme changes
        if (theme === 'system') {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const handleChange = () => updateResolvedTheme();

            // Modern browsers
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleChange);
                return () => mediaQuery.removeEventListener('change', handleChange);
            }
            // Legacy browsers
            mediaQuery.addListener(handleChange);
            return () => mediaQuery.removeListener(handleChange);
        }
    }, [theme]);

    // Persist theme to localStorage
    const setTheme = (newTheme: Theme) => {
        setThemeState(newTheme);
        localStorage.setItem('sage-theme', newTheme);
    };

    return { theme, setTheme, resolvedTheme };
}
```

### ThemeToggler Component Implementation

```tsx
// resources/js/components/theme-toggler.tsx
import { Moon, Sun, Monitor } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useTheme } from '@/hooks/use-theme';

export function ThemeToggler() {
    const { theme, setTheme } = useTheme();

    const themes = [
        { value: 'system' as const, label: 'System', icon: Monitor },
        { value: 'light' as const, label: 'Light', icon: Sun },
        { value: 'dark' as const, label: 'Dark', icon: Moon },
    ];

    const currentTheme = themes.find((t) => t.value === theme) ?? themes[0];
    const CurrentIcon = currentTheme.icon;

    return (
        <div className='fixed left-4 bottom-4 z-50'>
            <DropdownMenu>
                <DropdownMenuTrigger
                    render={
                        <Button variant='link' size='sm' className='gap-2'>
                            <CurrentIcon className='h-4 w-4' />
                            <span className='text-xs'>{currentTheme.label}</span>
                        </Button>
                    }
                />
                <DropdownMenuContent align='start' side='top' sideOffset={8}>
                    {themes.map((t) => {
                        const Icon = t.icon;
                        const isSelected = theme === t.value;

                        return (
                            <DropdownMenuItem key={t.value} className='gap-2' onClick={() => setTheme(t.value)} data-selected={isSelected}>
                                <Icon className='h-4 w-4' />
                                <span>{t.label}</span>
                                {isSelected && <span className='ml-auto text-primary'>✓</span>}
                            </DropdownMenuItem>
                        );
                    })}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
```

### CenteredCardLayout Integration

```tsx
// resources/js/components/layouts/centered-card-layout.tsx
import { ReactNode } from 'react';
import { Card } from '@/components/ui/card';
import { SageLogo } from '@/components/branding/sage-logo';
import { ThemeToggler } from '@/components/theme-toggler';
import { cn } from '@/lib/utils';

interface CenteredCardLayoutProps {
    children: ReactNode;
    cardClassName?: string;
}

export function CenteredCardLayout({ children, cardClassName }: CenteredCardLayoutProps) {
    return (
        <div className='min-h-screen bg-muted flex flex-col items-center justify-center p-4 gap-5'>
            <div>
                <SageLogo />
            </div>

            <Card className={cn('w-full max-w-xl', cardClassName)}>{children}</Card>

            <div className='justify-center flex items-center'>
                <p className='text-xs text-muted-foreground font-semibold'>AI Agent Orchestrator for Laravel Application Development</p>
            </div>

            {/* Theme Toggler - Fixed Bottom Left */}
            <ThemeToggler />
        </div>
    );
}
```

### Alternative: Custom Dropdown Styling

If you want more control over dropdown appearance:

```tsx
<DropdownMenuContent align='start' side='top' sideOffset={8} className='min-w-36'>
    {themes.map((t) => {
        const Icon = t.icon;
        const isSelected = theme === t.value;

        return (
            <DropdownMenuItem key={t.value} className={cn('gap-3 cursor-pointer', isSelected && 'bg-accent')} onClick={() => setTheme(t.value)}>
                <Icon className={cn('h-4 w-4', isSelected && 'text-primary')} />
                <span className={cn('flex-1', isSelected && 'font-semibold')}>{t.label}</span>
                {isSelected && <CheckIcon className='h-4 w-4 text-primary' />}
            </DropdownMenuItem>
        );
    })}
</DropdownMenuContent>
```

## Acceptance Criteria

- [ ] useTheme hook is created and exported
- [ ] ThemeToggler component is created
- [ ] ThemeToggler is added to CenteredCardLayout
- [ ] Theme toggler appears fixed in bottom-left corner (left-4 bottom-4)
- [ ] Button uses "link" variant for subtle styling
- [ ] Clicking button opens dropdown menu
- [ ] Dropdown shows three options: System, Light, Dark
- [ ] Each option has appropriate icon (Monitor, Sun, Moon)
- [ ] Selected theme is visually indicated with checkmark
- [ ] Clicking an option changes the theme
- [ ] Theme preference is persisted to localStorage
- [ ] System theme respects OS preference
- [ ] Theme changes apply immediately to the page
- [ ] Dark mode works correctly with Tailwind CSS
- [ ] Theme toggler appears on project list page
- [ ] Theme toggler appears on project create page
- [ ] Theme toggler appears on project edit page
- [ ] Theme toggler appears on project show page
- [ ] Component uses Base UI render prop pattern (not asChild)
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location:** `tests/Browser/ThemeTogglerTest.php`

**Key test cases:**

- Test theme toggler is visible in bottom-left corner on project list page
- Test clicking theme toggler opens dropdown
- Test dropdown contains System, Light, and Dark options
- Test clicking Light theme applies light mode
- Test clicking Dark theme applies dark mode
- Test clicking System theme respects OS preference
- Test selected theme shows checkmark indicator
- Test theme preference persists after page reload
- Test theme toggler works on project create page
- Test theme toggler works on project edit page
- Test theme toggler works on project show page
- Test theme change updates document class (light/dark)
- Test localStorage contains correct theme value after selection

### Component Tests (Optional)

**Test file location:** `tests/Frontend/Components/ThemeTogglerTest.tsx` (if using Vitest)

**Key test cases:**

- Test useTheme hook returns correct initial theme
- Test setTheme updates theme state
- Test theme is persisted to localStorage
- Test system theme is resolved correctly
- Test theme change applies to document element

## Code Formatting

Format all code using: **oxfmt** (for JavaScript/TypeScript), **Prettier** (for React/TSX)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files

## Additional Notes

### localStorage Key

Use a namespaced key to avoid conflicts:

```tsx
const THEME_STORAGE_KEY = 'sage-theme';
```

### Initial Theme Application

To prevent flash of unstyled content (FOUC), you may want to add an inline script to the HTML head:

```blade
<!-- resources/views/app.blade.php -->
<head>
    <!-- ... -->
    <script>
        // Apply theme before page renders
        (function() {
            const theme = localStorage.getItem('sage-theme') || 'system';
            const resolved = theme === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : theme;
            document.documentElement.classList.add(resolved);
        })();
    </script>
</head>
```

This prevents the page from briefly showing the wrong theme before React hydrates.

### Tailwind CSS Dark Mode Configuration

Ensure Tailwind is configured for class-based dark mode:

```css
/* resources/css/app.css */
@import 'tailwindcss';

@theme {
    /* Your theme customizations */
}
```

Tailwind v4 uses class-based dark mode by default, so `dark:` utilities will work when the `dark` class is present on the document element.

### Base UI Render Prop Pattern

Base UI uses the `render` prop instead of Radix UI's `asChild`:

**Base UI (Correct for this project):**

```tsx
<DropdownMenuTrigger render={<Button variant='link'>Click me</Button>} />
```

**Radix UI (NOT used in this project):**

```tsx
<DropdownMenuTrigger asChild>
    <Button variant='link'>Click me</Button>
</DropdownMenuTrigger>
```

Always use the `render` prop pattern when composing Base UI components.

### Positioning Considerations

**Fixed Position:**

- `fixed left-4 bottom-4` places the toggler 1rem from left and bottom edges
- Adjust spacing if it conflicts with other fixed elements
- Consider using `left-6 bottom-6` for more breathing room

**Z-index:**

- Set `z-50` to ensure toggler appears above other content
- Dropdown menu inherits higher z-index from Base UI Positioner

### Icon Size and Spacing

**Consistent Icon Sizing:**

```tsx
<Icon className='h-4 w-4' />
```

**Spacing in Button:**

```tsx
<Button className='gap-2'>
    <Icon className='h-4 w-4' />
    <span>Label</span>
</Button>
```

**Spacing in Dropdown Items:**

```tsx
<DropdownMenuItem className='gap-2'>
    <Icon className='h-4 w-4' />
    <span>Option</span>
</DropdownMenuItem>
```

### Accessibility

**Keyboard Navigation:**

- Dropdown is keyboard accessible (Tab to focus, Enter/Space to open)
- Arrow keys navigate options
- Escape closes dropdown

**ARIA Attributes:**
Base UI handles ARIA attributes automatically:

- `aria-expanded` on trigger
- `role="menu"` on dropdown
- `role="menuitem"` on items

**Screen Reader Support:**

- Theme selection is announced
- Current selection is indicated
- Icons have proper context from text labels

### Theme Change Animation

Add smooth transition to theme changes:

```css
/* In your global CSS */
html {
    transition:
        background-color 0.2s ease,
        color 0.2s ease;
}
```

Or use Tailwind utilities:

```tsx
<div className='transition-colors duration-200'>{/* content */}</div>
```

### System Theme Change Detection

The `useTheme` hook listens for system theme changes using `matchMedia`:

```tsx
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', handler);
```

This ensures the theme updates automatically when the user changes their OS theme preference while the "System" option is selected.

### Alternative: React Context

For larger applications, consider using React Context to provide theme globally:

```tsx
// resources/js/contexts/theme-context.tsx
import { createContext, useContext } from 'react';
import { useTheme as useThemeHook } from '@/hooks/use-theme';

const ThemeContext = createContext<ReturnType<typeof useThemeHook> | null>(null);

export function ThemeProvider({ children }: { children: React.ReactNode }) {
    const theme = useThemeHook();
    return <ThemeContext.Provider value={theme}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within ThemeProvider');
    }
    return context;
}
```

However, for this feature, the simple hook approach is sufficient since theme state is localized to the toggler component.

### Mobile Responsiveness

On mobile devices, the fixed positioning might need adjustment:

```tsx
<div className='fixed left-4 bottom-4 md:left-6 md:bottom-6 z-50'>{/* ThemeToggler */}</div>
```

Or hide on very small screens:

```tsx
<div className='hidden sm:block fixed left-4 bottom-4 z-50'>{/* ThemeToggler */}</div>
```

However, the theme toggler is useful on mobile, so keeping it visible is recommended.

### Dependencies

This feature has no dependencies and can be implemented independently.

**Implementation order:**

- Can be implemented at any time
- Does not depend on other features
- Other features do not depend on this

**Pages that will automatically include the theme toggler:**

- Project list (`resources/js/pages/projects/index.tsx`)
- Project create (`resources/js/pages/projects/create.tsx`)
- Project edit (`resources/js/pages/projects/edit.tsx`)
- Project show (`resources/js/pages/projects/show.tsx`)
- Any other page using `CenteredCardLayout`
