import { AppLayout } from '@/components/layout/app-layout';
import { BrainstormList } from '@/components/brainstorm/brainstorm-list';
import { ContextInputForm } from '@/components/brainstorm/context-input-form';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Head, router } from '@inertiajs/react';
import { Sparkles, Info } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { usePrivateChannel } from '@/hooks/use-echo';

interface Project {
    id: number;
    name: string;
    path: string;
}

interface Brainstorm {
    id: number;
    project_id: number;
    user_context: string | null;
    ideas: any[] | null;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface BrainstormPageProps {
    project: Project;
    brainstorms: Brainstorm[];
}

export default function BrainstormPage({ project, brainstorms }: BrainstormPageProps) {
    const [hasActiveBrainstorm, setHasActiveBrainstorm] = useState(false);

    // Set up WebSocket listener for brainstorm events
    usePrivateChannel(`project.${project.id}.brainstorm`, (channel) => {
        channel
            .listen('.brainstorm.completed', (event: any) => {
                toast.success(event.message, {
                    description: `${event.ideas_count} ideas generated!`,
                    action: {
                        label: 'View',
                        onClick: () => router.visit(`/projects/${project.id}/brainstorm/${event.brainstorm_id}`),
                    },
                });

                // Reload brainstorms list
                router.reload({ only: ['brainstorms'] });
            })
            .listen('.brainstorm.failed', (event: any) => {
                toast.error('Failed to generate ideas', {
                    description: event.error,
                });

                // Reload brainstorms list
                router.reload({ only: ['brainstorms'] });
            });
    });

    useEffect(() => {
        // Check if there are any active brainstorms
        const active = brainstorms.some((b) => b.status === 'pending' || b.status === 'processing');
        setHasActiveBrainstorm(active);
    }, [brainstorms]);

    return (
        <>
            <Head title={`${project.name} - Brainstorm`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center gap-3'>
                        <Sparkles className='h-8 w-8 text-yellow-500' />
                        <div>
                            <h1 className='text-3xl font-bold'>{project.name}</h1>
                            <div className='flex items-center gap-2 mt-1'>
                                <Badge variant='secondary'>Brainstorm</Badge>
                            </div>
                        </div>
                    </div>

                    <Alert>
                        <Info className='h-4 w-4' />
                        <AlertDescription>
                            Provide optional context to help the AI generate relevant feature ideas for your project. Ideas are generated in the background and
                            you'll be notified when ready!
                        </AlertDescription>
                    </Alert>

                    {hasActiveBrainstorm && (
                        <Alert>
                            <Sparkles className='h-4 w-4 animate-pulse' />
                            <AlertDescription>A brainstorm session is currently processing. You'll receive a notification when it's complete!</AlertDescription>
                        </Alert>
                    )}

                    <Card className='p-6'>
                        <h2 className='text-xl font-semibold mb-4'>Create New Brainstorm Session</h2>
                        <ContextInputForm projectId={project.id} />
                    </Card>

                    <Separator />

                    <div>
                        <h2 className='text-2xl font-semibold mb-4'>Previous Sessions</h2>
                        <BrainstormList brainstorms={brainstorms} />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
