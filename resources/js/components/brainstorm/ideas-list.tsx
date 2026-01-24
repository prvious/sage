import { IdeaCard } from './idea-card';
import { IdeaFilters } from './idea-filters';
import { IdeaStats } from './idea-stats';
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';
import { useMemo, useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { export as exportBrainstorm } from '@/actions/App/Http/Controllers/BrainstormController';

interface Idea {
    title: string;
    description: string;
    priority: 'high' | 'medium' | 'low';
    category: 'feature' | 'enhancement' | 'infrastructure' | 'tooling';
}

interface IdeasListProps {
    ideas: Idea[];
    projectId: number;
    brainstormId: number;
}

const priorityOrder = { high: 3, medium: 2, low: 1 };

export function IdeasList({ ideas, projectId, brainstormId }: IdeasListProps) {
    // Initialize state from URL query params
    const [category, setCategory] = useState('all');
    const [priority, setPriority] = useState('all');
    const [search, setSearch] = useState('');
    const [sortBy, setSortBy] = useState('title');
    const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('asc');

    // Load filters from URL on mount
    useEffect(() => {
        const searchParams = new URLSearchParams(window.location.search);
        setCategory(searchParams.get('category') || 'all');
        setPriority(searchParams.get('priority') || 'all');
        setSearch(searchParams.get('search') || '');
        setSortBy(searchParams.get('sortBy') || 'title');
        setSortOrder((searchParams.get('sortOrder') as 'asc' | 'desc') || 'asc');
    }, []);

    // Update URL when filters change
    const updateFilters = (newFilters: { category?: string; priority?: string; search?: string; sortBy?: string; sortOrder?: 'asc' | 'desc' }) => {
        const params = new URLSearchParams(window.location.search);

        if (newFilters.category !== undefined) {
            newFilters.category !== 'all' ? params.set('category', newFilters.category) : params.delete('category');
        }
        if (newFilters.priority !== undefined) {
            newFilters.priority !== 'all' ? params.set('priority', newFilters.priority) : params.delete('priority');
        }
        if (newFilters.search !== undefined) {
            newFilters.search ? params.set('search', newFilters.search) : params.delete('search');
        }
        if (newFilters.sortBy !== undefined) {
            params.set('sortBy', newFilters.sortBy);
        }
        if (newFilters.sortOrder !== undefined) {
            params.set('sortOrder', newFilters.sortOrder);
        }

        const paramsString = params.toString();
        const url = `${window.location.pathname}${paramsString ? `?${paramsString}` : ''}`;

        router.visit(url, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const handleCategoryChange = (value: string) => {
        setCategory(value);
        updateFilters({ category: value });
    };

    const handlePriorityChange = (value: string) => {
        setPriority(value);
        updateFilters({ priority: value });
    };

    const handleSearchChange = (value: string) => {
        setSearch(value);
        updateFilters({ search: value });
    };

    const handleSortByChange = (value: string) => {
        setSortBy(value);
        updateFilters({ sortBy: value });
    };

    const handleSortOrderChange = (value: 'asc' | 'desc') => {
        setSortOrder(value);
        updateFilters({ sortOrder: value });
    };

    const handleClearFilters = () => {
        setCategory('all');
        setPriority('all');
        setSearch('');
        setSortBy('title');
        setSortOrder('asc');
        router.visit(window.location.pathname, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const handleExport = () => {
        window.location.href = exportBrainstorm.url(projectId, brainstormId);
    };

    // Calculate active filter count
    const activeFilterCount = useMemo(() => {
        let count = 0;
        if (category !== 'all') count++;
        if (priority !== 'all') count++;
        if (search) count++;
        return count;
    }, [category, priority, search]);

    // Filter and sort ideas
    const filteredAndSortedIdeas = useMemo(() => {
        let filtered = ideas;

        // Filter by category
        if (category !== 'all') {
            filtered = filtered.filter((idea) => idea.category === category);
        }

        // Filter by priority
        if (priority !== 'all') {
            filtered = filtered.filter((idea) => idea.priority === priority);
        }

        // Filter by search
        if (search) {
            const searchLower = search.toLowerCase();
            filtered = filtered.filter(
                (idea) => idea.title.toLowerCase().includes(searchLower) || idea.description.toLowerCase().includes(searchLower),
            );
        }

        // Sort ideas
        const sorted = [...filtered].sort((a, b) => {
            let comparison = 0;

            if (sortBy === 'title') {
                comparison = a.title.localeCompare(b.title);
            } else if (sortBy === 'priority') {
                comparison = priorityOrder[b.priority] - priorityOrder[a.priority];
            } else if (sortBy === 'category') {
                comparison = a.category.localeCompare(b.category);
            }

            return sortOrder === 'asc' ? comparison : -comparison;
        });

        return sorted;
    }, [ideas, category, priority, search, sortBy, sortOrder]);

    // Calculate stats
    const stats = useMemo(() => {
        const total = ideas.length;
        const high = ideas.filter((i) => i.priority === 'high').length;
        const medium = ideas.filter((i) => i.priority === 'medium').length;
        const low = ideas.filter((i) => i.priority === 'low').length;

        const byCategory = ideas.reduce(
            (acc, idea) => {
                acc[idea.category] = (acc[idea.category] || 0) + 1;
                return acc;
            },
            {} as Record<string, number>,
        );

        return { total, high, medium, low, byCategory };
    }, [ideas]);

    return (
        <div className='space-y-6'>
            <div className='flex items-center justify-between'>
                <h2 className='text-2xl font-semibold'>Generated Ideas ({filteredAndSortedIdeas.length})</h2>
                <Button onClick={handleExport} variant='outline' className='gap-2'>
                    <Download className='h-4 w-4' />
                    Export
                </Button>
            </div>

            <IdeaStats {...stats} />

            <IdeaFilters
                category={category}
                priority={priority}
                search={search}
                sortBy={sortBy}
                sortOrder={sortOrder}
                onCategoryChange={handleCategoryChange}
                onPriorityChange={handlePriorityChange}
                onSearchChange={handleSearchChange}
                onSortByChange={handleSortByChange}
                onSortOrderChange={handleSortOrderChange}
                onClearFilters={handleClearFilters}
                activeFilterCount={activeFilterCount}
            />

            <div className='space-y-4'>
                {filteredAndSortedIdeas.length > 0 ? (
                    filteredAndSortedIdeas.map((idea, index) => (
                        <IdeaCard
                            key={index}
                            idea={idea}
                            index={ideas.findIndex((i) => i === idea)}
                            projectId={projectId}
                            brainstormId={brainstormId}
                        />
                    ))
                ) : (
                    <div className='text-center py-12 text-muted-foreground'>
                        <p>No ideas match your filters.</p>
                    </div>
                )}
            </div>
        </div>
    );
}
