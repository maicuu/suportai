import { Head, Link } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { PRIORITY_LABELS, priorityClass, SENTIMENT_LABELS, sentimentClass } from '@/lib/tickets';
import { cn } from '@/lib/utils';
import type { TicketCard, TicketStatus } from '@/types/ticket';

const COLUMNS: { key: TicketStatus; label: string }[] = [
    { key: 'open', label: 'Abertos' },
    { key: 'pending', label: 'Pendentes' },
    { key: 'closed', label: 'Fechados' },
];

function Card({ ticket }: { ticket: TicketCard }) {
    const { ai } = ticket;
    const analyzed = ai.category !== null;

    return (
        <Link
            href={`/tickets/${ticket.id}`}
            className="block rounded-lg border bg-background p-3 shadow-sm transition hover:shadow-md"
        >
            <p className="line-clamp-2 text-sm font-medium">{ticket.subject}</p>
            <p className="mt-1 text-xs text-muted-foreground">{ticket.requester_name}</p>
            <div className="mt-3 flex flex-wrap gap-1.5">
                {analyzed ? (
                    <>
                        {ai.category && <Badge variant="outline">{ai.category}</Badge>}
                        {ai.priority && (
                            <Badge className={cn('border-transparent', priorityClass(ai.priority))}>
                                {PRIORITY_LABELS[ai.priority] ?? ai.priority}
                            </Badge>
                        )}
                        {ai.sentiment && (
                            <Badge className={cn('border-transparent', sentimentClass(ai.sentiment))}>
                                {SENTIMENT_LABELS[ai.sentiment] ?? ai.sentiment}
                            </Badge>
                        )}
                    </>
                ) : (
                    <span className="text-xs text-muted-foreground italic">IA analisando…</span>
                )}
            </div>
        </Link>
    );
}

export default function TicketsIndex({ tickets: initial, tenantId }: { tickets: TicketCard[]; tenantId: number }) {
    const [tickets, setTickets] = useState<TicketCard[]>(initial);

    const upsert = (incoming: TicketCard) =>
        setTickets((prev) =>
            prev.some((t) => t.id === incoming.id)
                ? prev.map((t) => (t.id === incoming.id ? incoming : t))
                : [incoming, ...prev],
        );

    useEcho<{ ticket: TicketCard }>(`tenant.${tenantId}`, '.ticket.created', (e) => upsert(e.ticket), [tenantId]);
    useEcho<{ ticket: TicketCard }>(`tenant.${tenantId}`, '.ticket.classified', (e) => upsert(e.ticket), [tenantId]);

    return (
        <>
            <Head title="Tickets" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid flex-1 gap-4 md:grid-cols-3">
                    {COLUMNS.map((col) => {
                        const items = tickets.filter((t) => t.status === col.key);
                        return (
                            <section
                                key={col.key}
                                className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-muted/30 p-3 dark:border-sidebar-border"
                            >
                                <header className="flex items-center justify-between px-1">
                                    <h2 className="text-sm font-semibold">{col.label}</h2>
                                    <span className="text-xs text-muted-foreground">{items.length}</span>
                                </header>
                                <div className="flex flex-col gap-3">
                                    {items.map((ticket) => (
                                        <Card key={ticket.id} ticket={ticket} />
                                    ))}
                                    {items.length === 0 && (
                                        <p className="px-1 text-xs text-muted-foreground">Nenhum ticket.</p>
                                    )}
                                </div>
                            </section>
                        );
                    })}
                </div>
            </div>
        </>
    );
}

TicketsIndex.layout = { breadcrumbs: [{ title: 'Tickets', href: '/tickets' }] };
