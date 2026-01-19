import { Form, Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CenteredCardLayout } from '@/components/layouts/centered-card-layout';
import { FolderIcon, HomeIcon } from 'lucide-react';
import { create, store } from '@/actions/App/Http/Controllers/ProjectController';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field';
import { Separator } from '@/components/ui/separator';
import { debounce } from '@/lib/utils';

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
    const folderName = currentPath.split('/').filter(Boolean).pop() || '';
    const projectName = folderName
        .replace(/[^a-zA-Z0-9-_]/g, '-')
        .replace(/-+/g, '-')
        .toLowerCase();

    const navigate = debounce(
        (element: HTMLInputElement) =>
            router.visit(create({ query: { path: element.value } }).url, {
                preserveState: true,
                replace: true,
            }),
        400,
    );

    const searchInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (searchInputRef.current) searchInputRef.current.focus();
    }, []);

    return (
        <>
            <Head title='Add Project' />

            <CenteredCardLayout cardClassName='max-w-5xl'>
                <CardHeader className='border-b'>
                    <div>
                        <div className='flex items-center gap-4'>
                            <Button type='button' variant='ghost' size='icon'>
                                <HomeIcon />
                            </Button>

                            <Input
                                ref={searchInputRef}
                                defaultValue={currentPath}
                                placeholder={homePath || 'Enter path...'}
                                onChange={(e) => navigate(e.target)}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent className='py-1'>
                    <div className='flex gap-6'>
                        <div className='space-y-4 col-span-1 w-lg'>
                            <ScrollArea className='h-120'>
                                <div className='space-y-1'>
                                    {directories.length === 0 ? (
                                        <div className='text-center py-8 text-muted-foreground text-sm'>No subdirectories found</div>
                                    ) : (
                                        directories.map((dir) => (
                                            <Link
                                                key={dir.path}
                                                href={
                                                    create({
                                                        query: {
                                                            path: dir.path,
                                                        },
                                                    }).url
                                                }
                                                className='w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-muted transition-colors text-left'
                                            >
                                                <FolderIcon className='h-4 w-4 text-muted-foreground shrink-0' />
                                                <span className='text-sm truncate'>{dir.name}</span>
                                            </Link>
                                        ))
                                    )}
                                </div>
                            </ScrollArea>
                        </div>

                        <Separator orientation='vertical' />

                        <Form className='basis-full' {...store.form()}>
                            {({ errors, processing }) => (
                                <FieldSet className='space-y-6'>
                                    <FieldGroup>
                                        <Field>
                                            <FieldLabel htmlFor='name'>Project Name</FieldLabel>
                                            <Input id='name' name='name' defaultValue={folderName} placeholder='My Laravel App' />
                                            {/* <FieldDescription>
                                                Choose a unique username for your account.
                                                </FieldDescription> */}
                                            {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor='base_url'>Base URL</FieldLabel>
                                            <Input
                                                id='base_url'
                                                name='base_url'
                                                defaultValue={projectName ? `${projectName}.localhost` : ''}
                                                placeholder='myproject.localhost'
                                            />
                                            {errors.base_url && <p className='text-sm text-destructive'>{errors.base_url}</p>}
                                        </Field>

                                        <Field>
                                            <Label htmlFor='path'>Project Path</Label>
                                            <Input id='path' name='path' defaultValue={currentPath} className='font-mono text-sm' readOnly />
                                            {errors.path && <p className='text-sm text-destructive'>{errors.path}</p>}
                                        </Field>

                                        <div className='flex justify-end gap-3 pt-4'>
                                            <Button type='button' variant='ghost' render={<Link href='/projects'>Cancel</Link>} />
                                            <Button type='submit' disabled={processing}>
                                                {processing ? 'Creating...' : 'Create Project'}
                                            </Button>
                                        </div>
                                    </FieldGroup>
                                </FieldSet>
                            )}
                        </Form>
                    </div>
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
