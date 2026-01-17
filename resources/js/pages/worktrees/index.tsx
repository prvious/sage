import { Head, Link, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ExternalLink, FolderIcon, GitBranch } from 'lucide-react';
import { create } from '@/actions/App/Http/Controllers/WorktreeController';

interface Project {
    id: number;
    name: string;
    path: string;
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
    worktrees: Worktree[];
}

const statusVariant = {
    creating: 'secondary' as const,
    active: 'default' as const,
    error: 'destructive' as const,
    cleaning_up: 'secondary' as const,
};

export default function Index({ project, worktrees }: Props) {
    return (
        <>
            <Head title={`Worktrees - ${project.name}`} />

            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center justify-between'>
                        <h1 className='text-3xl font-bold'>Worktrees</h1>
                        <Button onClick={() => router.visit(create(project.id).url)}>Create Worktree</Button>
                    </div>

                    {worktrees.length === 0 ? (
                        <div className='flex min-h-96 flex-col items-center justify-center text-center'>
                            <div className='rounded-full bg-muted p-6'>
                                <GitBranch className='h-12 w-12 text-muted-foreground' />
                            </div>
                            <h3 className='mt-6 text-xl font-semibold'>No worktrees yet</h3>
                            <p className='text-muted-foreground mt-2 max-w-sm'>
                                Create your first worktree to work on a feature branch with its own preview URL.
                            </p>
                            <Button className='mt-4' onClick={() => router.visit(create(project.id).url)}>
                                Create Your First Worktree
                            </Button>
                        </div>
                    ) : (
                        <div className='grid gap-6 sm:grid-cols-2 lg:grid-cols-3'>
                            {worktrees.map((worktree) => (
                                <Link key={worktree.id} href={`/projects/${project.id}/worktrees/${worktree.id}`}>
                                    <Card className='transition-all hover:shadow-lg hover:border-primary h-full'>
                                        <CardHeader>
                                            <div className='flex items-center justify-between gap-2'>
                                                <CardTitle className='text-lg truncate'>{worktree.branch_name}</CardTitle>
                                                <Badge variant={statusVariant[worktree.status]} className='shrink-0'>
                                                    {worktree.status}
                                                </Badge>
                                            </div>
                                        </CardHeader>
                                        <CardContent className='space-y-3'>
                                            <div className='flex items-center gap-2 text-sm text-muted-foreground font-mono'>
                                                <FolderIcon className='h-4 w-4 shrink-0' />
                                                <span className='truncate' title={worktree.path}>
                                                    {worktree.path}
                                                </span>
                                            </div>
                                            <div className='flex items-center gap-2'>
                                                <a
                                                    href={worktree.preview_url}
                                                    target='_blank'
                                                    rel='noopener noreferrer'
                                                    onClick={(e) => e.stopPropagation()}
                                                    className='text-sm text-primary hover:underline flex items-center gap-1 truncate'
                                                    title={worktree.preview_url}
                                                >
                                                    <span className='truncate'>{worktree.preview_url}</span>
                                                    <ExternalLink className='h-3 w-3 shrink-0' />
                                                </a>
                                            </div>
                                            <Badge variant='secondary' className='text-xs'>
                                                {worktree.database_isolation} DB
                                            </Badge>
                                            {worktree.error_message && (
                                                <p className='text-sm text-destructive line-clamp-2' title={worktree.error_message}>
                                                    {worktree.error_message}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </AppLayout>
        </>
    );
}
