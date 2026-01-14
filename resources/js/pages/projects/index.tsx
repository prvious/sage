import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Project {
    id: number;
    name: string;
    path: string;
    server_driver: 'caddy' | 'nginx';
    base_url: string;
    worktrees_count: number;
    tasks_count: number;
    created_at: string;
}

interface Props {
    projects: Project[];
}

export default function Index({ projects }: Props) {
    return (
        <>
            <Head title='Projects' />

            <div className='min-h-screen bg-gradient-to-br from-gray-50 via-gray-50 to-gray-100 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950'>
                <div className='mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8'>
                    {/* Header Section */}
                    <div className='mb-12'>
                        <div className='flex items-start justify-between'>
                            <div className='space-y-2'>
                                <h1 className='text-4xl font-bold tracking-tight text-gray-900 dark:text-white'>Projects</h1>
                                <p className='text-muted-foreground max-w-2xl text-lg'>Manage your Laravel projects, worktrees, and development environments</p>
                            </div>
                            <Button asChild size='lg' className='shadow-lg'>
                                <Link href='/projects/create'>
                                    <svg className='mr-2 h-4 w-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                        <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M12 4v16m8-8H4' />
                                    </svg>
                                    New Project
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Projects Grid */}
                    {projects.length === 0 ? (
                        <Card className='border-2 border-dashed'>
                            <CardContent className='flex min-h-[400px] flex-col items-center justify-center p-12 text-center'>
                                <div className='mb-4 rounded-full bg-gray-100 p-6 dark:bg-gray-800'>
                                    <svg className='h-12 w-12 text-gray-400 dark:text-gray-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                        <path
                                            strokeLinecap='round'
                                            strokeLinejoin='round'
                                            strokeWidth={1.5}
                                            d='M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'
                                        />
                                    </svg>
                                </div>
                                <h3 className='mb-2 text-xl font-semibold text-gray-900 dark:text-white'>No projects yet</h3>
                                <p className='text-muted-foreground mb-6 max-w-sm'>
                                    Get started by adding your first Laravel project to manage worktrees and deployments
                                </p>
                                <Button render={<Link href='/projects/create'>Add Your First Project</Link>} />
                            </CardContent>
                        </Card>
                    ) : (
                        <div className='grid gap-6 sm:grid-cols-2 lg:grid-cols-3'>
                            {projects.map((project) => (
                                <Link key={project.id} href={`/projects/${project.id}`} className='group'>
                                    <Card className='h-full transition-all duration-200 hover:shadow-xl hover:-translate-y-1'>
                                        <CardHeader className='pb-4'>
                                            <div className='flex items-start justify-between'>
                                                <div className='flex-1'>
                                                    <CardTitle className='mb-2 text-xl font-bold group-hover:text-primary transition-colors'>
                                                        {project.name}
                                                    </CardTitle>
                                                    <CardDescription className='line-clamp-1 text-sm font-mono'>{project.path}</CardDescription>
                                                </div>
                                                <Badge variant='secondary' className='ml-2 shrink-0 capitalize'>
                                                    {project.server_driver}
                                                </Badge>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <div className='space-y-4'>
                                                {/* Stats */}
                                                <div className='grid grid-cols-2 gap-4'>
                                                    <div className='rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50'>
                                                        <div className='text-2xl font-bold text-gray-900 dark:text-white'>{project.worktrees_count}</div>
                                                        <div className='text-xs text-muted-foreground'>Worktrees</div>
                                                    </div>
                                                    <div className='rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50'>
                                                        <div className='text-2xl font-bold text-gray-900 dark:text-white'>{project.tasks_count}</div>
                                                        <div className='text-xs text-muted-foreground'>Tasks</div>
                                                    </div>
                                                </div>

                                                {/* Base URL */}
                                                {project.base_url && (
                                                    <div className='flex items-center gap-2 text-sm'>
                                                        <svg
                                                            className='h-4 w-4 shrink-0 text-muted-foreground'
                                                            fill='none'
                                                            stroke='currentColor'
                                                            viewBox='0 0 24 24'
                                                        >
                                                            <path
                                                                strokeLinecap='round'
                                                                strokeLinejoin='round'
                                                                strokeWidth={2}
                                                                d='M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'
                                                            />
                                                        </svg>
                                                        <span className='text-muted-foreground truncate font-mono text-xs'>{project.base_url}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
