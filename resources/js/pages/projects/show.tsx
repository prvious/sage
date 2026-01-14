import { Head, Link, router } from '@inertiajs/react';
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ScrollArea } from '@/components/ui/scroll-area';
import { CenteredCardLayout } from '@/components/layouts/centered-card-layout';

interface Project {
    id: number;
    name: string;
    path: string;
    server_driver: 'caddy' | 'nginx';
    base_url: string;
    created_at: string;
    worktrees: Array<{
        id: number;
        branch_name: string;
        status: string;
    }>;
    tasks: Array<{
        id: number;
        title: string;
        status: string;
    }>;
    specs: Array<{
        id: number;
        title: string;
    }>;
}

interface Props {
    project: Project;
}

export default function Show({ project }: Props) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
            router.delete(`/projects/${project.id}`);
        }
    };

    return (
        <>
            <Head title={project.name} />

            <CenteredCardLayout cardClassName='max-w-4xl'>
                <CardHeader>
                    <div className='flex items-center justify-between'>
                        <div>
                            <CardTitle>{project.name}</CardTitle>
                            <CardDescription className='font-mono text-xs mt-1'>{project.path}</CardDescription>
                        </div>
                        <div className='flex gap-2'>
                            <Button variant='outline' size='sm' asChild>
                                <Link href={`/projects/${project.id}/edit`}>Edit</Link>
                            </Button>
                            <Button variant='destructive' size='sm' onClick={handleDelete}>
                                Delete
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <ScrollArea className='h-[500px] pr-4'>
                        <div className='space-y-6'>
                            {/* Project Details */}
                            <div className='space-y-4'>
                                <h3 className='text-lg font-semibold'>Project Details</h3>
                                <dl className='grid gap-3'>
                                    <div className='flex items-center justify-between'>
                                        <dt className='text-sm font-medium text-muted-foreground'>Server Driver</dt>
                                        <dd>
                                            <Badge variant='secondary' className='capitalize'>
                                                {project.server_driver}
                                            </Badge>
                                        </dd>
                                    </div>
                                    <div className='flex items-center justify-between'>
                                        <dt className='text-sm font-medium text-muted-foreground'>Base URL</dt>
                                        <dd className='text-sm font-mono'>{project.base_url}</dd>
                                    </div>
                                    <div className='flex items-center justify-between'>
                                        <dt className='text-sm font-medium text-muted-foreground'>Created</dt>
                                        <dd className='text-sm'>{new Date(project.created_at).toLocaleDateString()}</dd>
                                    </div>
                                </dl>
                            </div>

                            <Separator />

                            {/* Worktrees */}
                            <div className='space-y-4'>
                                <div className='flex items-center justify-between'>
                                    <h3 className='text-lg font-semibold'>Worktrees</h3>
                                    <span className='text-sm text-muted-foreground'>{project.worktrees.length} total</span>
                                </div>
                                {project.worktrees.length === 0 ? (
                                    <div className='text-center py-8 text-muted-foreground'>
                                        <p className='text-sm'>No worktrees yet. Create one to get started.</p>
                                    </div>
                                ) : (
                                    <ul className='space-y-2'>
                                        {project.worktrees.map((worktree) => (
                                            <li key={worktree.id} className='flex items-center justify-between p-3 rounded-lg bg-muted/50 hover:bg-muted'>
                                                <span className='text-sm font-medium'>{worktree.branch_name}</span>
                                                <Badge variant='outline' className='text-xs'>
                                                    {worktree.status}
                                                </Badge>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>

                            <Separator />

                            {/* Tasks */}
                            <div className='space-y-4'>
                                <div className='flex items-center justify-between'>
                                    <h3 className='text-lg font-semibold'>Tasks</h3>
                                    <span className='text-sm text-muted-foreground'>{project.tasks.length} total</span>
                                </div>
                                {project.tasks.length === 0 ? (
                                    <div className='text-center py-8 text-muted-foreground'>
                                        <p className='text-sm'>No tasks yet. Create one to get started.</p>
                                    </div>
                                ) : (
                                    <ul className='space-y-2'>
                                        {project.tasks.map((task) => (
                                            <li key={task.id} className='flex items-center justify-between p-3 rounded-lg bg-muted/50 hover:bg-muted'>
                                                <span className='text-sm font-medium'>{task.title}</span>
                                                <Badge variant='outline' className='text-xs'>
                                                    {task.status}
                                                </Badge>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>

                            {/* Specs */}
                            {project.specs && project.specs.length > 0 && (
                                <>
                                    <Separator />
                                    <div className='space-y-4'>
                                        <div className='flex items-center justify-between'>
                                            <h3 className='text-lg font-semibold'>Specs</h3>
                                            <span className='text-sm text-muted-foreground'>{project.specs.length} total</span>
                                        </div>
                                        <ul className='space-y-2'>
                                            {project.specs.map((spec) => (
                                                <li key={spec.id} className='p-3 rounded-lg bg-muted/50 hover:bg-muted'>
                                                    <span className='text-sm font-medium'>{spec.title}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </>
                            )}
                        </div>
                    </ScrollArea>
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
