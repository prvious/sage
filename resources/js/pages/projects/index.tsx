import { Head, Link } from '@inertiajs/react';

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

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8 flex items-center justify-between'>
                        <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>Projects</h1>
                        <Link href='/projects/create' className='rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700'>
                            Add Project
                        </Link>
                    </div>

                    {projects.length === 0 ? (
                        <div className='rounded-lg border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-gray-800'>
                            <p className='text-gray-600 dark:text-gray-400'>No projects yet. Add your first Laravel project to get started.</p>
                        </div>
                    ) : (
                        <div className='grid gap-6 sm:grid-cols-2 lg:grid-cols-3'>
                            {projects.map((project) => (
                                <Link
                                    key={project.id}
                                    href={`/projects/${project.id}`}
                                    className='block rounded-lg border border-gray-200 bg-white p-6 transition hover:border-blue-500 dark:border-gray-700 dark:bg-gray-800'
                                >
                                    <h3 className='mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100'>{project.name}</h3>
                                    <p className='mb-4 text-sm text-gray-600 dark:text-gray-400'>{project.path}</p>
                                    <div className='flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400'>
                                        <span>{project.worktrees_count} worktrees</span>
                                        <span>•</span>
                                        <span>{project.tasks_count} tasks</span>
                                    </div>
                                    <div className='mt-4 inline-flex rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300'>
                                        {project.server_driver}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
