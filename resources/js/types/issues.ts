export type IssueStatus = 'unresolved' | 'resolved' | 'regressed';

export type IssueSort = 'last_seen' | 'first_seen' | 'events' | 'users';

export type IssueFilters = {
    environment: string | null;
    release: string | null;
    status: 'all' | IssueStatus;
    search: string | null;
    sort: IssueSort;
};

export type IssueListItem = {
    id: number;
    title: string;
    culprit: string | null;
    status: IssueStatus;
    event_count: number;
    user_count: number;
    first_seen: string;
    last_seen: string;
    level: string;
    environment: string | null;
    sparkline: number[];
};

export type IssuePagination = {
    data: IssueListItem[];
    meta: {
        total: number;
        from: number | null;
        to: number | null;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
};

export type StackFrame = {
    filename: string | null;
    lineno: number | null;
    function: string | null;
    class: string | null;
    in_app: boolean;
    is_culprit: boolean;
};

export type Breadcrumb = {
    timestamp: string | null;
    category: string | null;
    message: string | null;
    level: string | null;
    type: string | null;
};

export type EventTag = {
    key: string;
    value: string;
};

export type LatestEvent = {
    id: string;
    event_id: string;
    occurred_at: string;
    environment: string | null;
    release: string | null;
    exception_type: string | null;
    message: string | null;
    level: string;
    stacktrace: StackFrame[];
    breadcrumbs: Breadcrumb[];
    tags: EventTag[];
};

export type IssueDetail = IssueListItem & {
    latest_event: LatestEvent | null;
};

export type ProjectSummary = {
    id: number;
    name: string;
};

export type DashboardStats = {
    unresolved_issues: number;
    events_last_24h: number;
    affected_users: number;
};

export type DashboardIssue = IssueListItem & {
    project: ProjectSummary;
};
