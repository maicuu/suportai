import { Head, useForm } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { useState, type FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PRIORITY_LABELS, priorityClass, SENTIMENT_LABELS, sentimentClass } from '@/lib/tickets';
import { cn } from '@/lib/utils';
import type { TicketCard, TicketDetail } from '@/types/ticket';

export default function TicketsShow({ ticket: initial, tenantId }: { ticket: TicketDetail; tenantId: number }) {
    const [ticket, setTicket] = useState<TicketDetail>(initial);
    const form = useForm({ body: '' });

    useEcho<{ ticket: TicketCard }>(
        `tenant.${tenantId}`,
        '.ticket.classified',
        (e) => {
            if (e.ticket.id === ticket.id) {
                setTicket((prev) => ({ ...prev, ai: e.ticket.ai, status: e.ticket.status }));
            }
        },
        [tenantId, ticket.id],
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/tickets/${ticket.id}/reply`, {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    };

    const { ai } = ticket;

    return (
        <>
            <Head title={ticket.subject} />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <header>
                    <h1 className="text-lg font-semibold">{ticket.subject}</h1>
                    <p className="text-sm text-muted-foreground">
                        {ticket.requester_name} · {ticket.requester_email}
                    </p>
                    <div className="mt-2 flex flex-wrap gap-1.5">
                        <Badge variant="secondary">{ticket.status}</Badge>
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
                    </div>
                </header>

                {ai.suggested_reply && (
                    <div className="rounded-xl border bg-muted/40 p-4">
                        <div className="mb-2 flex items-center justify-between gap-2">
                            <p className="text-sm font-medium">✨ Sugestão da IA</p>
                            <Button
                                type="button"
                                size="sm"
                                variant="secondary"
                                onClick={() => form.setData('body', ai.suggested_reply ?? '')}
                            >
                                Usar sugestão
                            </Button>
                        </div>
                        <p className="text-sm whitespace-pre-wrap text-muted-foreground">{ai.suggested_reply}</p>
                    </div>
                )}

                <div className="flex flex-col gap-3">
                    {ticket.messages.map((message) => (
                        <div
                            key={message.id}
                            className={cn(
                                'rounded-lg border p-3',
                                message.author_type === 'agent' ? 'ml-8 bg-primary/5' : 'mr-8',
                            )}
                        >
                            <p className="text-xs font-medium">
                                {message.author_name}{' '}
                                <span className="text-muted-foreground">
                                    · {message.author_type === 'agent' ? 'agente' : 'cliente'}
                                </span>
                            </p>
                            <p className="mt-1 text-sm whitespace-pre-wrap">{message.body}</p>
                        </div>
                    ))}
                </div>

                <form onSubmit={submit} className="flex flex-col gap-2">
                    <textarea
                        value={form.data.body}
                        onChange={(event) => form.setData('body', event.target.value)}
                        rows={4}
                        placeholder="Escreva sua resposta…"
                        className="w-full rounded-lg border bg-background p-3 text-sm outline-none focus:ring-2 focus:ring-ring"
                    />
                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing || form.data.body.trim() === ''}>
                            Responder
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

TicketsShow.layout = {
    breadcrumbs: [
        { title: 'Tickets', href: '/tickets' },
        { title: 'Detalhe', href: '#' },
    ],
};
