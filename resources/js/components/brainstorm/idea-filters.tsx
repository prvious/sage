import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, X } from 'lucide-react';

interface IdeaFiltersProps {
    category: string;
    priority: string;
    search: string;
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    onCategoryChange: (value: string) => void;
    onPriorityChange: (value: string) => void;
    onSearchChange: (value: string) => void;
    onSortByChange: (value: string) => void;
    onSortOrderChange: (value: 'asc' | 'desc') => void;
    onClearFilters: () => void;
    activeFilterCount: number;
}

export function IdeaFilters({
    category,
    priority,
    search,
    sortBy,
    sortOrder,
    onCategoryChange,
    onPriorityChange,
    onSearchChange,
    onSortByChange,
    onSortOrderChange,
    onClearFilters,
    activeFilterCount,
}: IdeaFiltersProps) {
    return (
        <div className='space-y-4 mb-6'>
            <div className='flex flex-col sm:flex-row gap-3'>
                <div className='flex-1 relative'>
                    <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
                    <Input type='text' placeholder='Search ideas...' value={search} onChange={(e) => onSearchChange(e.target.value)} className='pl-9' />
                </div>

                <Select value={category} onValueChange={onCategoryChange}>
                    <SelectTrigger className='w-full sm:w-[180px]'>
                        <SelectValue placeholder='Category' />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value='all'>All Categories</SelectItem>
                        <SelectItem value='feature'>Features</SelectItem>
                        <SelectItem value='enhancement'>Enhancements</SelectItem>
                        <SelectItem value='infrastructure'>Infrastructure</SelectItem>
                        <SelectItem value='tooling'>Tooling</SelectItem>
                    </SelectContent>
                </Select>

                <Select value={priority} onValueChange={onPriorityChange}>
                    <SelectTrigger className='w-full sm:w-[180px]'>
                        <SelectValue placeholder='Priority' />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value='all'>All Priorities</SelectItem>
                        <SelectItem value='high'>High</SelectItem>
                        <SelectItem value='medium'>Medium</SelectItem>
                        <SelectItem value='low'>Low</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className='flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between'>
                <div className='flex gap-2 items-center'>
                    <Select value={sortBy} onValueChange={onSortByChange}>
                        <SelectTrigger className='w-[180px]'>
                            <SelectValue placeholder='Sort by' />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value='title'>Title</SelectItem>
                            <SelectItem value='priority'>Priority</SelectItem>
                            <SelectItem value='category'>Category</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button variant='outline' size='sm' onClick={() => onSortOrderChange(sortOrder === 'asc' ? 'desc' : 'asc')}>
                        {sortOrder === 'asc' ? '↑' : '↓'}
                    </Button>
                </div>

                {activeFilterCount > 0 && (
                    <Button variant='ghost' size='sm' onClick={onClearFilters} className='gap-1'>
                        <X className='h-4 w-4' />
                        Clear Filters
                        <Badge variant='secondary' className='ml-1'>
                            {activeFilterCount}
                        </Badge>
                    </Button>
                )}
            </div>
        </div>
    );
}
