import { Form, Head, Link } from '@inertiajs/react';
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { CenteredCardLayout } from '@/components/layouts/centered-card-layout';
import { Field, FieldContent, FieldDescription, FieldLabel, FieldTitle } from '@/components/ui/field';
import { Badge } from '@/components/ui/badge';
import { update } from '@/actions/App/Http/Controllers/ProjectController';

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
    return (
        <>
            <Head title={`Edit ${project.name}`} />

            <CenteredCardLayout>
                <CardHeader>
                    <CardTitle>Edit Project</CardTitle>
                    <CardDescription>Update your Laravel project settings</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form {...update.form(project.id)}>
                        {({ errors, processing }) => (
                            <div className='space-y-6'>
                                <div className='space-y-2'>
                                    <Label htmlFor='name'>Project Name</Label>
                                    <Input id='name' name='name' defaultValue={project.name} placeholder='My Laravel App' />
                                    {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                                </div>

                                <div className='space-y-2'>
                                    <Label htmlFor='path'>Project Path</Label>
                                    <Input id='path' name='path' defaultValue={project.path} placeholder='/var/www/myproject' />
                                    {errors.path && <p className='text-sm text-destructive'>{errors.path}</p>}
                                </div>

                                <div className='space-y-2'>
                                    <Label>Server Driver</Label>
                                    <RadioGroup defaultValue={project.server_driver} name='server_driver'>
                                        <Field orientation='horizontal'>
                                            <FieldLabel htmlFor='caddy' />
                                            <FieldContent>
                                                <FieldTitle>Caddy</FieldTitle>
                                                <FieldDescription>
                                                    Modern web server with automatic HTTPS
                                                    <Badge variant='secondary' className='ml-2 text-xs'>
                                                        Recommended
                                                    </Badge>
                                                </FieldDescription>
                                            </FieldContent>
                                            <RadioGroupItem value='caddy' id='caddy' />
                                        </Field>

                                        <Field orientation='horizontal'>
                                            <FieldLabel htmlFor='nginx' />
                                            <FieldContent>
                                                <FieldTitle>Nginx</FieldTitle>
                                                <FieldDescription>High-performance production web server</FieldDescription>
                                            </FieldContent>
                                            <RadioGroupItem value='nginx' id='nginx' />
                                        </Field>

                                        <Field orientation='horizontal'>
                                            <FieldLabel htmlFor='artisan' />
                                            <FieldContent>
                                                <FieldTitle>Artisan Server</FieldTitle>
                                                <FieldDescription>
                                                    Lightweight PHP development server
                                                    <Badge variant='outline' className='ml-2 text-xs'>
                                                        Development Only
                                                    </Badge>
                                                </FieldDescription>
                                            </FieldContent>
                                            <RadioGroupItem value='artisan' id='artisan' />
                                        </Field>
                                    </RadioGroup>
                                    {errors.server_driver && <p className='text-sm text-destructive'>{errors.server_driver}</p>}
                                </div>

                                <div className='space-y-2'>
                                    <Label htmlFor='base_url'>Base URL</Label>
                                    <Input id='base_url' name='base_url' defaultValue={project.base_url} placeholder='myproject.local' />
                                    {errors.base_url && <p className='text-sm text-destructive'>{errors.base_url}</p>}
                                </div>

                                <div className='flex justify-end gap-3'>
                                    <Button type='button' variant='ghost' render={<Link href={`/projects/${project.id}`}>Cancel</Link>} />
                                    <Button type='submit' disabled={processing}>
                                        {processing ? 'Saving...' : 'Save Changes'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
