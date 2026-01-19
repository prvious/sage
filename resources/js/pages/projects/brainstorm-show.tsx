import { AppLayout } from '@/components/layout/app-layout';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock, AlertCircle, Loader2, Lightbulb } from 'lucide-react';
import { index } from '@/actions/App/Http/Controllers/BrainstormController';

interface Project {
    id: number;
    name: string;
    path: string;
}

interface Brainstorm {
    id: number;
    project_id: number;
    user_context: string | null;
    ideas: Array<{
        title: string;
        description: string;
        priority?: string;
        category?: string;
    }> | null;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface BrainstormShowProps {
    project: Project;
    brainstorm: Brainstorm;
}

const statusConfig = {
    pending: {
        icon: Clock,
        variant: 'secondary' as const,
        label: 'Pending',
        description: 'This brainstorm session is waiting to be processed.',
    },
    processing: {
        icon: Loader2,
        variant: 'default' as const,
        label: 'Processing',
        description: 'AI is generating ideas for this brainstorm session.',
    },
    completed: {
        icon: CheckCircle2,
        variant: 'success' as const,
        label: 'Completed',
        description: 'Ideas have been successfully generated.',
    },
    failed: {
        icon: AlertCircle,
        variant: 'destructive' as const,
        label: 'Failed',
        description: 'An error occurred while processing this brainstorm.',
    },
};

export default function BrainstormShow({ project, brainstorm }: BrainstormShowProps) {
    const config = statusConfig[brainstorm.status];
    const StatusIcon = config.icon;
    const createdDate = new Date(brainstorm.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

    return (
        <>
            <Head title={`${project.name} - Brainstorm Session`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center gap-4'>
                        <Link href={index.url(project.id)}>
                            <Button variant='ghost' size='icon'>
                                <ArrowLeft className='h-4 w-4' />
                            </Button>
                        </Link>
                        <div>
                            <h1 className='text-3xl font-bold'>{project.name}</h1>
                            <p className='text-sm text-muted-foreground mt-1'>Brainstorm Session - {createdDate}</p>
                        </div>
                    </div>

                    <Card className='p-6'>
                        <div className='space-y-6'>
                            <div className='flex items-center justify-between'>
                                <h2 className='text-xl font-semibold'>Status</h2>
                                <Badge variant={config.variant} className='flex items-center gap-1'>
                                    <StatusIcon className={`h-3 w-3 ${brainstorm.status === 'processing' ? 'animate-spin' : ''}`} />
                                    {config.label}
                                </Badge>
                            </div>
                            <p className='text-sm text-muted-foreground'>{config.description}</p>

                            {brainstorm.user_context && (
                                <div>
                                    <h3 className='text-sm font-medium mb-2'>Context</h3>
                                    <p className='text-sm text-muted-foreground whitespace-pre-wrap'>{brainstorm.user_context}</p>
                                </div>
                            )}

                            {brainstorm.status === 'failed' && brainstorm.error_message && (
                                <div className='p-4 border border-red-200 bg-red-50 dark:bg-red-950 dark:border-red-800 rounded-md'>
                                    <h3 className='text-sm font-medium text-red-800 dark:text-red-200 mb-1'>Error</h3>
                                    <p className='text-sm text-red-600 dark:text-red-300'>{brainstorm.error_message}</p>
                                </div>
                            )}
                        </div>
                    </Card>

                    {brainstorm.status === 'completed' && brainstorm.ideas && brainstorm.ideas.length > 0 && (
                        <div>
                            <h2 className='text-2xl font-semibold mb-4'>Generated Ideas</h2>
                            <div className='grid gap-4 md:grid-cols-2'>
                                {brainstorm.ideas.map((idea, index) => (
                                    <Card key={index} className='p-4'>
                                        <div className='space-y-3'>
                                            <div className='flex items-start gap-3'>
                                                <Lightbulb className='h-5 w-5 text-yellow-500 flex-shrink-0 mt-0.5' />
                                                <div className='flex-1'>
                                                    <h3 className='font-semibold'>{idea.title}</h3>
                                                    <p className='text-sm text-muted-foreground mt-1'>{idea.description}</p>
                                                </div>
                                            </div>
                                            {(idea.priority || idea.category) && (
                                                <div className='flex gap-2'>
                                                    {idea.category && <Badge variant='outline'>{idea.category}</Badge>}
                                                    {idea.priority && (
                                                        <Badge
                                                            variant={
                                                                idea.priority === 'high' ? 'destructive' : idea.priority === 'medium' ? 'default' : 'secondary'
                                                            }
                                                        >
                                                            {idea.priority}
                                                        </Badge>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    )}

                    {brainstorm.status === 'pending' && (
                        <Card className='p-6 text-center'>
                            <Clock className='h-12 w-12 mx-auto text-muted-foreground mb-4' />
                            <h3 className='text-lg font-semibold mb-2'>Waiting to Process</h3>
                            <p className='text-sm text-muted-foreground'>
                                This brainstorm session will be processed soon. Check back later for generated ideas.
                            </p>
                        </Card>
                    )}

                    {brainstorm.status === 'processing' && (
                        <Card className='p-6 text-center'>
                            <Loader2 className='h-12 w-12 mx-auto text-primary mb-4 animate-spin' />
                            <h3 className='text-lg font-semibold mb-2'>Generating Ideas...</h3>
                            <p className='text-sm text-muted-foreground'>AI is working on generating creative ideas for you.</p>
                        </Card>
                    )}
                </div>
            </AppLayout>
        </>
    );
}
