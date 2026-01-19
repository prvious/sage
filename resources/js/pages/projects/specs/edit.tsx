import { AppLayout } from '@/components/layout/app-layout';
import { Head } from '@inertiajs/react';

interface Spec {
    id: number;
    title: string;
    content: string;
    generated_from_idea: string | null;
    project: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
}

interface Project {
    id: number;
    name: string;
    path: string;
}

interface EditProps {
    project: Project;
    spec: Spec;
}

export default function Edit({ project, spec }: EditProps) {
    return (
        <>
            <Head title={`${project.name} - Edit ${spec.title}`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Edit Specification</h1>
                        <p className='text-muted-foreground mt-2'>{spec.title}</p>
                    </div>
                    {/* TODO: Add spec edit form */}
                    <div className='text-muted-foreground'>Spec edit form coming soon...</div>
                </div>
            </AppLayout>
        </>
    );
}
