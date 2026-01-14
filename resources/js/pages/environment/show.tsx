import { Head, Link } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import EnvVariableForm from '@/components/env-variable-form';
import { AlertCircle, ArrowLeft, GitCompare } from 'lucide-react';

interface EnvVariable {
    value: string;
    comment?: string | null;
    is_sensitive: boolean;
}

interface Source {
    id: number;
    name: string;
    type: 'project' | 'worktree';
    env_path: string;
    project_id?: number;
}

interface Props {
    source: Source;
    variables?: Record<string, EnvVariable>;
    grouped?: Record<string, Record<string, EnvVariable>>;
    errors?: Record<string, string[]>;
    missing?: string[];
    error?: string;
}

export default function Show({ source, variables, grouped, errors, missing, error }: Props) {
    return (
        <>
            <Head title={`Environment - ${source.name}`} />

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8'>
                        <Link
                            href='/environment'
                            className='mb-4 inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                        >
                            <ArrowLeft className='mr-2 h-4 w-4' />
                            Back to Environment Manager
                        </Link>

                        <div className='flex items-center justify-between'>
                            <div>
                                <div className='flex items-center gap-3'>
                                    <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>{source.name}</h1>
                                    <Badge variant={source.type === 'project' ? 'default' : 'secondary'}>
                                        {source.type === 'project' ? 'Project' : 'Worktree'}
                                    </Badge>
                                </div>
                                <p className='mt-2 text-sm text-gray-600 dark:text-gray-400'>{source.env_path}</p>
                            </div>

                            <div className='flex gap-2'>
                                {source.type === 'project' && <Button variant='outline'>Sync to Worktrees</Button>}
                                {source.type === 'worktree' && (
                                    <Link href={`/environment/compare/${source.project_id}/${source.id}`}>
                                        <Button variant='outline'>
                                            <GitCompare className='mr-2 h-4 w-4' />
                                            Compare with Project
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>

                    {error && (
                        <Alert variant='destructive' className='mb-6'>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    {missing && missing.length > 0 && (
                        <Alert className='mb-6'>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>Missing required variables: {missing.join(', ')}</AlertDescription>
                        </Alert>
                    )}

                    {grouped && <EnvVariableForm grouped={grouped} envPath={source.env_path} />}
                </div>
            </div>
        </>
    );
}
