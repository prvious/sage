import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useQuickTask } from '@/contexts/quick-task-context';
import { useKeyboardShortcut } from '@/hooks/use-keyboard-shortcut';
import { SharedData, WorktreeOption } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

export function GlobalQuickTaskDialog() {
    const { isOpen, close, toggle } = useQuickTask();
    const { selectedProject, selectedProjectWorktrees } = usePage<SharedData>().props;
    const descriptionRef = useRef<HTMLTextAreaElement>(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        project_id: selectedProject?.id ?? 0,
        title: '',
        description: '',
        worktree_id: null as number | null,
    });

    // Register Cmd+K / Ctrl+K keyboard shortcut
    useKeyboardShortcut({
        key: 'k',
        modifiers: ['meta'],
        callback: toggle,
    });

    // Also support Ctrl+K for non-Mac users
    useKeyboardShortcut({
        key: 'k',
        modifiers: ['ctrl'],
        callback: toggle,
    });

    // Update project_id when selectedProject changes
    useEffect(() => {
        if (selectedProject) {
            setData('project_id', selectedProject.id);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedProject]);
    // setData is a stable function from useForm and doesn't need to be in dependencies

    // Focus description field when dialog opens
    useEffect(() => {
        if (isOpen && descriptionRef.current) {
            setTimeout(() => {
                descriptionRef.current?.focus();
            }, 100);
        }
    }, [isOpen]);

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            close();
            reset();
            clearErrors();
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!selectedProject) {
            return;
        }

        post('/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                clearErrors();
                close();
            },
        });
    };

    const handleDescriptionChange = (value: string) => {
        setData('description', value);
        // Auto-generate title from first line if title is empty
        if (!data.title && value) {
            const firstLine = value.split('\n')[0];
            setData('title', firstLine.substring(0, 100));
        }
    };

    const handleWorktreeChange = (value: string) => {
        setData('worktree_id', value === 'none' ? null : parseInt(value, 10));
    };

    // Render nothing if no project is selected
    if (!selectedProject) {
        return null;
    }

    return (
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
            <DialogContent className='sm:max-w-lg'>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Quick Task</DialogTitle>
                        <DialogDescription>
                            Create a new task for <span className='font-medium'>{selectedProject.name}</span>. Press{' '}
                            <kbd className='bg-muted text-muted-foreground pointer-events-none inline-flex h-5 items-center gap-1 rounded border px-1.5 font-mono text-[10px] font-medium'>
                                <span className='text-xs'>Esc</span>
                            </kbd>{' '}
                            to close.
                        </DialogDescription>
                    </DialogHeader>
                    <div className='py-4 space-y-4'>
                        <div className='space-y-2'>
                            <Label htmlFor='quick-task-title'>Title (optional)</Label>
                            <Input
                                id='quick-task-title'
                                placeholder='Auto-generated from description if empty'
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className={errors.title ? 'border-destructive' : ''}
                            />
                            {errors.title && <p className='text-sm text-destructive'>{errors.title}</p>}
                        </div>
                        <div className='space-y-2'>
                            <Label htmlFor='quick-task-description'>
                                Description <span className='text-destructive'>*</span>
                            </Label>
                            <Textarea
                                ref={descriptionRef}
                                id='quick-task-description'
                                placeholder='Describe what you want to accomplish...'
                                value={data.description}
                                onChange={(e) => handleDescriptionChange(e.target.value)}
                                rows={4}
                                className={errors.description ? 'border-destructive' : ''}
                            />
                            {errors.description && <p className='text-sm text-destructive'>{errors.description}</p>}
                        </div>
                        {selectedProjectWorktrees.length > 0 && (
                            <div className='space-y-2'>
                                <Label htmlFor='quick-task-worktree'>Worktree (optional)</Label>
                                <Select value={data.worktree_id?.toString() ?? 'none'} onValueChange={handleWorktreeChange}>
                                    <SelectTrigger className='w-full'>
                                        <SelectValue placeholder='Select a worktree' />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value='none'>No worktree (main branch)</SelectItem>
                                        {selectedProjectWorktrees.map((worktree: WorktreeOption) => (
                                            <SelectItem key={worktree.id} value={worktree.id.toString()}>
                                                {worktree.branch_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.worktree_id && <p className='text-sm text-destructive'>{errors.worktree_id}</p>}
                            </div>
                        )}
                    </div>
                    <DialogFooter className='gap-2 sm:gap-0'>
                        <Button type='button' variant='outline' onClick={() => handleOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type='submit' disabled={processing || !data.description.trim()}>
                            {processing ? 'Creating...' : 'Create Task'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
