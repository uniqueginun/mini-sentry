import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import SeverityBadge from '@/components/issues/severity-badge';
import Sparkline from '@/components/issues/sparkline';
import StatusBadge from '@/components/issues/status-badge';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Button } from '@/components/ui/button';
import { formatNumber, formatRelativeTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';
import { show as issueShow } from '@/routes/projects/issues';
import type { DashboardInvitation, DashboardIssue, DashboardStats } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    stats: DashboardStats;
    sparkline: number[];
    issues: DashboardIssue[];
    has_projects: boolean;
};

export default function Dashboard({
    pendingInvitations = [],
    stats,
    sparkline,
    issues,
    has_projects,
}: Props) {
    const { currentTeam } = usePage().props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    if (!currentTeam) {
        return null;
    }

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Dashboard"
                    description="What needs attention on this team."
                />

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard
                        label="Unresolved issues"
                        value={stats.unresolved_issues}
                        testId="dashboard-unresolved-count"
                    />
                    <StatCard
                        label="Events (24h)"
                        value={stats.events_last_24h}
                        testId="dashboard-events-count"
                    />
                    <StatCard
                        label="Affected users"
                        value={stats.affected_users}
                        testId="dashboard-users-count"
                    />
                </div>

                <section className="rounded-xl border bg-card p-4 shadow-sm">
                    <h2 className="text-sm font-medium">Events over 24 hours</h2>
                    <Sparkline
                        values={sparkline}
                        filled
                        className="mt-4 h-24 w-full"
                    />
                </section>

                <section className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="flex items-center justify-between gap-3 border-b px-4 py-3">
                        <h2 className="text-sm font-medium">
                            Unresolved issues
                        </h2>
                        {has_projects ? (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={projectsIndex(currentTeam)}>
                                    View projects
                                </Link>
                            </Button>
                        ) : null}
                    </div>

                    {issues.length > 0 ? (
                        <ul className="divide-y">
                            {issues.map((issue) => {
                                const href = issueShow.url({
                                    current_team: currentTeam,
                                    project: issue.project.id,
                                    issue: issue.id,
                                });

                                return (
                                    <li key={issue.id}>
                                        <Link
                                            href={href}
                                            data-test="dashboard-issue-row"
                                            className="flex items-start gap-3 px-4 py-3 hover:bg-muted/50"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <SeverityBadge
                                                        level={issue.level}
                                                    />
                                                    {issue.status ===
                                                    'regressed' ? (
                                                        <StatusBadge
                                                            status={
                                                                issue.status
                                                            }
                                                        />
                                                    ) : null}
                                                    <span className="min-w-0 truncate font-medium">
                                                        {issue.title}
                                                    </span>
                                                </div>
                                                <p className="mt-1 truncate text-xs text-muted-foreground">
                                                    {issue.project.name}
                                                    {issue.environment
                                                        ? ` · ${issue.environment}`
                                                        : ''}
                                                    {issue.culprit
                                                        ? ` · ${issue.culprit}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <Sparkline
                                                values={issue.sparkline}
                                                className="hidden h-7 w-24 shrink-0 sm:block"
                                            />
                                            <div className="shrink-0 text-right">
                                                <p className="text-sm tabular-nums">
                                                    {formatNumber(
                                                        issue.event_count,
                                                    )}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatRelativeTime(
                                                        issue.last_seen,
                                                    )}
                                                </p>
                                            </div>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    ) : (
                        <div
                            data-test="dashboard-empty"
                            className="flex flex-col items-center gap-3 px-4 py-12 text-center"
                        >
                            <p className="font-medium">
                                {has_projects
                                    ? 'No unresolved issues'
                                    : 'No projects yet'}
                            </p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                {has_projects
                                    ? 'Ingest events from a project to see them here.'
                                    : 'Create a project and generate an ingest key to start tracking errors.'}
                            </p>
                            {!has_projects ? (
                                <Button asChild>
                                    <Link href={projectsIndex(currentTeam)}>
                                        Create a project
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    testId,
}: {
    label: string;
    value: number;
    testId: string;
}) {
    return (
        <article className="rounded-xl border bg-card p-4 shadow-sm">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p
                data-test={testId}
                className="mt-2 text-3xl font-semibold tracking-tight tabular-nums"
            >
                {formatNumber(value)}
            </p>
        </article>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
