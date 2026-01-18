import { Head, Link, router } from "@inertiajs/react";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { AgentsIndexProps } from "@/types";
import { AppLayout } from "@/components/layout/app-layout";
import { Bot, ExternalLink, Square } from "lucide-react";

// Helper function to format relative time without date-fns
function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffInSeconds < 60) {
        return `${diffInSeconds} seconds ago`;
    }

    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} minute${diffInMinutes !== 1 ? "s" : ""} ago`;
    }

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} hour${diffInHours !== 1 ? "s" : ""} ago`;
    }

    const diffInDays = Math.floor(diffInHours / 24);
    return `${diffInDays} day${diffInDays !== 1 ? "s" : ""} ago`;
}

export default function Index({ runningAgents }: AgentsIndexProps) {
    const handleStop = (agentId: number) => {
        if (confirm("Are you sure you want to stop this agent?")) {
            router.post(
                `/tasks/${agentId}/stop`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload({ only: ["runningAgents"] });
                    },
                },
            );
        }
    };

    return (
        <>
            <Head title="Running Agents" />
            <AppLayout>
                <div className="p-6 space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">Running Agents</h1>
                        <p className="text-muted-foreground mt-2">
                            Monitor all active agents across all projects
                        </p>
                    </div>

                    {runningAgents.length === 0 ? (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-12">
                                <Bot className="h-12 w-12 text-muted-foreground mb-4" />
                                <p className="text-lg font-medium mb-2">
                                    No Running Agents
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    All agents are idle. Start an agent from a
                                    project dashboard.
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4">
                            {runningAgents.map((agent) => (
                                <Card key={agent.id}>
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <div className="space-y-1">
                                                <CardTitle className="flex items-center gap-2">
                                                    <Bot className="h-5 w-5" />
                                                    Agent #{agent.id}
                                                    <Badge
                                                        variant="default"
                                                        className="ml-2"
                                                    >
                                                        Running
                                                    </Badge>
                                                </CardTitle>
                                                <CardDescription>
                                                    {agent.description ||
                                                        "No description provided"}
                                                </CardDescription>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={`/projects/${agent.project_id}/dashboard`}
                                                        >
                                                            <ExternalLink className="h-4 w-4 mr-2" />
                                                            View Project
                                                        </Link>
                                                    }
                                                />
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() =>
                                                        handleStop(agent.id)
                                                    }
                                                >
                                                    <Square className="h-4 w-4 mr-2" />
                                                    Stop
                                                </Button>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <p className="text-muted-foreground">
                                                    Project
                                                </p>
                                                <p className="font-medium">
                                                    {agent.project_name}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-muted-foreground">
                                                    Model
                                                </p>
                                                <p className="font-medium">
                                                    {agent.model || "N/A"}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-muted-foreground">
                                                    Agent Type
                                                </p>
                                                <p className="font-medium capitalize">
                                                    {agent.agent_type || "N/A"}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-muted-foreground">
                                                    Started
                                                </p>
                                                <p className="font-medium">
                                                    {agent.started_at
                                                        ? formatRelativeTime(
                                                              agent.started_at,
                                                          )
                                                        : "Unknown"}
                                                </p>
                                            </div>
                                        </div>

                                        {agent.agent_output && (
                                            <div className="bg-muted rounded-lg p-4">
                                                <p className="text-xs font-medium mb-2 text-muted-foreground">
                                                    Latest Output
                                                </p>
                                                <pre className="text-xs whitespace-pre-wrap font-mono overflow-x-auto max-h-64">
                                                    {agent.agent_output
                                                        .split("\n")
                                                        .slice(-10)
                                                        .join("\n")}
                                                </pre>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </AppLayout>
        </>
    );
}
