import { useCallback, useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { ArrowDown, Pause, Play, Terminal, Copy, Check } from 'lucide-react';

export interface OutputLine {
    content: string;
    type: 'stdout' | 'stderr';
    timestamp?: string;
}

interface AgentOutputViewerProps {
    output: OutputLine[];
    isStreaming?: boolean;
    taskStatus?: 'queued' | 'in_progress' | 'waiting_review' | 'done' | 'failed';
    taskTitle?: string;
    className?: string;
    maxHeight?: string;
    onTogglePause?: () => void;
    isPaused?: boolean;
}

export function AgentOutputViewer({
    output,
    isStreaming = false,
    taskStatus,
    taskTitle,
    className,
    maxHeight = '400px',
    onTogglePause,
    isPaused = false,
}: AgentOutputViewerProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [autoScroll, setAutoScroll] = useState(true);
    const [copied, setCopied] = useState(false);

    const scrollToBottom = useCallback(() => {
        if (containerRef.current && autoScroll) {
            containerRef.current.scrollTop = containerRef.current.scrollHeight;
        }
    }, [autoScroll]);

    useEffect(() => {
        scrollToBottom();
    }, [output, scrollToBottom]);

    const handleScroll = useCallback((e: React.UIEvent<HTMLDivElement>) => {
        const element = e.currentTarget;
        const isAtBottom = element.scrollHeight - element.scrollTop - element.clientHeight < 50;
        setAutoScroll(isAtBottom);
    }, []);

    const handleScrollToBottom = () => {
        setAutoScroll(true);
        if (containerRef.current) {
            containerRef.current.scrollTop = containerRef.current.scrollHeight;
        }
    };

    const handleCopyOutput = async () => {
        const text = output.map((line) => line.content).join('\n');
        await navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const getStatusBadgeVariant = (status?: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
        switch (status) {
            case 'in_progress':
                return 'default';
            case 'done':
                return 'secondary';
            case 'failed':
                return 'destructive';
            default:
                return 'outline';
        }
    };

    const getStatusLabel = (status?: string): string => {
        switch (status) {
            case 'queued':
                return 'Queued';
            case 'in_progress':
                return 'Running';
            case 'waiting_review':
                return 'Review';
            case 'done':
                return 'Done';
            case 'failed':
                return 'Failed';
            default:
                return 'Unknown';
        }
    };

    return (
        <div className={cn('flex flex-col rounded-lg border bg-card', className)}>
            {/* Header */}
            <div className='flex items-center justify-between border-b px-4 py-3'>
                <div className='flex items-center gap-3'>
                    <Terminal className='h-4 w-4 text-muted-foreground' />
                    <span className='text-sm font-medium'>{taskTitle || 'Agent Output'}</span>
                    {taskStatus && <Badge variant={getStatusBadgeVariant(taskStatus)}>{getStatusLabel(taskStatus)}</Badge>}
                    {isStreaming && (
                        <span className='flex items-center gap-1.5'>
                            <span className='relative flex h-2 w-2'>
                                <span className='animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75'></span>
                                <span className='relative inline-flex rounded-full h-2 w-2 bg-green-500'></span>
                            </span>
                            <span className='text-xs text-muted-foreground'>Streaming</span>
                        </span>
                    )}
                </div>
                <div className='flex items-center gap-2'>
                    {onTogglePause && isStreaming && (
                        <Button variant='ghost' size='icon-sm' onClick={onTogglePause} title={isPaused ? 'Resume' : 'Pause'}>
                            {isPaused ? <Play className='h-3.5 w-3.5' /> : <Pause className='h-3.5 w-3.5' />}
                        </Button>
                    )}
                    <Button variant='ghost' size='icon-sm' onClick={handleCopyOutput} title='Copy output'>
                        {copied ? <Check className='h-3.5 w-3.5 text-green-500' /> : <Copy className='h-3.5 w-3.5' />}
                    </Button>
                </div>
            </div>

            {/* Output area */}
            <div className='relative'>
                <div ref={containerRef} className='overflow-auto bg-zinc-950 text-zinc-100 font-mono text-xs' style={{ maxHeight }} onScroll={handleScroll}>
                    <div className='p-4 space-y-0.5'>
                        {output.length === 0 ? (
                            <div className='text-zinc-500 italic'>Waiting for output...</div>
                        ) : (
                            output.map((line, index) => <OutputLineComponent key={index} line={line} />)
                        )}
                        {isStreaming && !isPaused && <StreamingCursor />}
                    </div>
                </div>

                {/* Scroll to bottom button */}
                {!autoScroll && (
                    <Button variant='secondary' size='sm' className='absolute bottom-4 right-4 shadow-lg' onClick={handleScrollToBottom}>
                        <ArrowDown className='h-3.5 w-3.5 mr-1' />
                        Scroll to bottom
                    </Button>
                )}
            </div>

            {/* Footer with stats */}
            <div className='flex items-center justify-between border-t px-4 py-2 text-xs text-muted-foreground'>
                <span>{output.length} lines</span>
                {isStreaming && <span>Auto-scroll: {autoScroll ? 'On' : 'Off'}</span>}
            </div>
        </div>
    );
}

function OutputLineComponent({ line }: { line: OutputLine }) {
    const highlightLine = (content: string, type: 'stdout' | 'stderr') => {
        // Add stderr styling
        if (type === 'stderr') {
            return <span className='text-red-400'>{content}</span>;
        }

        // Apply syntax highlighting for common patterns
        let processed = content;

        // Highlight ANSI codes (remove them for cleaner display)
        // eslint-disable-next-line no-control-regex
        processed = processed.replace(/\x1b\[[0-9;]*m/g, '');

        // Check for specific patterns and apply highlighting
        if (processed.startsWith('Error:') || processed.startsWith('error:')) {
            return <span className='text-red-400'>{processed}</span>;
        }

        if (processed.startsWith('Warning:') || processed.startsWith('warning:')) {
            return <span className='text-yellow-400'>{processed}</span>;
        }

        if (processed.startsWith('Success:') || processed.startsWith('✓') || processed.includes('successfully')) {
            return <span className='text-green-400'>{processed}</span>;
        }

        if (processed.startsWith('>>>') || processed.startsWith('$')) {
            return <span className='text-cyan-400'>{processed}</span>;
        }

        // Highlight file paths
        const pathRegex = /([\/\\][\w\-./\\]+\.(tsx?|jsx?|php|vue|css|html|json|md))/g;
        if (pathRegex.test(processed)) {
            return (
                <span
                    dangerouslySetInnerHTML={{
                        __html: processed.replace(pathRegex, '<span class="text-blue-400">$1</span>'),
                    }}
                />
            );
        }

        return <span className='text-zinc-100'>{processed}</span>;
    };

    return <div className='leading-relaxed whitespace-pre-wrap break-all'>{highlightLine(line.content, line.type)}</div>;
}

function StreamingCursor() {
    return (
        <span className='inline-block w-2 h-4 bg-zinc-100 animate-pulse ml-0.5' style={{ animation: 'blink 1s step-end infinite' }}>
            <style>
                {`
                    @keyframes blink {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0; }
                    }
                `}
            </style>
        </span>
    );
}

// Loading skeleton for the output viewer
export function AgentOutputViewerSkeleton({ className }: { className?: string }) {
    return (
        <div className={cn('flex flex-col rounded-lg border bg-card', className)}>
            <div className='flex items-center justify-between border-b px-4 py-3'>
                <div className='flex items-center gap-3'>
                    <Skeleton className='h-4 w-4 rounded' />
                    <Skeleton className='h-4 w-32' />
                    <Skeleton className='h-5 w-16 rounded-full' />
                </div>
            </div>
            <div className='bg-zinc-950 p-4' style={{ height: '200px' }}>
                <div className='space-y-2'>
                    <Skeleton className='h-4 w-full bg-zinc-800' />
                    <Skeleton className='h-4 w-3/4 bg-zinc-800' />
                    <Skeleton className='h-4 w-5/6 bg-zinc-800' />
                    <Skeleton className='h-4 w-2/3 bg-zinc-800' />
                </div>
            </div>
            <div className='border-t px-4 py-2'>
                <Skeleton className='h-3 w-20' />
            </div>
        </div>
    );
}
