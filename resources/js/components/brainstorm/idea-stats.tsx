import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface IdeaStatsProps {
    total: number;
    high: number;
    medium: number;
    low: number;
    byCategory: Record<string, number>;
}

export function IdeaStats({ total, high, medium, low, byCategory }: IdeaStatsProps) {
    return (
        <Card>
            <CardContent className='pt-6'>
                <div className='grid grid-cols-2 md:grid-cols-4 gap-4'>
                    <div>
                        <p className='text-sm text-muted-foreground'>Total Ideas</p>
                        <p className='text-2xl font-bold'>{total}</p>
                    </div>
                    <div>
                        <p className='text-sm text-muted-foreground'>High Priority</p>
                        <p className='text-2xl font-bold text-red-500'>{high}</p>
                    </div>
                    <div>
                        <p className='text-sm text-muted-foreground'>Medium Priority</p>
                        <p className='text-2xl font-bold text-yellow-500'>{medium}</p>
                    </div>
                    <div>
                        <p className='text-sm text-muted-foreground'>Low Priority</p>
                        <p className='text-2xl font-bold text-gray-500'>{low}</p>
                    </div>
                </div>
                <div className='mt-6'>
                    <p className='text-sm text-muted-foreground mb-2'>By Category</p>
                    <div className='flex flex-wrap gap-2'>
                        {Object.entries(byCategory).map(([category, count]) => (
                            <Badge key={category} variant='outline'>
                                {category}: {count}
                            </Badge>
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
