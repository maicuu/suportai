# PROGRESS_WEB — SupportAI (frontend Inertia/React)

> Changelog do frontend. Datas absolutas (AAAA-MM-DD). Status: ✅ concluído · 🚧 em andamento · 📋 planejado.

## Estado atual
- **Stack:** Inertia 2 + React 19 + TypeScript + Tailwind v4 (starter kit oficial do Laravel).
- **Auth UI:** telas de login/registro/perfil/2FA já scaffoldadas pelo starter kit.
- **Dev:** `npm run dev` (Vite + HMR). Build de produção já gerado em `public/build`.
- **Servido em:** `https://supportai.test`.

### Pendências imediatas (Fase 1)
- [ ] Inbox/board dos agentes (lista de tickets escopada por tenant).
- [ ] Tela do ticket com thread de mensagens + sugestões da IA.
- [ ] Laravel Echo + Reverb no front (atualização ao vivo do board).
- [ ] Indicador "digitando…" / presença (bônus).

## Histórico

### 2026-07-23 · Bootstrap do frontend · ✅ concluído
- **Arquivos:** `resources/js/**`, `vite.config.ts`, `package.json`, `tsconfig.json`.
- **Notas técnicas:**
  - Starter kit React trouxe Inertia 2 + React 19 + TS + Tailwind v4 + shadcn-style components.
  - Build inicial gerado (`--npm`). Landing renderiza em `https://supportai.test`.
  - Decisão de auth: sessão/cookie (Inertia), não token Bearer — ver PROGRESS_API.
