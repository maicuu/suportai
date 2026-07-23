# PROGRESS_API — SupportAI (backend Laravel)

> Changelog do backend. Datas absolutas (AAAA-MM-DD). Status: ✅ concluído · 🚧 em andamento · 📋 planejado.

## Estado atual
- **Stack:** Laravel 13.21.1 (PHP 8.4), starter kit React (Inertia 2 + Fortify).
- **Banco:** SQLite (`database/database.sqlite`). Migrations base rodadas (users, cache, jobs, passkeys, 2FA).
- **Auth:** Fortify (sessão/cookie — modo correto pra Inertia). 2FA e passkeys já vêm de fábrica.
- **Multi-tenant:** `tenants` + `tenant_id` FK em `users`/`tickets`; trait `BelongsToTenant` (global scope + auto-fill) isola tudo pelo usuário logado. Teste de isolamento verde.
- **Domínio:** `Ticket` + `Message` (thread) com relações. CRUD escopado: abertura **pública** por slug (`POST /t/{slug}/tickets`) + listagem/detalhe/reply do agente (auth), via Form Request + API Resource. Endpoint público testado em HTTP (201).
- **Fila:** driver `database`. Job `ClassifyTicket` (tries=3 + backoff) roda a IA **fora do request**.
- **IA:** porta hexagonal `AiProvider` + adapters `FakeAiProvider` (default, offline) e `GroqAiProvider`; binding por config (`AI_PROVIDER`). Abrir ticket dispara `ClassifyTicket`.
- **Tempo real:** Reverb (WebSocket, `reverb:start` na porta 8080). Events `TicketCreated`/`TicketClassified` no canal **privado** `tenant.{id}`; broadcast enfileirado (Reverb fora do ar não quebra o request). Falta o board React (Passo 5) pra consumir.
- **Servido em:** `https://supportai.test` (Herd link + TLS).

### Pendências imediatas (Fase 1)
- [x] Tabela `tenants` + model `Tenant`.
- [x] `tenant_id` em `users` (+ FK) e relações `Tenant`↔`User`; registro cria tenant.
- [x] Global scope por tenant (trait `BelongsToTenant`) + teste de isolamento (3 casos verdes).
- [x] Tickets: CRUD escopado, Form Request, API Resource, thread de `messages` (48/48 testes verdes).
- [ ] Registro cria/associa tenant; login escopado.
- [ ] Tickets: migration + model + relações (messages), Form Request, API Resource.
- [x] Reverb + Event broadcasting (`TicketCreated`/`TicketClassified`) em canal privado por tenant.
- [x] Board React (Inertia) + Echo ao vivo (Passo 5) — **Fase 1 completa**.
- [x] Porta `AiProvider` (hexagonal) + Job `ClassifyTicket` em fila (adapters Fake/Groq).

## Histórico

### 2026-07-23 · Passo 5 (glue backend) — controllers Inertia · ✅ concluído
- **Arquivos:** `app/Http/Controllers/TicketController.php`, `app/Models/Ticket.php` (`toBroadcastArray`), `tests/Feature/TicketTest.php`.
- **Notas técnicas:**
  - `TicketController@index/@show` agora retornam `Inertia::render` (props via `Resource->resolve()`, sem wrapper `data`); `@reply` redireciona (`back()`); intake público segue JSON.
  - `toBroadcastArray` remodelado com `ai` aninhado (igual ao `TicketResource`) — front trata props e broadcast do mesmo jeito.
  - Testes de ticket migrados p/ asserções Inertia (`AssertableInertia`). Suite 56/56.

### 2026-07-23 · Passo 3 — Tempo real com Reverb (broadcast por tenant) · ✅ concluído
- **Arquivos:** `install:broadcasting --reverb` (config/reverb.php, config/broadcasting.php, `routes/channels.php`, `.env` REVERB_*), `app/Events/{TicketCreated,TicketClassified}.php`, `app/Models/Ticket.php` (`toBroadcastArray`), `TicketController`/`ClassifyTicket` (dispatch), `.env.example`, `tests/Feature/BroadcastTest.php`.
- **Notas técnicas:**
  - Canal **privado** `tenant.{tenantId}` autorizado por `user->tenant_id` — isolamento multi-tenant também no WebSocket.
  - `TicketCreated` (ao abrir) e `TicketClassified` (ao fim da IA) via `ShouldBroadcast` (enfileirado → resiliente). Payload enxuto por `Ticket::toBroadcastArray()`. Nomes: `ticket.created`, `ticket.classified`.
  - Servidor: `php artisan reverb:start` (porta 8080) — verificado subindo e aceitando conexão.
  - **Verificação:** `php artisan test` 55/55 (3 novos de broadcast: dispatch no create, canal privado correto, dispatch no fim do job).

### 2026-07-23 · Passo 4 — IA plugável na fila (porta hexagonal + Job) · ✅ concluído
- **Arquivos:** `app/Ai/AiProvider.php` (port), `app/Ai/AiAnalysis.php` (DTO), `app/Ai/Providers/{Fake,Groq}AiProvider.php`, `app/Jobs/ClassifyTicket.php`, `app/Providers/AppServiceProvider.php` (binding), `config/services.php`, `.env(.example)`, `app/Http/Controllers/TicketController.php` (dispatch), `tests/Feature/AiClassificationTest.php`.
- **Notas técnicas:**
  - **Port hexagonal:** domínio depende só de `AiProvider`; adapters plugáveis. Binding no container por `config('services.ai.provider')` (`fake` default | `groq`).
  - **Job `ClassifyTicket`** (recebe só o id): busca `withoutGlobalScopes` (fila não tem usuário logado), chama a IA, `forceFill` nos campos de IA (não-fillable), salva. `tries=3` + backoff = resiliência; falha não derruba o ticket.
  - Abrir ticket faz `ClassifyTicket::dispatch()` — efeito colateral assíncrono, fora do request.
  - **Verificação:** `php artisan test` 52/52. Demo HTTP real: POST 201 com `ai=null` → `queue:work` → ticket classificado (category/priority/sentiment/rascunho).
- **Como rodar a IA localmente:** `php artisan queue:work` (ou `composer dev`). Groq: setar `AI_PROVIDER=groq` + `GROQ_API_KEY` no `.env`.

### 2026-07-23 · Passo 2 — Tickets (CRUD escopado + thread + API Resource) · ✅ concluído
- **Arquivos:** `app/Models/Message.php` + migration/factory, `app/Models/{Ticket,Tenant}.php` (relações), `app/Http/Controllers/TicketController.php`, `app/Http/Requests/{StoreTicketRequest,StoreReplyRequest}.php`, `app/Http/Resources/{Ticket,Message}Resource.php`, `routes/web.php`, `bootstrap/app.php` (CSRF), `tests/Feature/TicketTest.php`.
- **Endpoints:** `POST /t/{tenant:slug}/tickets` (público), `GET /tickets`, `GET /tickets/{ticket}`, `POST /tickets/{ticket}/reply` (auth).
- **Notas técnicas:**
  - Abertura pública escopada por **slug** do tenant (route-model binding); cria ticket + 1ª mensagem (`author_type=customer`) em `DB::transaction`.
  - `tenant_id` em `messages` também (isolamento em profundidade, via trait); em mensagem de cliente é setado explícito, em reply de agente é auto-fill.
  - Saída sempre via **API Resource** (nunca model cru; `tenant_id` não é exposto). `whenLoaded('messages')` evita N+1.
  - Isenção de CSRF só para `t/*/tickets` (intake público sem sessão).
  - `status` setado explícito no create (default do banco não reflete no objeto em memória).
  - **Verificação:** `php artisan test` 48/48; smoke test HTTP real do endpoint público → 201.
  - **Ops:** após mudar rotas/middleware, `herd restart` limpa o opcache/worker (evita 502).

### 2026-07-23 · Passo 1d — global scope por tenant (`BelongsToTenant`) + `Ticket` · ✅ concluído
- **Arquivos:** `app/Models/Concerns/BelongsToTenant.php`, `app/Models/Ticket.php`, `database/migrations/2026_07_23_155008_create_tickets_table.php`, `database/factories/TicketFactory.php`, `tests/Feature/TenantIsolationTest.php`.
- **Notas técnicas:**
  - Trait `BelongsToTenant`: global scope (`WHERE tenant_id = <tenant logado>`) + auto-fill de `tenant_id` no `creating`, só quando há usuário autenticado (fila/console/seed definem o tenant explicitamente).
  - `Ticket` usa a trait; `tenant_id` e campos de IA fora do `$fillable`.
  - Schema de tickets já inclui campos de IA nullable (`category`, `priority`, `sentiment`, `ai_suggested_reply`, `ai_processed_at`) p/ o Passo 4 não precisar de nova migration.
  - **Teste de isolamento (3 casos):** escopo por tenant, auto-fill no create, e não-vazamento por id. `php artisan test` → 42/42 verde.

### 2026-07-23 · Passo 1b+1c — `tenant_id` em users + registro cria tenant · ✅ concluído
- **Arquivos:** `database/migrations/0001_..._create_users_table.php` (FK `tenant_id`), migration de tenants renomeada p/ `0000_...` (rodar antes de users), `app/Models/{User,Tenant}.php`, `database/factories/{UserFactory,TenantFactory}.php`, `app/Actions/Fortify/CreateNewUser.php`.
- **Notas técnicas:**
  - `users.tenant_id` → FK p/ `tenants` (`cascadeOnDelete`). Tenants renomeada `0000_...` porque o SQLite não adiciona FK via `ALTER`.
  - Relações: `User belongsTo Tenant`, `Tenant hasMany User`.
  - `tenant_id` **não** está no `#[Fillable]` do `User` — é setado via relação no servidor (`$tenant->users()->create(...)`), nunca por input (regra de ouro multi-tenant).
  - Registro (`CreateNewUser`) cria tenant + usuário dentro de `DB::transaction`. Cada cadastro = novo tenant.
  - **Verificação:** `php artisan test` → 39/39 verde (136 assertions); tinker confirma a relação.

### 2026-07-23 · Passo 1a — tabela `tenants` + model `Tenant` · ✅ concluído
- **Arquivos:** `database/migrations/2026_07_23_152656_create_tenants_table.php`, `app/Models/Tenant.php`.
- **Notas técnicas:**
  - Colunas: `id`, `name`, `slug` (unique), timestamps. Model com `$fillable = [name, slug]`.
  - `php artisan migrate` aplicou; índice `tenants_slug_unique` criado.
  - Ainda sem FK (users ganha `tenant_id` no passo 1b).

### 2026-07-23 · Bootstrap do projeto · ✅ concluído
- **Arquivos:** projeto inteiro (`composer.json`, `.env`, `database/`, `app/`, `routes/`).
- **Notas técnicas:**
  - Ambiente nativo Windows via **Laravel Herd** (PHP 8.4.23 + Composer 2.10.1). Docker descartado (só existe na VM; PC não roda Docker).
  - Scaffold: `laravel new supportai --react --database=sqlite --pest --npm --git`.
  - Migrations base aplicadas; SQLite operacional.
  - Site publicado no Herd (`herd link` + `herd secure`) → `https://supportai.test` responde 200.
