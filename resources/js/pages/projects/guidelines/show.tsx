import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { GuidelineShowProps } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Edit, ArrowLeft } from 'lucide-react';
import { Textarea } from '@/components/ui/textarea';

export default function Show({ project, filename, content }: GuidelineShowProps) {
    return (
        <>
            <Head title={`${filename} - ${project.name}`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center justify-between'>
                        <div>
                            <h1 className='text-3xl font-bold'>{filename}</h1>
                            <p className='text-muted-foreground mt-2'>Custom Guideline</p>
                        </div>
                        <div className='flex gap-2'>
                            <Button variant='outline' render={<Link href={`/projects/${project.id}/guidelines`}>Back to Guidelines</Link>}>
                                <ArrowLeft className='h-4 w-4 mr-2' />
                                Back
                            </Button>
                            <Button render={<Link href={`/projects/${project.id}/guidelines/${filename}/edit`}>Edit Guideline</Link>}>
                                <Edit className='h-4 w-4 mr-2' />
                                Edit
                            </Button>
                        </div>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Guideline Content</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea value={content} readOnly className='min-h-[500px] font-mono' />
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        </>
    );
}
