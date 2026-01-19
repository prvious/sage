import { useState } from 'react';
import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Eye, EyeOff, Plus, Trash2 } from 'lucide-react';

interface EnvVariable {
    value: string;
    comment?: string | null;
    is_sensitive: boolean;
}

interface Props {
    grouped: Record<string, Record<string, EnvVariable>>;
    envPath: string;
    projectId: number;
    onSuccess?: () => void;
}

export default function EnvVariableForm({ grouped, envPath, projectId, onSuccess }: Props) {
    const [variables, setVariables] = useState(() => {
        const flattened: Record<string, EnvVariable> = {};
        Object.values(grouped).forEach((section) => {
            Object.entries(section).forEach(([key, value]) => {
                flattened[key] = value;
            });
        });
        return flattened;
    });

    const [revealedKeys, setRevealedKeys] = useState<Set<string>>(new Set());
    const [newKey, setNewKey] = useState('');
    const [newValue, setNewValue] = useState('');

    const toggleReveal = (key: string) => {
        const newRevealed = new Set(revealedKeys);
        if (newRevealed.has(key)) {
            newRevealed.delete(key);
        } else {
            newRevealed.add(key);
        }
        setRevealedKeys(newRevealed);
    };

    const updateVariable = (key: string, value: string) => {
        setVariables({
            ...variables,
            [key]: {
                ...variables[key],
                value,
            },
        });
    };

    const deleteVariable = (key: string) => {
        if (confirm(`Are you sure you want to delete ${key}?`)) {
            const newVariables = { ...variables };
            delete newVariables[key];
            setVariables(newVariables);
        }
    };

    const addVariable = () => {
        if (newKey && newValue) {
            setVariables({
                ...variables,
                [newKey]: {
                    value: newValue,
                    is_sensitive: false,
                },
            });
            setNewKey('');
            setNewValue('');
        }
    };

    return (
        <Form action={`/projects/${projectId}/environment`} method='put' data={{ variables }} onSuccess={() => onSuccess?.()}>
            {({ processing }) => (
                <div className='space-y-6'>
                    {Object.entries(grouped).map(([section, sectionVars]) => (
                        <Card key={section}>
                            <CardHeader>
                                <CardTitle className='text-lg'>{section}</CardTitle>
                            </CardHeader>
                            <CardContent className='space-y-4'>
                                {Object.entries(sectionVars).map(([key, data]) => (
                                    <div key={key} className='space-y-2'>
                                        <div className='flex items-center justify-between'>
                                            <Label htmlFor={key} className='flex items-center gap-2'>
                                                {key}
                                                {data.is_sensitive && (
                                                    <Badge variant='secondary' className='text-xs'>
                                                        Sensitive
                                                    </Badge>
                                                )}
                                            </Label>
                                            <Button type='button' variant='ghost' size='sm' onClick={() => deleteVariable(key)}>
                                                <Trash2 className='h-4 w-4' />
                                            </Button>
                                        </div>
                                        {data.comment && <p className='text-xs text-gray-500'>{data.comment}</p>}
                                        <div className='flex gap-2'>
                                            <Input
                                                id={key}
                                                type={data.is_sensitive && !revealedKeys.has(key) ? 'password' : 'text'}
                                                value={variables[key]?.value || data.value}
                                                onChange={(e) => updateVariable(key, e.target.value)}
                                                className='flex-1'
                                            />
                                            {data.is_sensitive && (
                                                <Button type='button' variant='outline' size='icon' onClick={() => toggleReveal(key)}>
                                                    {revealedKeys.has(key) ? <EyeOff className='h-4 w-4' /> : <Eye className='h-4 w-4' />}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ))}

                    <Card>
                        <CardHeader>
                            <CardTitle className='text-lg'>Add New Variable</CardTitle>
                        </CardHeader>
                        <CardContent className='space-y-4'>
                            <div className='space-y-2'>
                                <Label htmlFor='new-key'>Key</Label>
                                <Input id='new-key' value={newKey} onChange={(e) => setNewKey(e.target.value.toUpperCase())} placeholder='NEW_VARIABLE' />
                            </div>
                            <div className='space-y-2'>
                                <Label htmlFor='new-value'>Value</Label>
                                <Input id='new-value' value={newValue} onChange={(e) => setNewValue(e.target.value)} placeholder='value' />
                            </div>
                            <Button type='button' onClick={addVariable} variant='outline' className='w-full'>
                                <Plus className='mr-2 h-4 w-4' />
                                Add Variable
                            </Button>
                        </CardContent>
                    </Card>

                    <Separator />

                    <div className='flex justify-end gap-4'>
                        <Button type='submit' disabled={processing} className='bg-blue-600 hover:bg-blue-700'>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
