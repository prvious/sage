import { AppLayout } from "@/components/layout/app-layout";
import { Head } from "@inertiajs/react";

interface Spec {
    id: number;
    title: string;
    content: string;
    generated_from_idea: string | null;
    project: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
}

interface Project {
    id: number;
    name: string;
    path: string;
}

interface IndexProps {
    project: Project;
    specs: Spec[];
}

export default function Index({ project, specs }: IndexProps) {
    return (
        <>
            <Head title={`${project.name} - Specs`} />
            <AppLayout>
                <div className="p-6 space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">
                            Feature Specifications
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Manage and generate feature specifications for{" "}
                            {project.name}
                        </p>
                    </div>
                    {/* TODO: Add specs list UI */}
                    <div className="text-muted-foreground">
                        {specs.length > 0
                            ? `${specs.length} spec(s) found. UI coming soon...`
                            : "No specs yet. Create your first spec!"}
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
