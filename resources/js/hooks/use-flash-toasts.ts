import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types';

/**
 * Hook to automatically display flash toasts from server-side flash data.
 *
 * This hook monitors the Inertia flash data and automatically displays
 * toasts when the server sends flash notifications via Inertia::flash('toasts', [...]).
 *
 * Usage:
 * Add this hook to your layout component to enable automatic toast handling.
 *
 * @example
 * ```tsx
 * export function AppLayout({ children }: AppLayoutProps) {
 *   useFlashToasts();
 *   return <div>{children}</div>;
 * }
 * ```
 */
export function useFlashToasts() {
    const { flash } = usePage<{ flash?: { toasts?: FlashToast[] } }>().props;

    useEffect(() => {
        const toasts = flash?.toasts;

        if (toasts && Array.isArray(toasts)) {
            toasts.forEach((toastData) => {
                const toastFn = toast[toastData.type] || toast;
                toastFn(toastData.message, {
                    description: toastData.description,
                    duration: toastData.duration || 4000,
                });
            });
        }
    }, [flash]);
}
