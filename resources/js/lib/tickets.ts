export function priorityClass(priority: string | null): string {
    switch (priority) {
        case 'urgent':
        case 'high':
            return 'bg-red-500/15 text-red-600 dark:text-red-400';
        case 'medium':
            return 'bg-amber-500/15 text-amber-600 dark:text-amber-400';
        default:
            return 'bg-slate-500/15 text-slate-600 dark:text-slate-400';
    }
}

export function sentimentClass(sentiment: string | null): string {
    switch (sentiment) {
        case 'negative':
            return 'bg-red-500/15 text-red-600 dark:text-red-400';
        case 'positive':
            return 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400';
        default:
            return 'bg-slate-500/15 text-slate-600 dark:text-slate-400';
    }
}

export const PRIORITY_LABELS: Record<string, string> = {
    low: 'baixa',
    medium: 'média',
    high: 'alta',
    urgent: 'urgente',
};

export const SENTIMENT_LABELS: Record<string, string> = {
    positive: 'positivo',
    neutral: 'neutro',
    negative: 'negativo',
};
