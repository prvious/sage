import { Skeleton } from '@/components/ui/skeleton';

export function LoadingSkeleton() {
    return (
        <div className='space-y-2'>
            {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className='flex items-center gap-3 px-3 py-2'>
                    <Skeleton className='h-4 w-4 shrink-0' />
                    <Skeleton className='h-4 flex-1' />
                </div>
            ))}
        </div>
    );
}
