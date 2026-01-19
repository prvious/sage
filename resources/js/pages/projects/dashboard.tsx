import { AppLayout } from '@/components/layout/app-layout';
import { KanbanBoard } from '@/components/kanban/board';
import { QuickAddTaskDialog } from '@/components/kanban/quick-add-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Task } from '@/types';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

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

export default function Dashboard({ project, tasks }: DashboardProps) {
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    return (
        <>
            <Head title={`${project.data.name} - Dashboard`} />
            <AppLayout>
                <div className='flex flex-col h-screen'>
                    {/* Fixed header */}
                    <div className='flex shrink-0 p-6 pb-0'>
                        <div className='flex items-center justify-between'>
                            <div className='flex items-center gap-3'>
                                <h1 className='text-3xl font-bold'>{project.data.name}</h1>
                                <Badge variant='secondary'>Dashboard</Badge>
                            </div>
                            <Button onClick={() => setIsDialogOpen(true)}>
                                <Plus className='h-4 w-4 mr-2' />
                                Add Task
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
                    <QuickAddTaskDialog open={isDialogOpen} onOpenChange={setIsDialogOpen} projectId={project.data.id} />
                </div>
            </AppLayout>
        </>
    );
}
