import { Head, Link, router } from '@inertiajs/react';

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

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8 flex items-center justify-between'>
                        <div>
                            <Link href='/projects' className='mb-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400'>
                                ← Back to Projects
                            </Link>
                            <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>{project.name}</h1>
                        </div>
                        <div className='flex gap-2'>
                            <Link
                                href={`/projects/${project.id}/edit`}
                                className='rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700'
                            >
                                Edit
                            </Link>
                            <button onClick={handleDelete} className='rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700'>
                                Delete
                            </button>
                        </div>
                    </div>

                    <div className='grid gap-6'>
                        <div className='rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800'>
                            <h2 className='mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100'>Project Details</h2>
                            <dl className='grid gap-4'>
                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Path</dt>
                                    <dd className='mt-1 text-sm text-gray-900 dark:text-gray-100'>{project.path}</dd>
                                </div>
                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Server Driver</dt>
                                    <dd className='mt-1 text-sm text-gray-900 dark:text-gray-100'>{project.server_driver}</dd>
                                </div>
                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Base URL</dt>
                                    <dd className='mt-1 text-sm text-gray-900 dark:text-gray-100'>{project.base_url}</dd>
                                </div>
                            </dl>
                        </div>

                        <div className='rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800'>
                            <h2 className='mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100'>Worktrees ({project.worktrees.length})</h2>
                            {project.worktrees.length === 0 ? (
                                <p className='text-sm text-gray-600 dark:text-gray-400'>No worktrees yet.</p>
                            ) : (
                                <ul className='divide-y divide-gray-200 dark:divide-gray-700'>
                                    {project.worktrees.map((worktree) => (
                                        <li key={worktree.id} className='py-3'>
                                            <div className='flex items-center justify-between'>
                                                <span className='text-sm text-gray-900 dark:text-gray-100'>{worktree.branch_name}</span>
                                                <span className='text-xs text-gray-500 dark:text-gray-400'>{worktree.status}</span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>

                        <div className='rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800'>
                            <h2 className='mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100'>Tasks ({project.tasks.length})</h2>
                            {project.tasks.length === 0 ? (
                                <p className='text-sm text-gray-600 dark:text-gray-400'>No tasks yet.</p>
                            ) : (
                                <ul className='divide-y divide-gray-200 dark:divide-gray-700'>
                                    {project.tasks.map((task) => (
                                        <li key={task.id} className='py-3'>
                                            <div className='flex items-center justify-between'>
                                                <span className='text-sm text-gray-900 dark:text-gray-100'>{task.title}</span>
                                                <span className='text-xs text-gray-500 dark:text-gray-400'>{task.status}</span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
