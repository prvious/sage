import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { Loader2, Sparkles } from 'lucide-react';

interface ContextInputFormProps {
    projectId: number;
}

export function ContextInputForm({ projectId }: ContextInputFormProps) {
    const { data, setData, post, processing, errors } = useForm({
        user_context: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/projects/${projectId}/brainstorm`, {
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={handleSubmit} className='space-y-4'>
            <div className='space-y-2'>
                <label htmlFor='user_context' className='text-sm font-medium'>
                    Context (Optional)
                </label>
                <Textarea
                    id='user_context'
                    name='user_context'
                    placeholder='Describe what you want to brainstorm about... (max 5000 characters)'
                    value={data.user_context}
                    onChange={(e) => setData('user_context', e.target.value)}
                    rows={6}
                    maxLength={5000}
                    className='resize-none'
                />
                {errors.user_context && <p className='text-sm text-red-600'>{errors.user_context}</p>}
                <p className='text-sm text-muted-foreground'>{data.user_context.length} / 5000 characters</p>
            </div>
            <Button type='submit' disabled={processing} className='w-full'>
                {processing ? (
                    <>
                        <Loader2 className='h-4 w-4 mr-2 animate-spin' />
                        Creating...
                    </>
                ) : (
                    <>
                        <Sparkles className='h-4 w-4 mr-2' />
                        Create Brainstorm
                    </>
                )}
            </Button>
        </form>
    );
}
