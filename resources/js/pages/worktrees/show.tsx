import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ExternalLink, AlertCircle, Info } from 'lucide-react';

interface Project {
    id: number;
    name: string;
}

interface Worktree {
    id: number;
    branch_name: string;
    path: string;
    preview_url: string;
    status: 'creating' | 'active' | 'error' | 'cleaning_up';
    database_isolation: 'separate' | 'prefix' | 'shared';
    error_message?: string;
    created_at: string;
}

interface Props {
    project: Project;
    worktree: Worktree;
}

const statusVariant = {
    creating: 'secondary' as const,
    active: 'default' as const,
    error: 'destructive' as const,
    cleaning_up: 'secondary' as const,
};

export default function Show({ project, worktree }: Props) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this worktree? This action cannot be undone.')) {
            router.delete(`/projects/${project.id}/worktrees/${worktree.id}`);
        }
    };

    const handleOpenPreview = () => {
        window.open(worktree.preview_url, '_blank');
    };

    return (
        <>
            <Head title={`${worktree.branch_name} - ${project.name}`} />

            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center justify-between'>
                        <div className='flex items-center gap-3'>
                            <h1 className='text-3xl font-bold'>{worktree.branch_name}</h1>
                            <Badge variant={statusVariant[worktree.status]}>{worktree.status}</Badge>
                        </div>
                        <div className='flex gap-3'>
                            {worktree.status === 'active' && (
                                <Button onClick={handleOpenPreview}>
                                    <ExternalLink className='h-4 w-4 mr-2' />
                                    Open Preview
                                </Button>
                            )}
                            <Button variant='destructive' onClick={handleDelete}>
                                Delete Worktree
                            </Button>
                        </div>
                    </div>

                    {worktree.status === 'creating' && (
                        <Alert>
                            <Info className='h-4 w-4' />
                            <AlertDescription>This worktree is being set up. You'll be notified when it's ready.</AlertDescription>
                        </Alert>
                    )}

                    {worktree.error_message && (
                        <Alert variant='destructive'>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>{worktree.error_message}</AlertDescription>
                        </Alert>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Worktree Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className='space-y-4'>
                                <div>
                                    <dt className='text-sm font-medium text-muted-foreground'>Branch Name</dt>
                                    <dd className='mt-1 text-sm'>{worktree.branch_name}</dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-muted-foreground'>Preview URL</dt>
                                    <dd className='mt-1 flex items-center gap-2'>
                                        <a
                                            href={worktree.preview_url}
                                            target='_blank'
                                            rel='noopener noreferrer'
                                            className='text-sm text-primary hover:underline flex items-center gap-1'
                                        >
                                            {worktree.preview_url}
                                            <ExternalLink className='h-3 w-3' />
                                        </a>
                                    </dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-muted-foreground'>Path</dt>
                                    <dd className='mt-1 font-mono text-sm'>{worktree.path}</dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-muted-foreground'>Database Isolation</dt>
                                    <dd className='mt-1 text-sm'>
                                        <Badge variant='secondary'>{worktree.database_isolation}</Badge>
                                    </dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-muted-foreground'>Status</dt>
                                    <dd className='mt-1 text-sm'>
                                        <Badge variant={statusVariant[worktree.status]}>{worktree.status}</Badge>
                                    </dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-muted-foreground'>Created At</dt>
                                    <dd className='mt-1 text-sm'>{new Date(worktree.created_at).toLocaleString()}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        </>
    );
}
