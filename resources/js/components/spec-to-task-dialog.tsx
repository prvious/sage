import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { SharedData, Spec, WorktreeOption } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';

interface SpecToTaskDialogProps {
    spec: Spec;
    project: { id: number; name: string };
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

interface TaskPreview {
    title: string;
    description: string;
}

export function SpecToTaskDialog({ spec, project, open, onOpenChange }: SpecToTaskDialogProps) {
    const { selectedProjectWorktrees } = usePage<SharedData>().props;
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [worktreeId, setWorktreeId] = useState<number | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        if (open && spec?.id && project?.id) {
            setLoading(true);
            setErrors({});

            fetch(`/projects/${project.id}/specs/${spec.id}/preview-task`)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch preview');
                    }
                    return response.json();
                })
                .then((data: TaskPreview) => {
                    setTitle(data.title);
                    setDescription(data.description);
                })
                .catch(() => {
                    setTitle(`Implement: ${spec.title ?? 'Feature'}`);
                    setDescription(spec.content ?? '');
                })
                .finally(() => {
                    setLoading(false);
                });
        }
    }, [open, spec?.id, project?.id, spec?.title, spec?.content]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        router.post(
            `/projects/${project.id}/specs/${spec.id}/create-task`,
            {
                title,
                description,
                worktree_id: worktreeId,
            },
            {
                onSuccess: () => {
                    onOpenChange(false);
                    setTitle('');
                    setDescription('');
                    setWorktreeId(null);
                },
                onError: (formErrors) => {
                    setErrors(formErrors as Record<string, string>);
                },
                onFinish: () => {
                    setSubmitting(false);
                },
            },
        );
    };

    const handleWorktreeChange = (value: string) => {
        setWorktreeId(value === 'none' ? null : parseInt(value, 10));
    };

    const handleClose = () => {
        onOpenChange(false);
        setTitle('');
        setDescription('');
        setWorktreeId(null);
        setErrors({});
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className='sm:max-w-2xl max-h-[90vh] overflow-y-auto'>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Create Task from Spec</DialogTitle>
                        <DialogDescription>
                            Create a new task based on <span className='font-medium'>{spec.title}</span>. The description below will be used as the agent
                            prompt.
                        </DialogDescription>
                    </DialogHeader>
                    {loading ? (
                        <div className='py-8 flex items-center justify-center'>
                            <Loader2 className='h-6 w-6 animate-spin text-muted-foreground' />
                            <span className='ml-2 text-muted-foreground'>Generating task prompt...</span>
                        </div>
                    ) : (
                        <div className='py-4 space-y-4'>
                            <div className='space-y-2'>
                                <Label htmlFor='task-title'>Title</Label>
                                <Input
                                    id='task-title'
                                    placeholder='Task title'
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    className={errors.title ? 'border-destructive' : ''}
                                />
                                {errors.title && <p className='text-sm text-destructive'>{errors.title}</p>}
                            </div>
                            <div className='space-y-2'>
                                <Label htmlFor='task-description'>
                                    Agent Prompt <span className='text-destructive'>*</span>
                                </Label>
                                <Textarea
                                    id='task-description'
                                    placeholder='Instructions for the agent...'
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    rows={12}
                                    className={`font-mono text-sm ${errors.description ? 'border-destructive' : ''}`}
                                />
                                {errors.description && <p className='text-sm text-destructive'>{errors.description}</p>}
                                <p className='text-xs text-muted-foreground'>
                                    This prompt will be sent to the AI agent to implement the feature. You can edit it to add more specific instructions.
                                </p>
                            </div>
                            {selectedProjectWorktrees && selectedProjectWorktrees.length > 0 && (
                                <div className='space-y-2'>
                                    <Label htmlFor='task-worktree'>Worktree (optional)</Label>
                                    <Select value={worktreeId?.toString() ?? 'none'} onValueChange={handleWorktreeChange}>
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
                    )}
                    <DialogFooter className='gap-2 sm:gap-0'>
                        <Button type='button' variant='outline' onClick={handleClose} disabled={submitting}>
                            Cancel
                        </Button>
                        <Button type='submit' disabled={loading || submitting || !description?.trim()}>
                            {submitting ? (
                                <>
                                    <Loader2 className='mr-2 h-4 w-4 animate-spin' />
                                    Creating...
                                </>
                            ) : (
                                'Create Task'
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
