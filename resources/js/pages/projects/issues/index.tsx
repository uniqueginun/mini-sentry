import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import SeverityBadge from '@/components/issues/severity-badge';
import Sparkline from '@/components/issues/sparkline';
import ProjectNav from '@/components/projects/project-nav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatNumber, formatRelativeTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import { index as projectsIndex } from '@/routes/projects';
import { index, show } from '@/routes/projects/issues';
import type {
    IssueFilters,
    IssueListItem,
    IssuePagination,
    IssueSort,
    ProjectSummary,
} from '@/types';

type Props = {
    project: ProjectSummary;
    issues: IssuePagination;
    filters: IssueFilters;
    environments: string[];
    releases: string[];
};

export default function IssuesIndex({
    project,
    issues,
    filters,
    environments,
    releases,
}: Props) {
    const page = usePage();
    const currentTeam = page.props.currentTeam;
    const [search, setSearch] = useState(filters.search ?? '');
    const searchTimeout = useRef<number>(undefined);

    if (!currentTeam) {
        return null;
    }

    const routeArgs = {
        current_team: currentTeam,
        project: project.id,
    };

    const handleSearchChange = (value: string): void => {
        setSearch(value);

        window.clearTimeout(searchTimeout.current);

        const trimmed = value.trim();
        const current = filters.search ?? '';

        if (trimmed === current) {
            return;
        }

        searchTimeout.current = window.setTimeout(() => {
            visitFilters(currentTeam, project.id, filters, {
                search: trimmed === '' ? null : trimmed,
            });
        }, 300);
    };

    return (
        <>
            <Head title={`${project.name} issues`} />

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
                        {project.name}
                    </p>
                    <h1 className="text-xl font-semibold tracking-tight">
                        {project.name}
                    </h1>
                </div>

                <ProjectNav
                    currentTeam={currentTeam}
                    project={project}
                    current="issues"
                />

                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-wrap items-center gap-2">
                        <FilterSelect
                            label="Environment"
                            value={filters.environment ?? 'all'}
                            onChange={(value) =>
                                visitFilters(currentTeam, project.id, filters, {
                                    environment: value === 'all' ? null : value,
                                })
                            }
                            options={[
                                { value: 'all', label: 'All environments' },
                                ...environments.map((environment) => ({
                                    value: environment,
                                    label: environment,
                                })),
                            ]}
                        />
                        <FilterSelect
                            label="Release"
                            value={filters.release ?? 'all'}
                            onChange={(value) =>
                                visitFilters(currentTeam, project.id, filters, {
                                    release: value === 'all' ? null : value,
                                })
                            }
                            options={[
                                { value: 'all', label: 'All releases' },
                                ...releases.map((release) => ({
                                    value: release,
                                    label: release,
                                })),
                            ]}
                        />
                        <FilterSelect
                            label="Status"
                            value={filters.status}
                            onChange={(value) =>
                                visitFilters(currentTeam, project.id, filters, {
                                    status: value as IssueFilters['status'],
                                })
                            }
                            options={[
                                { value: 'unresolved', label: 'Unresolved' },
                                { value: 'resolved', label: 'Resolved' },
                                { value: 'regressed', label: 'Regressed' },
                                { value: 'all', label: 'All' },
                            ]}
                        />
                    </div>

                    <div className="relative w-full sm:max-w-xs">
                        <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) =>
                                handleSearchChange(event.target.value)
                            }
                            placeholder="Search issues"
                            data-test="issue-search"
                            className="pl-8"
                        />
                    </div>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <p data-test="issue-count">
                        {issues.meta.total === 1
                            ? '1 issue'
                            : `${formatNumber(issues.meta.total)} issues`}
                    </p>
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full min-w-[720px] text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-4 py-2.5">Issue</th>
                                <th className="w-28 px-4 py-2.5">Graph</th>
                                <SortHeader
                                    label="Events"
                                    sort="events"
                                    current={filters.sort}
                                    onSort={(sort) =>
                                        visitFilters(
                                            currentTeam,
                                            project.id,
                                            filters,
                                            { sort },
                                        )
                                    }
                                />
                                <SortHeader
                                    label="Users"
                                    sort="users"
                                    current={filters.sort}
                                    onSort={(sort) =>
                                        visitFilters(
                                            currentTeam,
                                            project.id,
                                            filters,
                                            { sort },
                                        )
                                    }
                                />
                                <SortHeader
                                    label="Last seen"
                                    sort="last_seen"
                                    current={filters.sort}
                                    onSort={(sort) =>
                                        visitFilters(
                                            currentTeam,
                                            project.id,
                                            filters,
                                            { sort },
                                        )
                                    }
                                />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {issues.data.map((issue) => (
                                <IssueRow
                                    key={issue.id}
                                    issue={issue}
                                    href={show.url({
                                        ...routeArgs,
                                        issue: issue.id,
                                    })}
                                />
                            ))}
                        </tbody>
                    </table>

                    {issues.data.length === 0 ? (
                        <p
                            data-test="issues-empty"
                            className="px-4 py-10 text-center text-muted-foreground"
                        >
                            {emptyMessage(filters)}
                        </p>
                    ) : null}
                </div>

                {issues.meta.last_page > 1 ? (
                    <div className="flex items-center justify-between text-sm">
                        <p className="text-muted-foreground">
                            {issues.meta.from}–{issues.meta.to} of{' '}
                            {formatNumber(issues.meta.total)}
                        </p>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                asChild={Boolean(issues.meta.prev_page_url)}
                                disabled={!issues.meta.prev_page_url}
                            >
                                {issues.meta.prev_page_url ? (
                                    <Link
                                        href={issues.meta.prev_page_url}
                                        preserveScroll
                                        preserveState
                                    >
                                        <ChevronLeft />
                                        Previous
                                    </Link>
                                ) : (
                                    <>
                                        <ChevronLeft />
                                        Previous
                                    </>
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                asChild={Boolean(issues.meta.next_page_url)}
                                disabled={!issues.meta.next_page_url}
                            >
                                {issues.meta.next_page_url ? (
                                    <Link
                                        href={issues.meta.next_page_url}
                                        preserveScroll
                                        preserveState
                                    >
                                        Next
                                        <ChevronRight />
                                    </Link>
                                ) : (
                                    <>
                                        Next
                                        <ChevronRight />
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>
        </>
    );
}

function IssueRow({ issue, href }: { issue: IssueListItem; href: string }) {
    const resolved = issue.status === 'resolved';

    return (
        <tr
            data-test="issue-row"
            className={cn(
                'cursor-pointer hover:bg-muted/50',
                resolved && 'opacity-60',
            )}
            onClick={() => router.visit(href)}
        >
            <td className="px-4 py-3">
                <div className="flex min-w-0 items-start gap-2">
                    <SeverityBadge level={issue.level} />
                    <div className="min-w-0">
                        <Link
                            href={href}
                            className={cn(
                                'block truncate font-medium hover:underline',
                                resolved &&
                                    'text-muted-foreground line-through',
                            )}
                            onClick={(event) => event.stopPropagation()}
                        >
                            {issue.title}
                        </Link>
                        <p className="truncate text-xs text-muted-foreground">
                            {[issue.culprit, issue.environment]
                                .filter(Boolean)
                                .join(' in ')}
                        </p>
                    </div>
                </div>
            </td>
            <td className="px-4 py-3">
                <Sparkline values={issue.sparkline} className="h-7 w-24" />
            </td>
            <td className="px-4 py-3 tabular-nums">
                {formatNumber(issue.event_count)}
            </td>
            <td className="px-4 py-3 tabular-nums">
                {formatNumber(issue.user_count)}
            </td>
            <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                {formatRelativeTime(issue.last_seen)}
            </td>
        </tr>
    );
}

function FilterSelect({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger
                size="sm"
                aria-label={label}
                data-test={`filter-${label.toLowerCase()}`}
            >
                <SelectValue placeholder={label} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function SortHeader({
    label,
    sort,
    current,
    onSort,
}: {
    label: string;
    sort: IssueSort;
    current: IssueSort;
    onSort: (sort: IssueSort) => void;
}) {
    const active = current === sort;

    return (
        <th className="px-4 py-2.5">
            <button
                type="button"
                className={cn(
                    'hover:text-foreground',
                    active && 'text-foreground',
                )}
                onClick={() => onSort(sort)}
            >
                {label}
            </button>
        </th>
    );
}

function visitFilters(
    currentTeam: { slug: string },
    projectId: number,
    filters: IssueFilters,
    next: Partial<IssueFilters>,
): void {
    const merged = { ...filters, ...next };
    const query: Record<string, string> = {
        status: merged.status,
        sort: merged.sort,
    };

    if (merged.environment) {
        query.environment = merged.environment;
    }

    if (merged.release) {
        query.release = merged.release;
    }

    if (merged.search) {
        query.search = merged.search;
    }

    router.get(
        index.url({ current_team: currentTeam, project: projectId }, { query }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function emptyMessage(filters: IssueFilters): string {
    if (
        filters.search ||
        filters.environment ||
        filters.release ||
        filters.status !== 'unresolved'
    ) {
        return 'No issues match these filters.';
    }

    return 'No unresolved issues. Ingest events to see them here.';
}

IssuesIndex.layout = (props: {
    currentTeam?: { slug: string } | null;
    project?: ProjectSummary;
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
    ],
});
