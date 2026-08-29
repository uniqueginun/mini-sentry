export function formatRelativeTime(value: string): string {
    const deltaMs = Date.now() - new Date(value).getTime();
    const deltaSeconds = Math.round(deltaMs / 1000);
    const abs = Math.abs(deltaSeconds);

    if (abs < 60) {
        return 'just now';
    }

    if (abs < 3600) {
        const minutes = Math.round(abs / 60);

        return `${minutes} min ago`;
    }

    if (abs < 86400) {
        const hours = Math.round(abs / 3600);

        return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
    }

    const days = Math.round(abs / 86400);

    return days === 1 ? '1 day ago' : `${days} days ago`;
}

export function formatDateTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat(undefined).format(value);
}
