import { Head, Link } from '@inertiajs/react';

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

const statusColors = {
    creating: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    cleaning_up: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};

export default function Index({ project, worktrees }: Props) {
    return (
        <>
            <Head title={`Worktrees - ${project.name}`} />

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8 flex items-center justify-between'>
                        <div>
                            <Link href={`/projects/${project.id}`} className='mb-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400'>
                                ← Back to {project.name}
                            </Link>
                            <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>Worktrees</h1>
                        </div>
                        <Link
                            href={`/projects/${project.id}/worktrees/create`}
                            className='rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700'
                        >
                            Create Worktree
                        </Link>
                    </div>

                    {worktrees.length === 0 ? (
                        <div className='rounded-lg border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-gray-800'>
                            <p className='text-gray-600 dark:text-gray-400'>
                                No worktrees yet. Create your first worktree to work on a feature branch with its own preview URL.
                            </p>
                        </div>
                    ) : (
                        <div className='grid gap-6 sm:grid-cols-2 lg:grid-cols-3'>
                            {worktrees.map((worktree) => (
                                <Link
                                    key={worktree.id}
                                    href={`/projects/${project.id}/worktrees/${worktree.id}`}
                                    className='block rounded-lg border border-gray-200 bg-white p-6 transition hover:border-blue-500 dark:border-gray-700 dark:bg-gray-800'
                                >
                                    <div className='mb-3 flex items-center justify-between'>
                                        <span className={`inline-flex rounded px-2 py-1 text-xs font-medium ${statusColors[worktree.status]}`}>
                                            {worktree.status}
                                        </span>
                                    </div>
                                    <h3 className='mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100'>{worktree.branch_name}</h3>
                                    <p className='mb-2 text-sm text-gray-600 dark:text-gray-400'>{worktree.preview_url}</p>
                                    {worktree.error_message && <p className='mt-2 text-sm text-red-600 dark:text-red-400'>{worktree.error_message}</p>}
                                    <div className='mt-4 inline-flex rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300'>
                                        {worktree.database_isolation} DB
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
