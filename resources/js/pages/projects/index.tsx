import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Input } from '@/components/ui/input';
import { CenteredCardLayout } from '@/components/layouts/centered-card-layout';
import { PlusIcon, Search, X } from 'lucide-react';
import { useState, useEffect } from 'react';
import { show as projectDashboard } from '@/actions/App/Http/Controllers/DashboardController';

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
                        <ScrollArea className='h-150'>
                            <div className='space-y-4 pr-4'>
                                {projects.map((project) => (
                                    <Link key={project.id} href={projectDashboard.url(project.id)} className='block group'>
                                        <Card className='transition-all duration-200 hover:shadow-lg hover:border-primary'>
                                            <CardHeader className='pb-3'>
                                                <div className='flex items-start justify-between'>
                                                    <div className='flex-1'>
                                                        <CardTitle className='text-lg font-bold group-hover:text-primary transition-colors'>
                                                            {project.name}
                                                        </CardTitle>
                                                        <CardDescription className='line-clamp-1 font-mono text-xs mt-1'>{project.path}</CardDescription>
                                                    </div>
                                                    <Badge variant='secondary' className='ml-2 shrink-0 capitalize'>
                                                        {project.server_driver}
                                                    </Badge>
                                                </div>
                                            </CardHeader>
                                            <CardContent className='pt-0'>
                                                <div className='flex items-center gap-4'>
                                                    <div className='flex items-center gap-2 text-sm'>
                                                        <span className='text-lg font-semibold'>{project.worktrees_count}</span>
                                                        <span className='text-xs text-muted-foreground'>Worktrees</span>
                                                    </div>
                                                    <div className='flex items-center gap-2 text-sm'>
                                                        <span className='text-lg font-semibold'>{project.tasks_count}</span>
                                                        <span className='text-xs text-muted-foreground'>Tasks</span>
                                                    </div>
                                                    {project.base_url && (
                                                        <div className='flex items-center gap-2 text-sm ml-auto'>
                                                            <svg
                                                                className='h-4 w-4 shrink-0 text-muted-foreground'
                                                                fill='none'
                                                                stroke='currentColor'
                                                                viewBox='0 0 24 24'
                                                            >
                                                                <path
                                                                    strokeLinecap='round'
                                                                    strokeLinejoin='round'
                                                                    strokeWidth={2}
                                                                    d='M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'
                                                                />
                                                            </svg>
                                                            <span className='text-muted-foreground truncate font-mono text-xs max-w-50'>
                                                                {project.base_url}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                ))}
                            </div>
                        </ScrollArea>
                    )}
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
