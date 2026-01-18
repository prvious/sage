import { AppLayout } from "@/components/layout/app-layout";
import { Head } from "@inertiajs/react";

interface Project {
    id: number;
    name: string;
    path: string;
}

interface CreateProps {
    project: Project;
}

export default function Create({ project }: CreateProps) {
    return (
        <>
            <Head title={`${project.name} - Create Spec`} />
            <AppLayout>
                <div className="p-6 space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">
                            Create Specification
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Generate a new feature specification for{" "}
                            {project.name}
                        </p>
                    </div>
                    {/* TODO: Add spec creation form */}
                    <div className="text-muted-foreground">
                        Spec creation form coming soon...
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
