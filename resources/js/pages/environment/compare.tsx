import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ArrowLeft, AlertCircle } from 'lucide-react';

interface EnvVariable {
    value: string;
    comment?: string | null;
    is_sensitive: boolean;
}

interface Source {
    id: number;
    name: string;
    type: string;
    variables: Record<string, EnvVariable>;
}

interface Differences {
    added: Record<string, EnvVariable>;
    removed: Record<string, EnvVariable>;
    changed: Record<
        string,
        {
            source: EnvVariable;
            target: EnvVariable;
        }
    >;
    unchanged: Record<string, EnvVariable>;
}

interface Props {
    source: Source;
    target: Source;
    differences: Differences;
    error?: string;
}

export default function Compare({ source, target, differences, error }: Props) {
    return (
        <>
            <Head title='Compare Environment Files' />

            <div className='min-h-screen bg-gray-50 dark:bg-gray-900'>
                <div className='mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8'>
                    <div className='mb-8'>
                        <Link
                            href='/environment'
                            className='mb-4 inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                        >
                            <ArrowLeft className='mr-2 h-4 w-4' />
                            Back to Environment Manager
                        </Link>

                        <h1 className='text-3xl font-bold text-gray-900 dark:text-gray-100'>Compare Environment Files</h1>
                        <div className='mt-4 flex gap-4'>
                            <div className='flex items-center gap-2'>
                                <Badge variant='default'>{source.type}</Badge>
                                <span className='text-sm text-gray-600 dark:text-gray-400'>{source.name}</span>
                            </div>
                            <span className='text-gray-400'>vs</span>
                            <div className='flex items-center gap-2'>
                                <Badge variant='secondary'>{target.type}</Badge>
                                <span className='text-sm text-gray-600 dark:text-gray-400'>{target.name}</span>
                            </div>
                        </div>
                    </div>

                    {error && (
                        <Alert variant='destructive' className='mb-6'>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    {!error && (
                        <div className='space-y-6'>
                            {Object.keys(differences.removed).length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <div className='flex items-center gap-2'>
                                            <CardTitle>Removed Variables</CardTitle>
                                            <Badge variant='destructive' className='text-xs'>
                                                {Object.keys(differences.removed).length}
                                            </Badge>
                                        </div>
                                        <p className='text-sm text-gray-600 dark:text-gray-400'>
                                            Present in {source.name} but not in {target.name}
                                        </p>
                                    </CardHeader>
                                    <CardContent>
                                        <div className='space-y-2'>
                                            {Object.entries(differences.removed).map(([key, data]) => (
                                                <div key={key} className='rounded border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20'>
                                                    <div className='flex items-center justify-between'>
                                                        <span className='font-mono text-sm font-medium'>{key}</span>
                                                        {data.is_sensitive && (
                                                            <Badge variant='outline' className='text-xs'>
                                                                Sensitive
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <span className='mt-1 block font-mono text-xs text-gray-600 dark:text-gray-400'>
                                                        {data.is_sensitive ? '••••••••' : data.value}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {Object.keys(differences.added).length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <div className='flex items-center gap-2'>
                                            <CardTitle>Added Variables</CardTitle>
                                            <Badge variant='default' className='text-xs'>
                                                {Object.keys(differences.added).length}
                                            </Badge>
                                        </div>
                                        <p className='text-sm text-gray-600 dark:text-gray-400'>
                                            Present in {target.name} but not in {source.name}
                                        </p>
                                    </CardHeader>
                                    <CardContent>
                                        <div className='space-y-2'>
                                            {Object.entries(differences.added).map(([key, data]) => (
                                                <div
                                                    key={key}
                                                    className='rounded border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20'
                                                >
                                                    <div className='flex items-center justify-between'>
                                                        <span className='font-mono text-sm font-medium'>{key}</span>
                                                        {data.is_sensitive && (
                                                            <Badge variant='outline' className='text-xs'>
                                                                Sensitive
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <span className='mt-1 block font-mono text-xs text-gray-600 dark:text-gray-400'>
                                                        {data.is_sensitive ? '••••••••' : data.value}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {Object.keys(differences.changed).length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <div className='flex items-center gap-2'>
                                            <CardTitle>Changed Variables</CardTitle>
                                            <Badge className='text-xs'>{Object.keys(differences.changed).length}</Badge>
                                        </div>
                                        <p className='text-sm text-gray-600 dark:text-gray-400'>
                                            Different values between {source.name} and {target.name}
                                        </p>
                                    </CardHeader>
                                    <CardContent>
                                        <div className='space-y-4'>
                                            {Object.entries(differences.changed).map(([key, data]) => (
                                                <div
                                                    key={key}
                                                    className='space-y-2 rounded border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-800 dark:bg-yellow-900/20'
                                                >
                                                    <div className='flex items-center justify-between'>
                                                        <span className='font-mono text-sm font-medium'>{key}</span>
                                                        {data.source.is_sensitive && (
                                                            <Badge variant='outline' className='text-xs'>
                                                                Sensitive
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    <div className='grid gap-2 md:grid-cols-2'>
                                                        <div>
                                                            <span className='text-xs text-gray-600 dark:text-gray-400'>{source.name}</span>
                                                            <div className='mt-1 font-mono text-xs text-gray-700 dark:text-gray-300'>
                                                                {data.source.is_sensitive ? '••••••••' : data.source.value}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span className='text-xs text-gray-600 dark:text-gray-400'>{target.name}</span>
                                                            <div className='mt-1 font-mono text-xs text-gray-700 dark:text-gray-300'>
                                                                {data.target.is_sensitive ? '••••••••' : data.target.value}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {Object.keys(differences.changed).length === 0 &&
                                Object.keys(differences.added).length === 0 &&
                                Object.keys(differences.removed).length === 0 && (
                                    <Card>
                                        <CardContent className='p-12 text-center'>
                                            <p className='text-gray-600 dark:text-gray-400'>
                                                No differences found. Both environment files have identical variables.
                                            </p>
                                        </CardContent>
                                    </Card>
                                )}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
