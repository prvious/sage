import { AppLayout } from '@/components/layout/app-layout';
import { BrainstormList } from '@/components/brainstorm/brainstorm-list';
import { ContextInputForm } from '@/components/brainstorm/context-input-form';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Head } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';

interface Project {
    id: number;
    name: string;
    path: string;
}

interface Brainstorm {
    id: number;
    project_id: number;
    user_context: string | null;
    ideas: any[] | null;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface BrainstormPageProps {
    project: Project;
    brainstorms: Brainstorm[];
}

export default function BrainstormPage({ project, brainstorms }: BrainstormPageProps) {
    return (
        <>
            <Head title={`${project.name} - Brainstorm`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center gap-3'>
                        <Sparkles className='h-8 w-8 text-yellow-500' />
                        <div>
                            <h1 className='text-3xl font-bold'>{project.name}</h1>
                            <div className='flex items-center gap-2 mt-1'>
                                <Badge variant='secondary'>Brainstorm</Badge>
                            </div>
                        </div>
                    </div>

                    <Card className='p-6'>
                        <h2 className='text-xl font-semibold mb-4'>Create New Brainstorm Session</h2>
                        <ContextInputForm projectId={project.id} />
                    </Card>

                    <Separator />

                    <div>
                        <h2 className='text-2xl font-semibold mb-4'>Previous Sessions</h2>
                        <BrainstormList brainstorms={brainstorms} />
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
