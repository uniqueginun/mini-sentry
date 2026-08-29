import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Check } from 'lucide-react';
import SeverityBadge from '@/components/issues/severity-badge';
import Sparkline from '@/components/issues/sparkline';
import StatusBadge from '@/components/issues/status-badge';
import ProjectNav from '@/components/projects/project-nav';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatDateTime,
    formatNumber,
    formatRelativeTime,
} from '@/lib/datetime';
import { cn } from '@/lib/utils';
import { index as projectsIndex } from '@/routes/projects';
import { index, resolve, show } from '@/routes/projects/issues';
import type {
    Breadcrumb,
    IssueDetail,
    LatestEvent,
    ProjectSummary,
    StackFrame,
} from '@/types';

type Props = {
    project: ProjectSummary;
    issue: IssueDetail;
};

export default function IssueShow({ project, issue }: Props) {
    const page = usePage();
    const currentTeam = page.props.currentTeam;

    if (!currentTeam) {
        return null;
    }

    const routeArgs = {
        current_team: currentTeam,
        project: project.id,
        issue: issue.id,
    };
    const latestEvent = issue.latest_event;

    return (
        <>
            <Head title={issue.title} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-1">
                    <p className="text-sm text-muted-foreground">
                        <Link
                            href={projectsIndex(currentTeam)}
                            className="hover:text-foreground"
                        >
                            Projects
                        </Link>
                        <span className="mx-1.5">/</span>
                        <Link
                            href={index({
                                current_team: currentTeam,
                                project: project.id,
                            })}
                            className="hover:text-foreground"
                        >
                            {project.name}
                        </Link>
                    </p>
                    <h1 className="sr-only">{issue.title}</h1>
                </div>

                <ProjectNav
                    currentTeam={currentTeam}
                    project={project}
                    current="issues"
                />

                <div className="flex flex-col gap-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Link
                            href={index({
                                current_team: currentTeam,
                                project: project.id,
                            })}
                            className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="size-4" />
                            Issues
                        </Link>

                        <div className="flex items-center gap-2">
                            <StatusBadge status={issue.status} />
                            {issue.status !== 'resolved' ? (
                                <Form
                                    {...resolve.form(routeArgs)}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            size="sm"
                                            data-test="resolve-issue-button"
                                            disabled={processing}
                                        >
                                            <Check />
                                            Resolve
                                        </Button>
                                    )}
                                </Form>
                            ) : null}
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <SeverityBadge level={issue.level} />
                            <h2
                                data-test="issue-title"
                                className={cn(
                                    'text-xl font-semibold tracking-tight',
                                    issue.status === 'resolved' &&
                                        'text-muted-foreground line-through',
                                )}
                            >
                                {issue.title}
                            </h2>
                        </div>
                        {issue.culprit ? (
                            <p className="font-mono text-sm text-muted-foreground">
                                {issue.culprit}
                            </p>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <MetricCard
                        label="Events"
                        value={formatNumber(issue.event_count)}
                    />
                    <MetricCard
                        label="Users"
                        value={formatNumber(issue.user_count)}
                    />
                    <MetricCard
                        label="First seen"
                        value={formatDateTime(issue.first_seen)}
                    />
                    <MetricCard
                        label="Last seen"
                        value={formatRelativeTime(issue.last_seen)}
                    />
                </div>

                <Card className="gap-4 py-4">
                    <CardHeader className="px-4">
                        <CardTitle className="text-sm font-medium">
                            Frequency
                            <span className="ml-2 font-normal text-muted-foreground">
                                last 24 hours
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="px-4">
                        <Sparkline
                            values={issue.sparkline}
                            filled
                            className="h-24 w-full"
                        />
                    </CardContent>
                </Card>

                <LatestEventCard event={latestEvent} />

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="gap-4 py-4">
                        <CardHeader className="px-4">
                            <CardTitle className="text-sm font-medium">
                                Breadcrumbs
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-4">
                            <BreadcrumbList
                                breadcrumbs={latestEvent?.breadcrumbs ?? []}
                            />
                        </CardContent>
                    </Card>

                    <Card className="gap-4 py-4">
                        <CardHeader className="px-4">
                            <CardTitle className="text-sm font-medium">
                                Tags
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-4">
                            <TagList tags={latestEvent?.tags ?? []} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function MetricCard({ label, value }: { label: string; value: string }) {
    return (
        <Card className="gap-1 py-4">
            <CardHeader className="px-4">
                <CardTitle className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {label}
                </CardTitle>
            </CardHeader>
            <CardContent className="px-4 text-lg font-semibold tabular-nums">
                {value}
            </CardContent>
        </Card>
    );
}

function LatestEventCard({ event }: { event: LatestEvent | null }) {
    if (event === null) {
        return (
            <Card className="gap-4 py-4">
                <CardHeader className="px-4">
                    <CardTitle className="text-sm font-medium">
                        Latest event
                    </CardTitle>
                </CardHeader>
                <CardContent className="px-4 text-sm text-muted-foreground">
                    This issue has no events yet.
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="gap-4 py-4">
            <CardHeader className="px-4">
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle className="text-sm font-medium">
                        Latest event
                    </CardTitle>
                    <p className="font-mono text-xs text-muted-foreground">
                        {event.event_id}
                    </p>
                </div>
                <p className="text-sm text-muted-foreground">
                    {formatDateTime(event.occurred_at)}
                    {event.environment ? ` · ${event.environment}` : ''}
                    {event.release ? ` · ${event.release}` : ''}
                </p>
            </CardHeader>
            <CardContent className="px-4">
                <StackTrace frames={event.stacktrace} />
            </CardContent>
        </Card>
    );
}

function StackTrace({ frames }: { frames: StackFrame[] }) {
    if (frames.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No stack trace was captured for this event.
            </p>
        );
    }

    return (
        <ol
            data-test="stacktrace"
            className="overflow-x-auto font-mono text-xs"
        >
            {frames.map((frame, index) => (
                <li
                    key={`${frame.class ?? ''}:${frame.function ?? ''}:${frame.lineno ?? index}:${index}`}
                    className={cn(
                        'border-l-2 px-3 py-1.5',
                        frame.is_culprit
                            ? 'border-red-500 bg-red-50 dark:bg-red-950/40'
                            : 'border-transparent',
                        frame.in_app
                            ? 'text-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    <span>
                        {frameLocation(frame)}
                        {frame.lineno !== null ? `:${frame.lineno}` : ''}
                    </span>
                    {frame.function ? (
                        <span className="text-muted-foreground">
                            {' '}
                            in {frame.function}
                            {frame.function.endsWith(')') ? '' : '()'}
                        </span>
                    ) : null}
                </li>
            ))}
        </ol>
    );
}

function frameLocation(frame: StackFrame): string {
    if (frame.filename) {
        return frame.filename;
    }

    if (frame.class) {
        return frame.class;
    }

    return '(unknown)';
}

function BreadcrumbList({ breadcrumbs }: { breadcrumbs: Breadcrumb[] }) {
    if (breadcrumbs.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No breadcrumbs were captured for this event.
            </p>
        );
    }

    return (
        <ol className="flex flex-col gap-2" data-test="breadcrumbs">
            {breadcrumbs.map((breadcrumb, index) => (
                <li
                    key={`${breadcrumb.timestamp ?? ''}:${breadcrumb.message ?? ''}:${index}`}
                    className="flex flex-col gap-0.5 border-b pb-2 last:border-b-0 last:pb-0"
                >
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                        {breadcrumb.category ? (
                            <span className="font-medium">
                                {breadcrumb.category}
                            </span>
                        ) : null}
                        {breadcrumb.level ? (
                            <span className="text-muted-foreground uppercase">
                                {breadcrumb.level}
                            </span>
                        ) : null}
                    </div>
                    {breadcrumb.message ? (
                        <p className="text-sm">{breadcrumb.message}</p>
                    ) : null}
                </li>
            ))}
        </ol>
    );
}

function TagList({ tags }: { tags: LatestEvent['tags'] }) {
    if (tags.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                No tags were captured for this event.
            </p>
        );
    }

    return (
        <dl
            className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm"
            data-test="tags"
        >
            {tags.map((tag) => (
                <div key={`${tag.key}:${tag.value}`} className="contents">
                    <dt className="font-mono text-xs text-muted-foreground">
                        {tag.key}
                    </dt>
                    <dd className="min-w-0 truncate font-mono text-xs">
                        {tag.value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

IssueShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    project?: ProjectSummary;
    issue?: IssueDetail;
}) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: props.currentTeam ? projectsIndex(props.currentTeam) : '/',
        },
        {
            title: props.project?.name ?? 'Issues',
            href:
                props.currentTeam && props.project
                    ? index({
                          current_team: props.currentTeam,
                          project: props.project.id,
                      })
                    : '/',
        },
        {
            title: props.issue?.title ?? 'Issue',
            href:
                props.currentTeam && props.project && props.issue
                    ? show({
                          current_team: props.currentTeam,
                          project: props.project.id,
                          issue: props.issue.id,
                      })
                    : '/',
        },
    ],
});
