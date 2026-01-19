import { Form } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Project } from '@/types';
import { Copy } from 'lucide-react';
import { useState } from 'react';

interface GeneralSettingsProps {
    project: Project;
}

export function GeneralSettings({ project }: GeneralSettingsProps) {
    const [copied, setCopied] = useState(false);

    const handleCopyPath = () => {
        navigator.clipboard.writeText(project.path);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>General Settings</CardTitle>
                <CardDescription>Manage your project's basic information</CardDescription>
            </CardHeader>
            <CardContent>
                <Form method='put' action={`/projects/${project.id}/settings`}>
                    {({ errors, processing }) => (
                        <div className='space-y-6'>
                            <div className='space-y-2'>
                                <Label htmlFor='name'>Project Name</Label>
                                <Input id='name' name='name' defaultValue={project.name} placeholder='My Laravel App' />
                                {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                            </div>

                            <div className='space-y-2'>
                                <Label htmlFor='path'>Project Path</Label>
                                <div className='flex gap-2'>
                                    <Input id='path' name='path' value={project.path} readOnly className='flex-1' />
                                    <Button type='button' variant='outline' size='icon' onClick={handleCopyPath}>
                                        <Copy className='h-4 w-4' />
                                    </Button>
                                </div>
                                {copied && <p className='text-sm text-muted-foreground'>Copied to clipboard!</p>}
                            </div>

                            <div className='space-y-2'>
                                <Label htmlFor='base_url'>Base URL</Label>
                                <Input id='base_url' name='base_url' defaultValue={project.base_url} placeholder='http://myproject.local' />
                                {errors.base_url && <p className='text-sm text-destructive'>{errors.base_url}</p>}
                            </div>

                            <div className='flex justify-end'>
                                <Button type='submit' disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
