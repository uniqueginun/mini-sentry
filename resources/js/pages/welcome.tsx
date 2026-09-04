import { Head, Link, usePage } from '@inertiajs/react';
import {
    CircleCheck,
    FolderKanban,
    GitBranch,
    KeyRound,
    Layers,
    TriangleAlert,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import SeverityBadge from '@/components/issues/severity-badge';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, register } from '@/routes';

const features = [
    {
        title: 'Grouped issues',
        description:
            'Similar errors collapse into one issue so you can see volume, last seen, and the latest stack trace in one place.',
        icon: Layers,
    },
    {
        title: 'Projects and keys',
        description:
            'Create a project per app, then ingest events with a Bearer token. Keys can be generated and revoked at any time.',
        icon: KeyRound,
    },
    {
        title: 'Teams that share context',
        description:
            'Invite teammates, switch teams, and keep projects scoped so the right people see the right errors.',
        icon: FolderKanban,
    },
    {
        title: 'Environments and releases',
        description:
            'Filter by environment or release to tell a production outage from a noisy staging deploy.',
        icon: GitBranch,
    },
    {
        title: 'Severity that stands out',
        description:
            'Fatal, error, warning, and info are labeled so you can triage the crash before the log noise.',
        icon: TriangleAlert,
    },
    {
        title: 'Resolve when it is done',
        description:
            'Mark an issue resolved when the fix ships, and keep the rest of the queue focused on what is still broken.',
        icon: CircleCheck,
    },
];

const steps = [
    {
        step: '1',
        title: 'Create a project',
        description: 'Name the app you want to monitor. An ingest key is generated for you.',
    },
    {
        step: '2',
        title: 'Send an event',
        description:
            'POST to /api/events with your Bearer token. Events are grouped into issues automatically.',
    },
    {
        step: '3',
        title: 'Triage and resolve',
        description:
            'Open the issue, inspect the latest event, and resolve it when the fix is in.',
    },
];

const sampleIssues = [
    {
        title: 'TypeError: Cannot read properties of undefined',
        meta: 'production · 24 events',
        time: '2h ago',
        severity: 'error',
    },
    {
        title: 'QueryException: SQLSTATE[23000] Integrity constraint',
        meta: 'production · 8 events',
        time: '6h ago',
        severity: 'fatal',
    },
    {
        title: 'HttpException: 429 Too Many Requests',
        meta: 'staging · 3 events',
        time: '1d ago',
        severity: 'warning',
    },
];

export default function Welcome() {
    const { auth, currentTeam, name } = usePage().props;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';

    return (
        <>
            <Head title="Error tracking for small teams" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="border-b">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-6">
                        <Link
                            href={home()}
                            className="flex items-center gap-2 font-semibold"
                        >
                            <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-5 fill-current" />
                            </span>
                            <span>{name}</span>
                        </Link>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboardUrl}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>
                                            Get started
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:py-24">
                        <div className="flex flex-col gap-6">
                            <p className="text-sm font-medium text-muted-foreground">
                                Open-source error monitoring
                            </p>
                            <h1 className="text-4xl font-semibold tracking-tight text-balance lg:text-5xl">
                                Catch the crash before your users do
                            </h1>
                            <p className="max-w-lg text-lg text-muted-foreground text-pretty">
                                {name} groups exceptions into issues, tracks
                                volume over time, and gives your team a place to
                                triage production errors without the noise of a
                                raw log dump.
                            </p>
                            <div className="flex flex-wrap items-center gap-3">
                                {auth.user ? (
                                    <Button size="lg" asChild>
                                        <Link href={dashboardUrl}>
                                            Open dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button size="lg" asChild>
                                            <Link href={register()}>
                                                Create a free account
                                            </Link>
                                        </Button>
                                        <Button
                                            size="lg"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link href={login()}>Log in</Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="rounded-xl border bg-card p-4 shadow-sm">
                            <div className="mb-3 flex items-center justify-between gap-2">
                                <p className="text-sm font-medium">
                                    Unresolved issues
                                </p>
                                <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                    production
                                </span>
                            </div>
                            <ul className="flex flex-col gap-2">
                                {sampleIssues.map((issue) => (
                                    <li
                                        key={issue.title}
                                        className="flex flex-col gap-1 rounded-lg border bg-background p-3"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="min-w-0 truncate font-medium">
                                                {issue.title}
                                            </p>
                                            <SeverityBadge
                                                level={issue.severity}
                                            />
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {issue.meta} · {issue.time}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </section>

                    <section className="border-t bg-muted/40">
                        <div className="mx-auto grid w-full max-w-6xl gap-6 px-6 py-16 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature) => (
                                <article
                                    key={feature.title}
                                    className="flex flex-col gap-3 rounded-xl border bg-card p-5 shadow-sm"
                                >
                                    <span className="flex size-9 items-center justify-center rounded-md bg-primary/10 text-primary">
                                        <feature.icon className="size-4" />
                                    </span>
                                    <h2 className="font-medium">
                                        {feature.title}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {feature.description}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-16">
                        <div className="max-w-2xl">
                            <h2 className="text-2xl font-semibold tracking-tight">
                                From ingest to resolved in three steps
                            </h2>
                            <p className="mt-2 text-muted-foreground">
                                No agents to babysit. Create a project, send
                                events, and work the queue.
                            </p>
                        </div>
                        <ol className="grid gap-6 md:grid-cols-3">
                            {steps.map((item) => (
                                <li
                                    key={item.step}
                                    className="flex flex-col gap-2 rounded-xl border p-5"
                                >
                                    <span className="text-sm font-medium text-muted-foreground">
                                        Step {item.step}
                                    </span>
                                    <h3 className="font-medium">
                                        {item.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {item.description}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>

                    <section className="border-t">
                        <div className="mx-auto flex w-full max-w-6xl flex-col items-start justify-between gap-6 px-6 py-16 sm:flex-row sm:items-center">
                            <div className="max-w-xl">
                                <h2 className="text-2xl font-semibold tracking-tight">
                                    Start tracking errors today
                                </h2>
                                <p className="mt-2 text-muted-foreground">
                                    Create a team, add a project, and send your
                                    first event. {name} keeps the rest of the
                                    queue ready for you.
                                </p>
                            </div>
                            {auth.user ? (
                                <Button size="lg" asChild>
                                    <Link href={dashboardUrl}>
                                        Go to dashboard
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="lg" asChild>
                                    <Link href={register()}>Get started</Link>
                                </Button>
                            )}
                        </div>
                    </section>
                </main>

                <footer className="border-t">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-6 text-sm text-muted-foreground">
                        <span>{name}</span>
                        <span>Error tracking for teams</span>
                    </div>
                </footer>
            </div>
        </>
    );
}
