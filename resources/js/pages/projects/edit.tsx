import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { CenteredCardLayout } from '@/components/layouts/centered-card-layout';

interface Project {
    id: number;
    name: string;
    path: string;
    server_driver: 'caddy' | 'nginx' | 'artisan';
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

            <CenteredCardLayout>
                <CardHeader>
                    <CardTitle>Edit Project</CardTitle>
                    <CardDescription>Update your Laravel project settings</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className='space-y-6'>
                        <div className='space-y-2'>
                            <Label htmlFor='name'>Project Name</Label>
                            <Input id='name' value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder='My Laravel App' />
                            {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                        </div>

                        <div className='space-y-2'>
                            <Label htmlFor='path'>Project Path</Label>
                            <Input id='path' value={data.path} onChange={(e) => setData('path', e.target.value)} placeholder='/var/www/myproject' />
                            {errors.path && <p className='text-sm text-destructive'>{errors.path}</p>}
                        </div>

                        <div className='space-y-2'>
                            <Label>Server Driver</Label>
                            <RadioGroup value={data.server_driver} onValueChange={(value) => setData('server_driver', value as 'caddy' | 'nginx' | 'artisan')}>
                                <div className='flex items-center space-x-2'>
                                    <RadioGroupItem value='caddy' id='caddy' />
                                    <Label htmlFor='caddy' className='font-normal'>
                                        Caddy
                                    </Label>
                                </div>
                                <div className='flex items-center space-x-2'>
                                    <RadioGroupItem value='nginx' id='nginx' />
                                    <Label htmlFor='nginx' className='font-normal'>
                                        Nginx
                                    </Label>
                                </div>
                                <div className='flex items-center space-x-2'>
                                    <RadioGroupItem value='artisan' id='artisan' />
                                    <Label htmlFor='artisan' className='font-normal'>
                                        Artisan Server
                                    </Label>
                                </div>
                            </RadioGroup>
                            {errors.server_driver && <p className='text-sm text-destructive'>{errors.server_driver}</p>}
                        </div>

                        <div className='space-y-2'>
                            <Label htmlFor='base_url'>Base URL</Label>
                            <Input id='base_url' value={data.base_url} onChange={(e) => setData('base_url', e.target.value)} placeholder='myproject.local' />
                            {errors.base_url && <p className='text-sm text-destructive'>{errors.base_url}</p>}
                        </div>

                        <div className='flex justify-end gap-3'>
                            <Button type='button' variant='ghost' asChild>
                                <Link href={`/projects/${project.id}`}>Cancel</Link>
                            </Button>
                            <Button type='submit' disabled={processing}>
                                {processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
