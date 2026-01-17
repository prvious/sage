import { Project, ServerStatus as ServerStatusType } from '@/types';
import { ServerDriverSelector } from './server-driver-selector';
import { ServerStatus } from './server-status';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Form } from '@inertiajs/react';

interface ServerSettingsProps {
    project: Project;
    serverStatus: ServerStatusType;
}

export function ServerSettings({ project, serverStatus }: ServerSettingsProps) {
    return (
        <div className='space-y-6'>
            <ServerDriverSelector project={project} />
            <ServerStatus serverStatus={serverStatus} projectId={project.id} />

            <Card>
                <CardHeader>
                    <CardTitle>Advanced Server Configuration</CardTitle>
                    <CardDescription>Configure server-specific settings</CardDescription>
                </CardHeader>
                <CardContent>
                    <Form method='put' action={`/projects/${project.id}/settings`}>
                        {({ errors, processing }) => (
                            <div className='space-y-6'>
                                <div className='space-y-2'>
                                    <Label htmlFor='server_port'>Custom Port (optional)</Label>
                                    <Input
                                        id='server_port'
                                        name='server_port'
                                        type='number'
                                        defaultValue={project.server_port?.toString() || ''}
                                        placeholder='8000'
                                        min='1'
                                        max='65535'
                                    />
                                    {errors.server_port && <p className='text-sm text-destructive'>{errors.server_port}</p>}
                                    <p className='text-sm text-muted-foreground'>Override the default server port</p>
                                </div>

                                <div className='flex items-center justify-between space-x-2'>
                                    <div className='space-y-0.5'>
                                        <Label htmlFor='tls_enabled'>Enable TLS/HTTPS</Label>
                                        <p className='text-sm text-muted-foreground'>Automatically enable HTTPS for this project</p>
                                    </div>
                                    <Switch id='tls_enabled' name='tls_enabled' defaultChecked={project.tls_enabled} />
                                </div>

                                <div className='space-y-2'>
                                    <Label htmlFor='custom_domain'>Custom Domain (optional)</Label>
                                    <Input id='custom_domain' name='custom_domain' defaultValue={project.custom_domain || ''} placeholder='myapp.example.com' />
                                    {errors.custom_domain && <p className='text-sm text-destructive'>{errors.custom_domain}</p>}
                                    <p className='text-sm text-muted-foreground'>Use a custom domain instead of the base URL</p>
                                </div>

                                <div className='space-y-2'>
                                    <Label htmlFor='custom_directives'>Custom Server Directives (optional)</Label>
                                    <Textarea
                                        id='custom_directives'
                                        name='custom_directives'
                                        defaultValue={project.custom_directives || ''}
                                        placeholder='Add custom Caddy or Nginx directives here...'
                                        rows={6}
                                    />
                                    {errors.custom_directives && <p className='text-sm text-destructive'>{errors.custom_directives}</p>}
                                    <p className='text-sm text-muted-foreground'>Add custom configuration directives for your server driver</p>
                                </div>

                                <div className='flex justify-end'>
                                    <Button type='submit' disabled={processing}>
                                        {processing ? 'Saving...' : 'Save Server Settings'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </CardContent>
            </Card>
        </div>
    );
}
