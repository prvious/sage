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

interface ShowProps {
    project: Project;
    spec: Spec;
}

export default function Show({ project, spec }: ShowProps) {
    return (
        <>
            <Head title={`${project.name} - ${spec.title}`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>{spec.title}</h1>
                        <p className='text-muted-foreground mt-2'>Project: {project.name}</p>
                    </div>
                    {/* TODO: Add spec display UI */}
                    <div>
                        <pre className='whitespace-pre-wrap text-sm'>{spec.content}</pre>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
