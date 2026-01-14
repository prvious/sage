import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface Worktree {
    id: number;
    name: string;
    path: string;
    type: string;
    env_path: string;
    project_id: number;
}

interface Project {
    id: number;
    name: string;
    path: string;
    type: string;
    env_path: string;
    worktrees: Worktree[];
}

interface Props {
    projects: Project[];
}

export default function Index({ projects }: Props) {
    return (
        <>
            <Head title='Environment Manager' />

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8'>
                        <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>Environment Manager</h1>
                        <p className='mt-2 text-gray-600 dark:text-gray-400'>Manage environment variables across your projects and worktrees</p>
                    </div>

                    {projects.length === 0 ? (
                        <Card>
                            <CardContent className='p-12 text-center'>
                                <p className='text-gray-600 dark:text-gray-400'>No projects found. Add a project first to manage environment variables.</p>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className='space-y-6'>
                            {projects.map((project) => (
                                <Card key={project.id}>
                                    <CardHeader>
                                        <div className='flex items-center justify-between'>
                                            <div className='flex items-center gap-3'>
                                                <CardTitle>{project.name}</CardTitle>
                                                <Badge variant='secondary'>Project</Badge>
                                            </div>
                                            <Link
                                                href={`/environment/project/${project.id}`}
                                                className='rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700'
                                            >
                                                View .env
                                            </Link>
                                        </div>
                                        <p className='text-sm text-gray-600 dark:text-gray-400'>{project.path}</p>
                                    </CardHeader>

                                    {project.worktrees.length > 0 && (
                                        <CardContent>
                                            <h4 className='mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300'>Worktrees</h4>
                                            <div className='grid gap-3 sm:grid-cols-2 lg:grid-cols-3'>
                                                {project.worktrees.map((worktree) => (
                                                    <Link
                                                        key={worktree.id}
                                                        href={`/environment/worktree/${worktree.id}`}
                                                        className='block rounded-lg border border-gray-200 bg-gray-50 p-4 transition hover:border-blue-500 dark:border-gray-700 dark:bg-gray-800'
                                                    >
                                                        <div className='mb-2 flex items-center justify-between'>
                                                            <h5 className='font-medium text-gray-900 dark:text-gray-100'>{worktree.name}</h5>
                                                            <Badge variant='outline' className='text-xs'>
                                                                Worktree
                                                            </Badge>
                                                        </div>
                                                        <p className='text-xs text-gray-600 dark:text-gray-400'>{worktree.path}</p>
                                                    </Link>
                                                ))}
                                            </div>
                                        </CardContent>
                                    )}
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
