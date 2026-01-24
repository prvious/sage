import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Sparkles } from 'lucide-react';

interface ContextInputFormProps {
    projectId: number;
}

export function ContextInputForm({ projectId }: ContextInputFormProps) {
    const { data, setData, post, processing, errors } = useForm({
        user_context: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/projects/${projectId}/brainstorm`);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Generate Feature Ideas</CardTitle>
                <CardDescription>Provide optional context to help the AI generate relevant ideas for your project</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className='space-y-4'>
                    <div className='space-y-2'>
                        <Label htmlFor='user_context'>Context (Optional)</Label>
                        <Textarea
                            id='user_context'
                            value={data.user_context}
                            onChange={(e) => setData('user_context', e.target.value)}
                            placeholder='e.g., Focus on user experience improvements, performance optimizations, or developer tooling...'
                            className='min-h-[120px]'
                            maxLength={5000}
                        />
                        {errors.user_context && <p className='text-sm text-destructive'>{errors.user_context}</p>}
                        <p className='text-xs text-muted-foreground'>{data.user_context.length} / 5000 characters</p>
                    </div>
                    <Button type='submit' disabled={processing} className='w-full'>
                        <Sparkles className='h-4 w-4 mr-2' />
                        {processing ? 'Starting...' : 'Generate Ideas'}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
