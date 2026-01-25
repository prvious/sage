import { AddFeatureDialog } from '@/components/feature/add-feature-dialog';
import { AppLayout } from '@/components/layout/app-layout';
import { KanbanBoard } from '@/components/kanban/board';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Task } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface Project {
    id: number;
    name: string;
    path: string;
    base_url: string;
}

interface DashboardProps {
    project: {
        data: Project;
    };
    tasks: {
        queued: { data: Task[] };
        in_progress: { data: Task[] };
        waiting_review: { data: Task[] };
        done: { data: Task[] };
    };
}

interface FeatureGeneratedEvent {
    project_id: number;
    feature_id: number;
    task_count: number;
    message: string;
}

interface FeatureGenerationFailedEvent {
    project_id: number;
    error: string;
    description: string;
}

export default function Dashboard({ project, tasks }: DashboardProps) {
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    // Listen for feature generation completion
    useEcho<FeatureGeneratedEvent>(`project.${project.data.id}.features`, 'feature.generated', (event) => {
        toast.success('Feature created!', {
            description: `${event.task_count} ${event.task_count === 1 ? 'task' : 'tasks'} added to your board`,
        });
        router.reload({ only: ['tasks'] });
    });

    // Listen for feature generation failures
    useEcho<FeatureGenerationFailedEvent>(`project.${project.data.id}.features`, 'feature.generation.failed', (event) => {
        toast.error('Feature generation failed', {
            description: event.error,
        });
    });

    return (
        <>
            <Head title={`${project.data.name} - Dashboard`} />
            <AppLayout>
                <div className='flex flex-col h-screen'>
                    {/* Fixed header */}
                    <div className='flex shrink-0 p-6 pb-0'>
                        <div className='flex w-full items-center justify-between'>
                            <div className='flex items-center gap-3'>
                                <h1 className='text-3xl font-bold'>{project.data.name}</h1>
                                <Badge variant='secondary'>Dashboard</Badge>
                            </div>
                            <Button onClick={() => setIsDialogOpen(true)} size='lg' className='gap-2'>
                                <Plus className='h-5 w-5' />
                                Add Feature
                            </Button>
                        </div>
                    </div>

                    {/* Scrollable kanban board */}
                    <div className='flex-1 overflow-hidden p-6 pt-6'>
                        <KanbanBoard
                            tasks={{
                                queued: tasks.queued.data,
                                in_progress: tasks.in_progress.data,
                                waiting_review: tasks.waiting_review.data,
                                done: tasks.done.data,
                            }}
                            projectId={project.data.id}
                        />
                    </div>
                    <AddFeatureDialog open={isDialogOpen} onOpenChange={setIsDialogOpen} projectId={project.data.id} />
                </div>
            </AppLayout>
        </>
    );
}
