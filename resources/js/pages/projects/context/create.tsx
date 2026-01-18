import { Head, Link, useForm } from "@inertiajs/react";
import { AppLayout } from "@/components/layout/app-layout";
import { ContextCreateProps } from "@/types";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { AlertCircle, Save, X } from "lucide-react";
import MDEditor from "@uiw/react-md-editor";
import { useState } from "react";

export default function Create({ project }: ContextCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        filename: "",
        content: "",
    });

    const [editorValue, setEditorValue] = useState("");

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/projects/${project.id}/context`);
    };

    return (
        <>
            <Head title={`Create Context File - ${project.name}`} />
            <AppLayout>
                <div className="p-6 space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">
                                Create Context File
                            </h1>
                            <p className="text-muted-foreground mt-2">
                                Create a new .ai/ markdown file for custom agent
                                rules
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            render={
                                <Link href={`/projects/${project.id}/context`}>
                                    Cancel
                                </Link>
                            }
                        >
                            <X className="h-4 w-4 mr-2" />
                            Cancel
                        </Button>
                    </div>

                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            The filename will be automatically appended with .md
                            if not provided. Only alphanumeric characters,
                            dashes, and underscores are allowed.
                        </AlertDescription>
                    </Alert>

                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle>File Details</CardTitle>
                                <CardDescription>
                                    Enter the filename and content for your new
                                    context file
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="filename">Filename</Label>
                                    <Input
                                        id="filename"
                                        type="text"
                                        placeholder="e.g., custom-rules or custom-rules.md"
                                        value={data.filename}
                                        onChange={(e) =>
                                            setData("filename", e.target.value)
                                        }
                                        className={
                                            errors.filename
                                                ? "border-destructive"
                                                : ""
                                        }
                                    />
                                    {errors.filename && (
                                        <p className="text-sm text-destructive">
                                            {errors.filename}
                                        </p>
                                    )}
                                </div>

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
                                        <AlertCircle className="h-4 w-4" />
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
                                                href={`/projects/${project.id}/context`}
                                            >
                                                Cancel
                                            </Link>
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {processing
                                            ? "Creating..."
                                            : "Create File"}
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
