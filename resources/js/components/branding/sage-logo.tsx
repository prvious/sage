import { Link } from '@inertiajs/react';
import { home } from '@/routes';

export function SageLogo() {
    return (
        <Link
            href={home()}
            className='flex items-center gap-2 no-underline hover:opacity-80 transition-opacity focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded'
            aria-label='Sage - Go to home page'
        >
            <h1 className='text-4xl font-bold tracking-tight'>Sage</h1>
            <div className='size-2 rounded-full bg-primary animate-pulse' />
        </Link>
    );
}
