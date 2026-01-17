import { Head } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AppLayout } from '@/components/layout/app-layout';
import EnvVariableForm from '@/components/env-variable-form';
import { AlertCircle } from 'lucide-react';

interface EnvVariable {
    value: string;
    comment?: string | null;
    is_sensitive: boolean;
}

interface Project {
    id: number;
    name: string;
    path: string;
}

interface Props {
    project: Project;
    variables?: Record<string, EnvVariable>;
    grouped?: Record<string, Record<string, EnvVariable>>;
    errors?: Record<string, string[]>;
    missing?: string[];
    env_path: string;
    error?: string;
}

export default function Environment({ project, variables, grouped, errors, missing, env_path, error }: Props) {
    return (
        <>
            <Head title={`${project.name} - Environment`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Environment Variables</h1>
                        <p className='text-muted-foreground mt-2'>Manage environment variables for {project.name}</p>
                        <p className='text-xs text-muted-foreground mt-1'>{env_path}</p>
                    </div>

                    {error && (
                        <Alert variant='destructive'>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    {missing && missing.length > 0 && (
                        <Alert>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>Missing required variables: {missing.join(', ')}</AlertDescription>
                        </Alert>
                    )}

                    {grouped && <EnvVariableForm grouped={grouped} envPath={env_path} projectId={project.id} />}
                </div>
            </AppLayout>
        </>
    );
}
