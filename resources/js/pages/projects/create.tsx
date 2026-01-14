import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { CenteredCardLayout } from '@/components/layouts/centered-card-layout';
import { FolderBrowser, FolderBrowserHeaderProps } from '@/components/file-explorer/folder-browser';
import { Separator } from '@/components/ui/separator';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import { HomeIcon } from 'lucide-react';

interface Directory {
    name: string;
    path: string;
    type: string;
}

interface Breadcrumb {
    name: string;
    path: string;
}

interface Props {
    directories: Directory[];
    breadcrumbs: Breadcrumb[];
    currentPath: string;
    homePath: string;
}

export default function Create({ directories, breadcrumbs, currentPath, homePath }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        path: '',
        server_driver: 'caddy' as 'caddy' | 'nginx' | 'artisan',
        base_url: '',
    });

    const [headerProps, setHeaderProps] = useState<FolderBrowserHeaderProps | null>(null);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/projects');
    };

    const handlePathSelect = (path: string) => {
        // Extract folder name from path
        const folderName = path.split('/').filter(Boolean).pop() || '';
        const projectName = folderName
            .replace(/[^a-zA-Z0-9-_]/g, '-')
            .replace(/-+/g, '-')
            .toLowerCase();

        setData({
            ...data,
            path,
            name: folderName,
            base_url: projectName ? `${projectName}.localhost` : '',
        });
    };

    return (
        <>
            <Head title='Add Project' />

            <CenteredCardLayout>
                <CardHeader className='border-b'>
                    <div>
                        {headerProps && (
                            <div className='flex items-center'>
                                <Button type='button' variant='outline' size='icon' onClick={headerProps.onHomeClick}>
                                    <HomeIcon />
                                </Button>
                                <Input
                                    value={headerProps.inputPath}
                                    onChange={(e) => headerProps.setInputPath(e.target.value)}
                                    onKeyDown={headerProps.onInputKeyDown}
                                    placeholder={headerProps.homePath || 'Enter path...'}
                                />
                            </div>
                        )}
                    </div>
                </CardHeader>
                <CardContent className='py-1'>
                    <div className='space-y-6'>
                        <FolderBrowser
                            directories={directories}
                            breadcrumbs={breadcrumbs}
                            currentPath={currentPath}
                            homePath={homePath}
                            onPathSelect={handlePathSelect}
                            onHeaderPropsChange={setHeaderProps}
                        />

                        {data.path && (
                            <>
                                <Separator />

                                <form onSubmit={handleSubmit} className='space-y-6'>
                                    <div className='grid grid-cols-2 gap-4'>
                                        <div className='space-y-2'>
                                            <Label htmlFor='name'>Project Name</Label>
                                            <Input id='name' value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder='My Laravel App' />
                                            {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                                        </div>

                                        <div className='space-y-2'>
                                            <Label htmlFor='base_url'>Base URL</Label>
                                            <Input
                                                id='base_url'
                                                value={data.base_url}
                                                onChange={(e) => setData('base_url', e.target.value)}
                                                placeholder='myproject.localhost'
                                            />
                                            {errors.base_url && <p className='text-sm text-destructive'>{errors.base_url}</p>}
                                        </div>
                                    </div>

                                    <div className='space-y-2'>
                                        <Label htmlFor='path'>Project Path</Label>
                                        <Input
                                            id='path'
                                            value={data.path}
                                            onChange={(e) => setData('path', e.target.value)}
                                            className='font-mono text-sm'
                                            readOnly
                                        />
                                        {errors.path && <p className='text-sm text-destructive'>{errors.path}</p>}
                                    </div>

                                    <div className='space-y-2'>
                                        <Label>Server Driver</Label>
                                        <RadioGroup
                                            value={data.server_driver}
                                            onValueChange={(value) => setData('server_driver', value as 'caddy' | 'nginx' | 'artisan')}
                                        >
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

                                    <div className='flex justify-end gap-3 pt-4'>
                                        <Button type='button' variant='ghost' render={<Link href='/projects'>Cancel</Link>} />
                                        <Button type='submit' disabled={processing}>
                                            {processing ? 'Creating...' : 'Create Project'}
                                        </Button>
                                    </div>
                                </form>
                            </>
                        )}
                    </div>
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
