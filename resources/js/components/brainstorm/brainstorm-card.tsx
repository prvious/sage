import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { show } from "@/actions/App/Http/Controllers/BrainstormController";
import { Link } from "@inertiajs/react";
import { CheckCircle2, Clock, AlertCircle, Loader2 } from "lucide-react";

interface Brainstorm {
    id: number;
    project_id: number;
    user_context: string | null;
    ideas: any[] | null;
    status: "pending" | "processing" | "completed" | "failed";
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface BrainstormCardProps {
    brainstorm: Brainstorm;
}

const statusConfig = {
    pending: {
        icon: Clock,
        variant: "secondary" as const,
        label: "Pending",
    },
    processing: {
        icon: Loader2,
        variant: "default" as const,
        label: "Processing",
    },
    completed: {
        icon: CheckCircle2,
        variant: "success" as const,
        label: "Completed",
    },
    failed: {
        icon: AlertCircle,
        variant: "destructive" as const,
        label: "Failed",
    },
};

export function BrainstormCard({ brainstorm }: BrainstormCardProps) {
    const config = statusConfig[brainstorm.status];
    const StatusIcon = config.icon;
    const createdDate = new Date(brainstorm.created_at).toLocaleDateString(
        "en-US",
        {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        },
    );

    return (
        <Link href={show.url([brainstorm.project_id, brainstorm.id])}>
            <Card className="p-4 hover:bg-accent transition-colors cursor-pointer">
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <Badge
                            variant={config.variant}
                            className="flex items-center gap-1"
                        >
                            <StatusIcon
                                className={`h-3 w-3 ${brainstorm.status === "processing" ? "animate-spin" : ""}`}
                            />
                            {config.label}
                        </Badge>
                        <span className="text-xs text-muted-foreground">
                            {createdDate}
                        </span>
                    </div>

                    {brainstorm.user_context && (
                        <p className="text-sm text-muted-foreground line-clamp-2">
                            {brainstorm.user_context}
                        </p>
                    )}

                    {brainstorm.status === "completed" && brainstorm.ideas && (
                        <p className="text-sm font-medium">
                            {brainstorm.ideas.length}{" "}
                            {brainstorm.ideas.length === 1 ? "idea" : "ideas"}{" "}
                            generated
                        </p>
                    )}

                    {brainstorm.status === "failed" &&
                        brainstorm.error_message && (
                            <p className="text-sm text-red-600">
                                {brainstorm.error_message}
                            </p>
                        )}
                </div>
            </Card>
        </Link>
    );
}
