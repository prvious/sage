import { Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { History, ChevronRight } from 'lucide-react';

interface Brainstorm {
    id: number;
    created_at: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    ideas?: any[];
    error_message?: string;
}

interface BrainstormHistoryProps {
    brainstorms: Brainstorm[];
    projectId: number;
}

const statusColors = {
    pending: 'bg-gray-500',
    processing: 'bg-blue-500',
    completed: 'bg-green-500',
    failed: 'bg-red-500',
};

export function BrainstormHistory({ brainstorms, projectId }: BrainstormHistoryProps) {
    if (brainstorms.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className='flex items-center gap-2'>
                    <History className='h-5 w-5' />
                    Previous Sessions
                </CardTitle>
                <CardDescription>View your past brainstorming sessions</CardDescription>
            </CardHeader>
            <CardContent>
                <div className='space-y-3'>
                    {brainstorms.map((brainstorm) => (
                        <div key={brainstorm.id} className='flex items-center justify-between p-3 border rounded-lg hover:bg-accent transition-colors'>
                            <div className='flex items-center gap-3'>
                                <Badge className={statusColors[brainstorm.status]}>{brainstorm.status}</Badge>
                                <div>
                                    <p className='text-sm font-medium'>
                                        {new Date(brainstorm.created_at).toLocaleDateString()} at {new Date(brainstorm.created_at).toLocaleTimeString()}
                                    </p>
                                    {brainstorm.status === 'completed' && brainstorm.ideas && (
                                        <p className='text-xs text-muted-foreground'>{brainstorm.ideas.length} ideas generated</p>
                                    )}
                                    {brainstorm.status === 'failed' && <p className='text-xs text-destructive'>{brainstorm.error_message}</p>}
                                </div>
                            </div>
                            {brainstorm.status === 'completed' && (
                                <Button variant='ghost' size='sm' render={<Link href={`/projects/${projectId}/brainstorm/${brainstorm.id}`}>View Ideas</Link>}>
                                    <ChevronRight className='h-4 w-4' />
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
