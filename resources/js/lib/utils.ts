import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function debounce<T extends (...args: any[]) => any>(func: T, wait: number = 300): (...args: Parameters<T>) => ReturnType<T> {
    let timeout: ReturnType<typeof setTimeout> | null = null;

    return function (this: any, ...args: Parameters<T>): ReturnType<T> {
        const context = this;

        if (timeout !== null) {
            clearTimeout(timeout);
        }

        let result: ReturnType<T>;

        timeout = setTimeout(() => {
            result = func.apply(context, args);
            timeout = null;
        }, wait);

        return result!;
    };
}
