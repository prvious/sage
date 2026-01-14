import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Project {
    id: number;
    name: string;
}

interface Props {
    project: Project;
}

export default function Create({ project }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        branch_name: '',
        create_branch: false,
        database_isolation: 'separate',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/projects/${project.id}/worktrees`);
    };

    return (
        <>
            <Head title={`Create Worktree - ${project.name}`} />

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8'>
                        <Link href={`/projects/${project.id}/worktrees`} className='mb-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400'>
                            ← Back to Worktrees
                        </Link>
                        <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>Create Worktree</h1>
                    </div>

                    <div className='rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800'>
                        <form onSubmit={submit} className='space-y-6'>
                            <div>
                                <label htmlFor='branch_name' className='block text-sm font-medium text-gray-900 dark:text-gray-100'>
                                    Branch Name
                                </label>
                                <input
                                    id='branch_name'
                                    type='text'
                                    value={data.branch_name}
                                    onChange={(e) => setData('branch_name', e.target.value)}
                                    className='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100'
                                    placeholder='feature/new-feature'
                                    required
                                />
                                {errors.branch_name && <p className='mt-1 text-sm text-red-600 dark:text-red-400'>{errors.branch_name}</p>}
                            </div>

                            <div className='flex items-center'>
                                <input
                                    id='create_branch'
                                    type='checkbox'
                                    checked={data.create_branch}
                                    onChange={(e) => setData('create_branch', e.target.checked)}
                                    className='h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700'
                                />
                                <label htmlFor='create_branch' className='ml-2 block text-sm text-gray-900 dark:text-gray-100'>
                                    Create branch if it doesn't exist
                                </label>
                            </div>

                            <div>
                                <label htmlFor='database_isolation' className='block text-sm font-medium text-gray-900 dark:text-gray-100'>
                                    Database Isolation
                                </label>
                                <select
                                    id='database_isolation'
                                    value={data.database_isolation}
                                    onChange={(e) => setData('database_isolation', e.target.value)}
                                    className='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100'
                                >
                                    <option value='separate'>Separate Database (SQLite)</option>
                                    <option value='prefix'>Table Prefix</option>
                                    <option value='shared'>Shared Database</option>
                                </select>
                                {errors.database_isolation && <p className='mt-1 text-sm text-red-600 dark:text-red-400'>{errors.database_isolation}</p>}
                                <p className='mt-1 text-sm text-gray-500 dark:text-gray-400'>Choose how the worktree database should be isolated</p>
                            </div>

                            <div className='flex gap-4'>
                                <button
                                    type='submit'
                                    disabled={processing}
                                    className='rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50'
                                >
                                    {processing ? 'Creating...' : 'Create Worktree'}
                                </button>
                                <Link
                                    href={`/projects/${project.id}/worktrees`}
                                    className='rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                                >
                                    Cancel
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
