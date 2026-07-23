# SupportAI — Handoff & Guia de Continuidade

> Documento vivo pra retomar o projeto (rodar, gravar o demo, e continuar num chat novo).
> Última atualização: **2026-07-23** — Fase 1 completa.

---

## 1. O que é o projeto
SaaS **multi-tenant de helpdesk** onde a **IA tria e sugere respostas** e o quadro de
tickets atualiza **em tempo real**. Objetivo duplo: (a) demo de 30s "de parar o scroll"
no LinkedIn (IA + tempo real) e (b) engenharia defensável em entrevista.

**Fluxo-estrela:** ticket chega → job na fila chama o LLM (categoria/prioridade/sentimento
+ rascunho de resposta) → o board dos agentes atualiza ao vivo (Reverb).

Sou **novo em PHP/Laravel** (venho de Java/Spring, Node/TS, React) — quero aprender o
backend Laravel construindo isto, com cada conceito novo ancorado no equivalente de
Spring/JPA/Express.

## 2. Status atual (2026-07-23)
**Fase 1 ENTREGUE** — 5 passos, **56 testes verdes**, tudo pushado.

| Passo | Entrega | Commit |
|---|---|---|
| 1 | Fundação multi-tenant + global scope + isolamento (teste) | `51f4f25` `7f2fb64` |
| 2 | Tickets: intake público + thread + CRUD escopado (Form Request, API Resource) | `609efb1` |
| 4 | IA plugável na fila (porta hexagonal `AiProvider`, adapters Fake/Groq) | `20725ff` |
| 3 | Tempo real com Reverb (events em canal privado por tenant) | `6675493` |
| 5 | Board Kanban React + tela do ticket (Echo ao vivo) | `4108afd` |
| — | DemoSeeder (demo rodável) | `73223f0` |

## 3. Ambiente
- **SO:** Windows 10. **PHP + Composer** vêm do **Laravel Herd** (nada de Docker no PC —
  Docker só existe numa VM). Binários do Herd: `C:\Users\Trabalho\.config\herd\bin`.
- **Node 22 / npm** ok.
- **Pasta do projeto:** `C:\Users\Trabalho\Documents\Projetinhos 2026\supportai`
- **Repo:** https://github.com/maicuu/suportai (branch `main`)
- **Servido em:** `https://supportai.test` (Herd link + TLS). Para o demo ao vivo, usar
  **http://supportai.test** (ver nota do WebSocket abaixo).
- **Banco:** SQLite (`database/database.sqlite`).

### Gotchas de ambiente (importante)
- No **meu terminal normal**, `php`/`composer`/`artisan` já funcionam (Herd no PATH).
- Depois de mudar **rotas/middleware**, rodar **`herd restart`** (limpa opcache; senão dá 502).
- PowerShell 5.1 quebra aspas duplas ao chamar `git`/`tinker` — usar **arquivo**
  (`git commit -F arquivo`, `php artisan tinker arquivo.php`).

## 4. Como rodar (desenvolvimento)
```bash
# na pasta do projeto
composer install          # se ainda não tiver vendor/
npm install               # se ainda não tiver node_modules/
php artisan migrate        # cria o schema no SQLite

# dev com HMR (serve + fila + vite juntos):
composer dev
#   (ou separados: php artisan serve  |  php artisan queue:work  |  npm run dev)
```
O site também é servido pelo Herd em `https://supportai.test` sem manter terminal aberto.

## 5. Como gravar o DEMO ao vivo 🎬
```bash
# 1) cria o cenário de demo (tenant "demo" + agente):
php artisan db:seed --class=DemoSeeder     # login: agente@demo.test / password

# 2) suba 2 processos (2 terminais):
php artisan reverb:start     # WebSocket na porta 8080
php artisan queue:work       # processa IA + broadcasts
#   (se o front não estiver buildado: npm run build  — ou  npm run dev)
```
3. Abra **http://supportai.test/login** (use **http**, não https — ver nota abaixo),
   entre com `agente@demo.test` / `password`, e vá em **Tickets**.
4. Dispare um ticket (abra `docs/api.http` e clique *Send Request* no POST público, ou):
   ```bash
   curl.exe -X POST http://supportai.test/t/demo/tickets ^
     -H "Content-Type: application/json" -H "Accept: application/json" ^
     -d "{\"requester_name\":\"Cliente\",\"requester_email\":\"c@x.com\",\"subject\":\"App travando ao pagar\",\"body\":\"Erro no cartao ao finalizar a compra\"}"
   ```
   → o card **aparece** em *Abertos* na hora e **acende** com categoria/prioridade/
   sentimento; abra o card pra ver a **sugestão da IA**.

> **Nota WebSocket (mixed-content):** o Reverb usa `ws://localhost:8080`. Uma página
> **https** bloqueia conexão `ws://` insegura. Por isso, para o demo, acesse via
> **http://supportai.test**. (Alternativa: configurar Reverb com TLS/`wss`.)

### IA real (Groq) em vez do Fake
No `.env`: `AI_PROVIDER=groq` e `GROQ_API_KEY=<sua_key>` (free tier em groq.com);
reinicie o `queue:work`. O adapter já existe (`app/Ai/Providers/GroqAiProvider.php`).

## 6. Testes
```bash
php artisan test           # 56 verdes (Pest)
npm run types:check        # tipos TS (tsc --noEmit)
npm run build              # build de produção do front
```

## 7. Mapa da arquitetura (arquivos-chave)
- **Multi-tenant:** `app/Models/Concerns/BelongsToTenant.php` (global scope + auto-fill),
  aplicado a `Ticket` e `Message`. Registro cria tenant em `app/Actions/Fortify/CreateNewUser.php`.
- **Tickets:** `app/Http/Controllers/TicketController.php` (intake público JSON + páginas
  Inertia), `app/Http/Requests/*`, `app/Http/Resources/*`, `routes/web.php`.
- **IA (porta hexagonal):** `app/Ai/AiProvider.php` (interface), `app/Ai/AiAnalysis.php`
  (DTO), `app/Ai/Providers/{Fake,Groq}AiProvider.php`, binding em `app/Providers/AppServiceProvider.php`.
- **Fila:** `app/Jobs/ClassifyTicket.php` (disparado no `store`).
- **Tempo real:** `app/Events/{TicketCreated,TicketClassified}.php`, `routes/channels.php`
  (canal privado `tenant.{id}`).
- **Front:** `resources/js/pages/tickets/{index,show}.tsx`, `resources/js/lib/tickets.ts`,
  `resources/js/types/ticket.ts`.

## 8. Convenções do projeto (SEGUIR SEMPRE)
- **Docs vivos** (atualizar a cada mudança relevante): `PROGRESS_API.md`, `PROGRESS_WEB.md`,
  `docs/api.http`. Formato: "Estado atual" no topo + "Histórico" cronológico inverso.
- **Git:** commit + push **a cada passo**; **NÃO** incluir Claude como coautor (sem trailer
  `Co-Authored-By`). Usar `git commit -F arquivo`. Push `--force` é bloqueado — usar
  `merge --allow-unrelated-histories -X ours` se precisar reconciliar.
- **Engenharia (inegociável):** `tenant_id` em todo dado, escopo por query vindo do usuário
  logado (nunca do body/header); efeitos colaterais (IA/notificação) na **fila**, fora do
  request; IA atrás da porta, resiliente (falha não derruba o ticket); validação por Form
  Request, saída por API Resource/DTO (nunca model cru), autorização por escopo/Policies.
- **Ensino:** explicar cada conceito novo de Laravel ancorado em Spring/JPA/Express.

## 9. Roadmap (próximos passos, pós-Fase 1)
- Mover status do ticket (drag-and-drop no board) + persistir (`PATCH /tickets/{id}`).
- SLA + jobs agendados (scheduler); "digitando…"/presença (Reverb presence).
- E-mail → ticket (intake por e-mail) com **idempotência** em webhooks.
- Filament (admin), Billing (Cashier/Stripe), canned replies, métricas.
- Reverb com TLS (`wss`) pra rodar sob https sem mixed-content.

---

## 10. PROMPT PARA CONTINUAR (colar num chat novo do Claude Code, nesta pasta)

```
Estou continuando o projeto SupportAI (SaaS helpdesk multi-tenant com IA + tempo real,
Laravel 13 + Inertia/React + Reverb). Leia primeiro, na raiz do projeto:
HANDOFF.md, PROGRESS_API.md, PROGRESS_WEB.md e docs/api.http — eles têm o estado atual,
a arquitetura e as convenções.

Contexto: sou novo em PHP/Laravel (venho de Java/Spring, Node/TS, React); explique cada
conceito novo de Laravel ancorado no equivalente de Spring/JPA/Express, em passos pequenos
e verificáveis. A Fase 1 (fluxo-estrela: ticket → IA na fila → board ao vivo) está
COMPLETA e com 56 testes verdes.

Ambiente: Windows + Laravel Herd (sem Docker no PC). Repo: github.com/maicuu/suportai.
Convenções OBRIGATÓRIAS: (1) atualizar PROGRESS_API.md / PROGRESS_WEB.md / docs/api.http a
cada mudança; (2) commit + push a cada passo, SEM me incluir como coautor.

Quero continuar pelo roadmap (seção 9 do HANDOFF.md). Comece confirmando o estado atual
(rode `php artisan test` pra validar) e me proponha o plano do próximo passo antes de codar.
```
