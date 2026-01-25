import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreVertical, FileText, Copy } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { useState } from 'react';
import { createSpec } from '@/actions/App/Http/Controllers/BrainstormController';

interface Idea {
    title: string;
    description: string;
    priority: 'high' | 'medium' | 'low';
    category: 'feature' | 'enhancement' | 'infrastructure' | 'tooling';
}

interface IdeaCardProps {
    idea: Idea;
    index: number;
    projectId: number;
    brainstormId: number;
}

const priorityVariants = {
    high: 'destructive' as const,
    medium: 'default' as const,
    low: 'secondary' as const,
};

export function IdeaCard({ idea, index, projectId, brainstormId }: IdeaCardProps) {
    const [isExpanded, setIsExpanded] = useState(false);
    const isLongDescription = idea.description.length > 200;

    const handleCreateSpec = () => {
        router.post(
            createSpec.url(projectId, brainstormId, index),
            {},
            {
                onSuccess: () => {
                    toast.success('Spec created from idea!');
                },
                onError: () => {
                    toast.error('Failed to create spec');
                },
            },
        );
    };

    const handleCopyToClipboard = () => {
        const text = `# ${idea.title}\n\n${idea.description}\n\nPriority: ${idea.priority}\nCategory: ${idea.category}`;

        navigator.clipboard.writeText(text).then(() => {
            toast.success('Idea copied to clipboard!');
        });
    };

    return (
        <Card className='hover:shadow-md transition-shadow'>
            <CardHeader className='pb-3'>
                <div className='flex items-start justify-between gap-2'>
                    <CardTitle className='text-lg flex-1'>{idea.title}</CardTitle>
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            render={
                                <Button variant='ghost' size='sm'>
                                    <MoreVertical className='h-4 w-4' />
                                </Button>
                            }
                        />
                        <DropdownMenuContent align='end'>
                            <DropdownMenuItem onClick={handleCreateSpec}>
                                <FileText className='mr-2 h-4 w-4' />
                                Create Spec
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={handleCopyToClipboard}>
                                <Copy className='mr-2 h-4 w-4' />
                                Copy to Clipboard
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <div className='flex gap-2 mt-2'>
                    <Badge variant={priorityVariants[idea.priority]}>{idea.priority}</Badge>
                    <Badge variant='outline'>{idea.category}</Badge>
                </div>
            </CardHeader>
            <CardContent>
                <p className={`text-sm text-muted-foreground ${!isExpanded && isLongDescription ? 'line-clamp-3' : ''}`}>{idea.description}</p>
                {isLongDescription && (
                    <Button variant='link' size='sm' className='mt-2 h-auto p-0' onClick={() => setIsExpanded(!isExpanded)}>
                        {isExpanded ? 'Show less' : 'Show more'}
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}
