import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Project {
    id: number;
    name: string;
    path: string;
    server_driver: 'caddy' | 'nginx';
    base_url: string;
}

interface Props {
    project: Project;
}

export default function Edit({ project }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: project.name,
        path: project.path,
        server_driver: project.server_driver,
        base_url: project.base_url,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        patch(`/projects/${project.id}`);
    };

    return (
        <>
            <Head title={`Edit ${project.name}`} />

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8'>
                        <Link href={`/projects/${project.id}`} className='mb-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400'>
                            ← Back to Project
                        </Link>
                        <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>Edit Project</h1>
                    </div>

                    <form onSubmit={handleSubmit} className='rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800'>
                        <div className='space-y-6'>
                            <div>
                                <label htmlFor='name' className='block text-sm font-medium text-gray-700 dark:text-gray-300'>
                                    Project Name
                                </label>
                                <input
                                    type='text'
                                    id='name'
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className='mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100'
                                />
                                {errors.name && <p className='mt-1 text-sm text-red-600'>{errors.name}</p>}
                            </div>

                            <div>
                                <label htmlFor='path' className='block text-sm font-medium text-gray-700 dark:text-gray-300'>
                                    Project Path
                                </label>
                                <input
                                    type='text'
                                    id='path'
                                    value={data.path}
                                    onChange={(e) => setData('path', e.target.value)}
                                    placeholder='/var/www/myproject'
                                    className='mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100'
                                />
                                {errors.path && <p className='mt-1 text-sm text-red-600'>{errors.path}</p>}
                            </div>

                            <div>
                                <label className='block text-sm font-medium text-gray-700 dark:text-gray-300'>Server Driver</label>
                                <div className='mt-2 flex gap-4'>
                                    <label className='flex items-center'>
                                        <input
                                            type='radio'
                                            value='caddy'
                                            checked={data.server_driver === 'caddy'}
                                            onChange={(e) => setData('server_driver', e.target.value as 'caddy' | 'nginx')}
                                            className='h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500'
                                        />
                                        <span className='ml-2 text-sm text-gray-700 dark:text-gray-300'>Caddy</span>
                                    </label>
                                    <label className='flex items-center'>
                                        <input
                                            type='radio'
                                            value='nginx'
                                            checked={data.server_driver === 'nginx'}
                                            onChange={(e) => setData('server_driver', e.target.value as 'caddy' | 'nginx')}
                                            className='h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500'
                                        />
                                        <span className='ml-2 text-sm text-gray-700 dark:text-gray-300'>Nginx</span>
                                    </label>
                                </div>
                                {errors.server_driver && <p className='mt-1 text-sm text-red-600'>{errors.server_driver}</p>}
                            </div>

                            <div>
                                <label htmlFor='base_url' className='block text-sm font-medium text-gray-700 dark:text-gray-300'>
                                    Base URL
                                </label>
                                <input
                                    type='text'
                                    id='base_url'
                                    value={data.base_url}
                                    onChange={(e) => setData('base_url', e.target.value)}
                                    placeholder='myproject.local'
                                    className='mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100'
                                />
                                {errors.base_url && <p className='mt-1 text-sm text-red-600'>{errors.base_url}</p>}
                            </div>
                        </div>

                        <div className='mt-6 flex justify-end gap-3'>
                            <Link
                                href={`/projects/${project.id}`}
                                className='rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
                            >
                                Cancel
                            </Link>
                            <button
                                type='submit'
                                disabled={processing}
                                className='rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50'
                            >
                                {processing ? 'Saving...' : 'Save Changes'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
