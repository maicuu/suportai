# PROGRESS_WEB — SupportAI (frontend Inertia/React)

> Changelog do frontend. Datas absolutas (AAAA-MM-DD). Status: ✅ concluído · 🚧 em andamento · 📋 planejado.

## Estado atual
- **Stack:** Inertia 2 + React 19 + TypeScript + Tailwind v4 (starter kit) + componentes shadcn.
- **Auth UI:** login/registro/perfil/2FA (starter kit).
- **Board de tickets (Kanban):** colunas Abertos / Pendentes / Fechados; cards com badges de categoria/prioridade/sentimento da IA. **Ao vivo via Echo/Reverb** — o card **aparece** (`ticket.created`) e **acende** com a classificação (`ticket.classified`), no canal privado do tenant.
- **Tela do ticket:** thread cliente/agente, card de **sugestão da IA** (botão "Usar sugestão"), form de resposta (Inertia `useForm` → redirect back); escuta `ticket.classified` pra atualizar ao vivo.
- **Nav:** item "Tickets" na sidebar.
- **Dev:** `npm run dev` (HMR). Build de produção gerado (tipos `tsc` verdes).
- **Servido em:** `https://supportai.test`. Para o WebSocket ao vivo, abrir via **http://supportai.test** evita mixed-content com `ws://` (Reverb).

### Pendências (pós-Fase 1)
- [ ] Mover status do ticket (drag-and-drop entre colunas) + persistir.
- [ ] Indicador "digitando…" / presença (bônus).
- [ ] Broadcast de novas mensagens (reply ao vivo entre agentes).

## Histórico

### 2026-07-23 · Passo 5 — Board Kanban + tela do ticket (Echo ao vivo) · ✅ concluído
- **Arquivos:** `resources/js/pages/tickets/{index,show}.tsx`, `resources/js/types/ticket.ts`, `resources/js/lib/tickets.ts`, `resources/js/components/app-sidebar.tsx`.
- **Notas técnicas:**
  - Board Kanban por status; `useEcho` (`@laravel/echo-react`) no canal privado `tenant.{id}` ouvindo `.ticket.created` / `.ticket.classified` (upsert no estado local).
  - Detalhe: thread + sugestão da IA ("Usar sugestão" preenche a resposta) + reply (`useForm` → `back()`); escuta `.ticket.classified` pra atualizar a classificação em tempo real.
  - Payload de broadcast (`Ticket::toBroadcastArray`) tem a mesma forma (subset) do `TicketResource` → front trata props iniciais e eventos igual.
  - Verificação: `tsc --noEmit` sem erros, `npm run build` OK, `php artisan test` 56/56.

### 2026-07-23 · Bootstrap do frontend · ✅ concluído
- **Arquivos:** `resources/js/**`, `vite.config.ts`, `package.json`, `tsconfig.json`.
- **Notas técnicas:**
  - Starter kit React trouxe Inertia 2 + React 19 + TS + Tailwind v4 + componentes shadcn.
  - Build inicial gerado (`--npm`). Landing renderiza em `https://supportai.test`.
  - Decisão de auth: sessão/cookie (Inertia), não token Bearer — ver PROGRESS_API.
