export type TicketStatus = 'open' | 'pending' | 'closed';

export type TicketAi = {
    category: string | null;
    priority: string | null;
    sentiment: string | null;
    suggested_reply: string | null;
    processed_at: string | null;
};

export type TicketMessage = {
    id: number;
    author_type: 'customer' | 'agent';
    author_name: string;
    body: string;
    created_at: string;
};

/** Forma enxuta usada no board (e no payload de broadcast). */
export type TicketCard = {
    id: number;
    requester_name: string;
    subject: string;
    status: TicketStatus;
    ai: TicketAi;
    created_at: string;
};

/** Forma completa da tela de detalhe. */
export type TicketDetail = TicketCard & {
    requester_email: string;
    body: string;
    messages: TicketMessage[];
    updated_at: string;
};
