import { Head, Link, router } from '@inertiajs/react';
import { CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Input } from '@/components/ui/input';
import { CenteredCardLayout } from '@/components/layouts/centered-card-layout';
import { PlusIcon, Search, X } from 'lucide-react';
import { useState, useEffect } from 'react';
import { show as projectDashboard } from '@/actions/App/Http/Controllers/DashboardController';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

interface Project {
    id: number;
    name: string;
    path: string;
    server_driver: 'caddy' | 'nginx';
    base_url: string;
    worktrees_count: number;
    tasks_count: number;
    created_at: string;
}

interface Props {
    projects: Project[];
    search?: string;
}

export default function Index({ projects, search = '' }: Props) {
    const [searchQuery, setSearchQuery] = useState(search);

    // Debounce search requests
    useEffect(() => {
        const timer = setTimeout(() => {
            router.get(
                '/projects',
                { search: searchQuery },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['projects'],
                },
            );
        }, 300); // 300ms debounce

        return () => clearTimeout(timer);
    }, [searchQuery]);

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchQuery(e.target.value);
    };

    const clearSearch = () => {
        setSearchQuery('');
    };
    return (
        <>
            <Head title='Projects' />

            <CenteredCardLayout>
                <CardHeader className='border-b'>
                    <div className='flex items-center gap-4'>
                        {/* Search Input - Takes up most space */}
                        <div className='flex-1 relative'>
                            <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
                            <Input
                                type='search'
                                placeholder='Search projects by name, path, or URL...'
                                value={searchQuery}
                                onChange={handleSearchChange}
                                className='pl-10 pr-10'
                                aria-label='Search projects by name, path, or URL'
                            />
                            {searchQuery && (
                                <button onClick={clearSearch} className='absolute right-3 top-1/2 -translate-y-1/2' aria-label='Clear search'>
                                    <X className='h-4 w-4 text-muted-foreground hover:text-foreground' />
                                </button>
                            )}
                        </div>

                        {/* Plus Button - Fixed width */}
                        <Button size='icon' onClick={() => router.visit('/projects/create')} aria-label='Create new project' title='Create New Project'>
                            <PlusIcon className='h-5 w-5' />
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    {projects.length === 0 ? (
                        <div className='flex min-h-150 flex-col items-center justify-center text-center'>
                            {searchQuery ? (
                                <>
                                    <div className='rounded-full bg-muted p-6'>
                                        <Search className='h-12 w-12 text-muted-foreground' />
                                    </div>
                                    <h3 className='mt-6 text-xl font-semibold'>No projects found</h3>
                                    <p className='text-muted-foreground mt-2 max-w-sm'>No projects match "{searchQuery}"</p>
                                    <Button variant='link' onClick={clearSearch} className='mt-2'>
                                        Clear search
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <div className='rounded-full bg-muted p-6'>
                                        <svg className='h-12 w-12 text-muted-foreground' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                            <path
                                                strokeLinecap='round'
                                                strokeLinejoin='round'
                                                strokeWidth={1.5}
                                                d='M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'
                                            />
                                        </svg>
                                    </div>
                                    <h3 className='mt-6 text-xl font-semibold'>No projects yet</h3>
                                    <p className='text-muted-foreground mt-2 max-w-sm'>
                                        Get started by adding your first Laravel project to manage worktrees and deployments
                                    </p>
                                    <Button className='mt-4' render={<Link href='/projects/create'>Add Your First Project</Link>} />
                                </>
                            )}
                        </div>
                    ) : (
                        <ScrollArea className='h-150 flex flex-col gap-4 p-1 w-full'>
                            {projects.map((project) => (
                                <Item
                                    key={project.id}
                                    className='group w-full'
                                    variant='outline'
                                    role='listitem'
                                    render={
                                        <Link href={projectDashboard.url(project.id)}>
                                            <ItemMedia variant='icon'>
                                                <Avatar size='lg'>
                                                    <AvatarFallback>{project.name.charAt(0).toUpperCase()}</AvatarFallback>
                                                </Avatar>
                                            </ItemMedia>
                                            <ItemContent>
                                                <ItemTitle className='capitalize'>{project.name}</ItemTitle>
                                                <ItemDescription>{project.path}</ItemDescription>
                                            </ItemContent>
                                            <ItemActions>
                                                <Button>Open Dashboard</Button>
                                            </ItemActions>
                                        </Link>
                                    }
                                />
                            ))}
                        </ScrollArea>
                    )}
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
