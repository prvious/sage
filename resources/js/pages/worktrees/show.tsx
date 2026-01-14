import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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

const statusColors = {
    creating: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    cleaning_up: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};

export default function Show({ project, worktree }: Props) {
    const handleDelete: FormEventHandler = (e) => {
        e.preventDefault();

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

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8'>
                        <Link href={`/projects/${project.id}/worktrees`} className='mb-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400'>
                            ← Back to Worktrees
                        </Link>
                        <div className='mt-2 flex items-center justify-between'>
                            <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>{worktree.branch_name}</h1>
                            <span className={`inline-flex rounded px-3 py-1 text-sm font-medium ${statusColors[worktree.status]}`}>{worktree.status}</span>
                        </div>
                    </div>

                    <div className='space-y-6'>
                        <div className='rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800'>
                            <h2 className='mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100'>Worktree Details</h2>

                            <dl className='space-y-4'>
                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Branch Name</dt>
                                    <dd className='mt-1 text-sm text-gray-900 dark:text-gray-100'>{worktree.branch_name}</dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Preview URL</dt>
                                    <dd className='mt-1 flex items-center gap-2'>
                                        <a
                                            href={worktree.preview_url}
                                            target='_blank'
                                            rel='noopener noreferrer'
                                            className='text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400'
                                        >
                                            {worktree.preview_url}
                                        </a>
                                    </dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Path</dt>
                                    <dd className='mt-1 font-mono text-sm text-gray-900 dark:text-gray-100'>{worktree.path}</dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Database Isolation</dt>
                                    <dd className='mt-1 text-sm text-gray-900 dark:text-gray-100'>{worktree.database_isolation}</dd>
                                </div>

                                <div>
                                    <dt className='text-sm font-medium text-gray-500 dark:text-gray-400'>Created At</dt>
                                    <dd className='mt-1 text-sm text-gray-900 dark:text-gray-100'>{new Date(worktree.created_at).toLocaleString()}</dd>
                                </div>

                                {worktree.error_message && (
                                    <div>
                                        <dt className='text-sm font-medium text-red-600 dark:text-red-400'>Error Message</dt>
                                        <dd className='mt-1 text-sm text-red-600 dark:text-red-400'>{worktree.error_message}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        <div className='flex gap-4'>
                            {worktree.status === 'active' && (
                                <button
                                    onClick={handleOpenPreview}
                                    className='rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700'
                                >
                                    Open in Browser
                                </button>
                            )}

                            <form onSubmit={handleDelete}>
                                <button type='submit' className='rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700'>
                                    Delete Worktree
                                </button>
                            </form>
                        </div>

                        {worktree.status === 'creating' && (
                            <div className='rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20'>
                                <p className='text-sm text-yellow-800 dark:text-yellow-300'>
                                    This worktree is being set up. You'll be notified when it's ready.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
