import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2 } from 'lucide-react';

export function LoadingState() {
    return (
        <div className='space-y-4'>
            <Alert>
                <Loader2 className='h-4 w-4 animate-spin' />
                <AlertDescription>Generating ideas... This may take a minute. You can navigate away and we'll notify you when ready!</AlertDescription>
            </Alert>
            <Card>
                <CardContent className='pt-6 space-y-4'>
                    <Skeleton className='h-8 w-3/4' />
                    <Skeleton className='h-20 w-full' />
                </CardContent>
            </Card>
            <Card>
                <CardContent className='pt-6 space-y-4'>
                    <Skeleton className='h-8 w-2/3' />
                    <Skeleton className='h-20 w-full' />
                </CardContent>
            </Card>
        </div>
    );
}
