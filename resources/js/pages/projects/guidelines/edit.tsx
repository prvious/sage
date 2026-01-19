import { Head, Link, useForm } from "@inertiajs/react";
import { AppLayout } from "@/components/layout/app-layout";
import { GuidelineEditProps } from "@/types";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { AlertCircle, Save, X } from "lucide-react";
import MDEditor from "@uiw/react-md-editor";
import { useState } from "react";

export default function Edit({
    project,
    filename,
    content,
}: GuidelineEditProps) {
    const { data, setData, put, processing, errors } = useForm({
        filename: filename,
        content: content,
    });

    const [editorValue, setEditorValue] = useState(content);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/projects/${project.id}/guidelines/${filename}`);
    };

    return (
        <>
            <Head title={`Edit ${filename} - ${project.name}`} />
            <AppLayout>
                <div className="p-6 space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">
                                Edit Custom Guideline
                            </h1>
                            <p className="text-muted-foreground mt-2">
                                Editing: {filename}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={`/projects/${project.id}/guidelines`}
                                >
                                    Cancel
                                </Link>
                            }
                        >
                            <X className="size-4 mr-2" />
                            Cancel
                        </Button>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle>Guideline Content</CardTitle>
                                <CardDescription>
                                    Edit the content for this custom guideline
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="content">Content</Label>
                                    <div data-color-mode="light">
                                        <MDEditor
                                            value={editorValue}
                                            onChange={(value) => {
                                                setEditorValue(value || "");
                                                setData("content", value || "");
                                            }}
                                            height={500}
                                            preview="edit"
                                        />
                                    </div>
                                    {errors.content && (
                                        <p className="text-sm text-destructive mt-2">
                                            {errors.content}
                                        </p>
                                    )}
                                </div>

                                {errors.error && (
                                    <Alert variant="destructive">
                                        <AlertCircle className="size-4" />
                                        <AlertDescription>
                                            {errors.error}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                <div className="flex justify-end gap-2 pt-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        render={
                                            <Link
                                                href={`/projects/${project.id}/guidelines`}
                                            >
                                                Cancel
                                            </Link>
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="size-4 mr-2" />
                                        {processing
                                            ? "Saving..."
                                            : "Save Changes"}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                </div>
            </AppLayout>
        </>
    );
}
