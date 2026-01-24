import { AppLayout } from '@/components/layout/app-layout';
import { SpecToTaskDialog } from '@/components/spec-to-task-dialog';
import { Button } from '@/components/ui/button';
import { Spec } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, Play, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Project {
    id: number;
    name: string;
    path: string;
}

interface ShowProps {
    project: Project;
    spec: Spec;
}

export default function Show({ project, spec }: ShowProps) {
    const [createTaskDialogOpen, setCreateTaskDialogOpen] = useState(false);

    return (
        <>
            <Head title={`${project.name} - ${spec.title}`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-start justify-between'>
                        <div className='space-y-1'>
                            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
                                <Link href={`/projects/${project.id}/specs`} className='hover:text-foreground flex items-center gap-1'>
                                    <ArrowLeft className='h-4 w-4' />
                                    Back to Specs
                                </Link>
                            </div>
                            <h1 className='text-3xl font-bold'>{spec.title}</h1>
                            <p className='text-muted-foreground'>Project: {project.name}</p>
                        </div>
                        <div className='flex items-center gap-2'>
                            <Button
                                variant='outline'
                                size='sm'
                                render={
                                    <Link href={`/projects/${project.id}/specs/${spec.id}/edit`}>
                                        <Edit className='h-4 w-4 mr-2' />
                                        Edit
                                    </Link>
                                }
                            />
                            <Button size='sm' onClick={() => setCreateTaskDialogOpen(true)}>
                                <Play className='h-4 w-4 mr-2' />
                                Create Task
                            </Button>
                        </div>
                    </div>

                    <div className='bg-card border rounded-lg p-6'>
                        <h2 className='text-lg font-semibold mb-4'>Specification</h2>
                        <div className='prose prose-sm dark:prose-invert max-w-none'>
                            <pre className='whitespace-pre-wrap text-sm font-sans bg-muted/50 p-4 rounded-lg'>{spec.content}</pre>
                        </div>
                    </div>

                    {spec.generated_from_idea && (
                        <div className='bg-card border rounded-lg p-6'>
                            <h2 className='text-lg font-semibold mb-4'>Original Idea</h2>
                            <p className='text-muted-foreground'>{spec.generated_from_idea}</p>
                        </div>
                    )}

                    <div className='flex items-center justify-between text-sm text-muted-foreground'>
                        <div>
                            Created: {new Date(spec.created_at).toLocaleDateString()}
                            {spec.updated_at !== spec.created_at && <span className='ml-4'>Updated: {new Date(spec.updated_at).toLocaleDateString()}</span>}
                        </div>
                        <Link
                            href={`/projects/${project.id}/specs/${spec.id}`}
                            method='delete'
                            as='button'
                            className='text-destructive hover:text-destructive/80 flex items-center gap-1'
                        >
                            <Trash2 className='h-4 w-4' />
                            Delete Spec
                        </Link>
                    </div>
                </div>
            </AppLayout>

            <SpecToTaskDialog spec={spec} project={project} open={createTaskDialogOpen} onOpenChange={setCreateTaskDialogOpen} />
        </>
    );
}
