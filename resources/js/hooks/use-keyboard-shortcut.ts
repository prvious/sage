import { useCallback, useEffect } from 'react';

type KeyModifier = 'meta' | 'ctrl' | 'alt' | 'shift';

interface KeyboardShortcutOptions {
    key: string;
    modifiers?: KeyModifier[];
    callback: () => void;
    enabled?: boolean;
}

/**
 * Hook to handle global keyboard shortcuts.
 * Supports modifier keys (Cmd/Ctrl, Alt, Shift) and prevents default browser behavior.
 */
export function useKeyboardShortcut({ key, modifiers = [], callback, enabled = true }: KeyboardShortcutOptions) {
    const handleKeyDown = useCallback(
        (event: KeyboardEvent) => {
            if (!enabled) {
                return;
            }

            // Check if the target is an input, textarea, or contenteditable element
            const target = event.target as HTMLElement;
            const isInputElement = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable;

            // Allow the shortcut even in inputs if it has modifiers (power user feature)
            const hasModifiers = modifiers.length > 0;
            if (isInputElement && !hasModifiers) {
                return;
            }

            // Check if the pressed key matches
            if (event.key.toLowerCase() !== key.toLowerCase()) {
                return;
            }

            // Check modifier keys
            const modifierChecks: Record<KeyModifier, boolean> = {
                meta: event.metaKey,
                ctrl: event.ctrlKey,
                alt: event.altKey,
                shift: event.shiftKey,
            };

            // All specified modifiers must be pressed
            const allModifiersPressed = modifiers.every((mod) => modifierChecks[mod]);

            // No extra modifiers should be pressed (except the ones specified)
            const noExtraModifiers = (['meta', 'ctrl', 'alt', 'shift'] as KeyModifier[]).every((mod) => {
                if (modifiers.includes(mod)) {
                    return modifierChecks[mod]; // Must be pressed
                }
                return !modifierChecks[mod]; // Must not be pressed
            });

            if (allModifiersPressed && noExtraModifiers) {
                event.preventDefault();
                callback();
            }
        },
        [key, modifiers, callback, enabled],
    );

    useEffect(() => {
        if (!enabled) {
            return;
        }

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [handleKeyDown, enabled]);
}
