import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    Check,
    Copy,
    FolderKanban,
    KeyRound,
    Plus,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClipboard } from '@/hooks/use-clipboard';
import { index, store } from '@/routes/projects';
import { index as issuesIndex } from '@/routes/projects/issues';
import { store as storeKey, update as updateKey } from '@/routes/projects/keys';
import type { Project, ProjectKey as ProjectKeyType } from '@/types';

type Props = {
    projects: Project[];
};

export default function ProjectsIndex({ projects }: Props) {
    const page = usePage();
    const currentTeam = page.props.currentTeam;
    const generatedKey = page.flash.generatedKey;
    const [dismissedKey, setDismissedKey] = useState<string | null>(null);
    const [copiedText, copy] = useClipboard();
    const keyDialogOpen = Boolean(
        generatedKey?.publicKey && dismissedKey !== generatedKey.publicKey,
    );

    if (!currentTeam) {
        return null;
    }

    return (
        <>
            <Head title="Projects" />

            <h1 className="sr-only">Projects</h1>

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Projects"
                    description="Create projects and manage ingest keys for this team."
                />

                <section className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="flex items-start gap-4 p-6">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <FolderKanban className="size-5" />
                        </span>
                        <div className="min-w-0 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Create a project
                            </h2>
                            <p className="text-sm text-muted-foreground text-pretty">
                                A project groups errors from one application.
                                Creating one also generates an ingest key you
                                can use as a Bearer token.
                            </p>
                        </div>
                    </div>

                    <Form
                        {...store.form(currentTeam)}
                        resetOnSuccess
                        options={{ preserveScroll: true }}
                        className="border-t bg-muted/40 p-6"
                    >
                        {({ errors, processing }) => (
                            <div className="flex flex-col gap-4">
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
                                    <div className="grid min-w-0 flex-1 gap-2">
                                        <Label htmlFor="name">
                                            Project name
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            data-test="project-name-input"
                                            placeholder="Production API"
                                            autoComplete="off"
                                            required
                                            className="h-10 bg-background"
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <Button
                                        type="submit"
                                        data-test="create-project-button"
                                        disabled={processing}
                                        className="h-10 shrink-0"
                                    >
                                        {processing ? <Spinner /> : <Plus />}
                                        Create project
                                    </Button>
                                </div>
                                <p className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <KeyRound className="size-3.5 shrink-0" />
                                    Copy the key when it appears — it cannot be
                                    shown again.
                                </p>
                            </div>
                        )}
                    </Form>
                </section>

                <div className="flex flex-col gap-4">
                    {projects.map((project) => (
                        <article
                            key={project.id}
                            data-test="project-card"
                            className="flex flex-col gap-4 rounded-xl border p-4"
                        >
                            <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 className="font-medium">
                                        <Link
                                            href={issuesIndex({
                                                current_team: currentTeam,
                                                project: project.id,
                                            })}
                                            className="hover:underline"
                                            data-test="project-issues-link"
                                        >
                                            {project.name}
                                        </Link>
                                    </h2>
                                    {project.created_at ? (
                                        <p className="text-sm text-muted-foreground">
                                            Created{' '}
                                            {formatDate(project.created_at)}
                                        </p>
                                    ) : null}
                                </div>
                                <Form
                                    {...storeKey.form({
                                        current_team: currentTeam,
                                        project: project.id,
                                    })}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            size="sm"
                                            data-test="generate-key-button"
                                            disabled={processing}
                                        >
                                            <KeyRound />
                                            Generate key
                                        </Button>
                                    )}
                                </Form>
                            </div>

                            {project.keys.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    This project has no keys yet.
                                </p>
                            ) : (
                                <ul className="flex flex-col gap-2">
                                    {project.keys.map((key) => (
                                        <ProjectKeyRow
                                            key={key.id}
                                            projectKey={key}
                                            currentTeam={currentTeam}
                                            projectId={project.id}
                                        />
                                    ))}
                                </ul>
                            )}
                        </article>
                    ))}

                    {projects.length === 0 ? (
                        <div className="rounded-xl border border-dashed py-12 text-center">
                            <p className="font-medium">No projects yet</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Create one above to generate your first ingest
                                key.
                            </p>
                        </div>
                    ) : null}
                </div>
            </div>

            <Dialog
                open={keyDialogOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        setDismissedKey(generatedKey?.publicKey ?? null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Save your project key</DialogTitle>
                        <DialogDescription>
                            Copy this Bearer token now. You will not be able to
                            see it again.
                        </DialogDescription>
                    </DialogHeader>

                    <Alert>
                        <TriangleAlert />
                        <AlertTitle>This key cannot be shown again</AlertTitle>
                        <AlertDescription>
                            It is used as a Bearer token to authenticate ingest
                            requests. Store it somewhere safe — only a hash is
                            kept after you close this dialog.
                        </AlertDescription>
                    </Alert>

                    {generatedKey?.publicKey ? (
                        <div className="flex items-center gap-2">
                            <code
                                data-test="generated-key-value"
                                className="min-w-0 flex-1 overflow-x-auto rounded-md border bg-muted px-3 py-2 font-mono text-sm"
                            >
                                {generatedKey.publicKey}
                            </code>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                data-test="copy-generated-key-button"
                                onClick={() => copy(generatedKey.publicKey)}
                            >
                                {copiedText === generatedKey.publicKey ? (
                                    <Check />
                                ) : (
                                    <Copy />
                                )}
                                <span className="sr-only">Copy key</span>
                            </Button>
                        </div>
                    ) : null}

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button">Done</Button>
                        </DialogClose>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function ProjectKeyRow({
    projectKey,
    currentTeam,
    projectId,
}: {
    projectKey: ProjectKeyType;
    currentTeam: { slug: string };
    projectId: number;
}) {
    return (
        <li
            data-test="project-key-row"
            className="flex flex-col gap-3 rounded-lg border bg-muted/30 p-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <div className="flex items-center gap-2">
                    <code
                        data-test="project-key-prefix"
                        className="font-mono text-sm"
                    >
                        {projectKey.prefix}…
                    </code>
                    <Badge
                        variant={projectKey.is_active ? 'secondary' : 'outline'}
                    >
                        {projectKey.is_active ? 'Active' : 'Revoked'}
                    </Badge>
                </div>
                {projectKey.created_at ? (
                    <span className="text-xs text-muted-foreground">
                        {formatDate(projectKey.created_at)}
                    </span>
                ) : null}
            </div>

            {projectKey.is_active ? (
                <Form
                    {...updateKey.form({
                        current_team: currentTeam,
                        project: projectId,
                        projectKey: projectKey.id,
                    })}
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <div className="flex flex-col items-end gap-1">
                            <Button
                                type="submit"
                                variant="destructive"
                                size="sm"
                                data-test="revoke-key-button"
                                disabled={processing}
                            >
                                Revoke
                            </Button>
                            <InputError message={errors.projectKey} />
                        </div>
                    )}
                </Form>
            ) : null}
        </li>
    );
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

ProjectsIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: props.currentTeam ? index(props.currentTeam) : '/',
        },
    ],
});
