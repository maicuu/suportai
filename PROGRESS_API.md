# PROGRESS_API — SupportAI (backend Laravel)

> Changelog do backend. Datas absolutas (AAAA-MM-DD). Status: ✅ concluído · 🚧 em andamento · 📋 planejado.

## Estado atual
- **Stack:** Laravel 13.21.1 (PHP 8.4), starter kit React (Inertia 2 + Fortify).
- **Banco:** SQLite (`database/database.sqlite`). Migrations base rodadas (users, cache, jobs, passkeys, 2FA).
- **Auth:** Fortify (sessão/cookie — modo correto pra Inertia). 2FA e passkeys já vêm de fábrica.
- **Fila:** tabela `jobs` criada; driver a definir (começaremos em `database`).
- **Servido em:** `https://supportai.test` (Herd link + TLS).

### Pendências imediatas (Fase 1)
- [x] Tabela `tenants` + model `Tenant`.
- [ ] `tenant_id` em `users` (+ FK) e relação `Tenant`↔`User`.
- [ ] Global scope por tenant (isolamento na camada de query).
- [ ] Registro cria/associa tenant; login escopado.
- [ ] Tickets: migration + model + relações (messages), Form Request, API Resource.
- [ ] Reverb + Event broadcasting ao criar/responder ticket.
- [ ] Porta `AiProvider` + Job de classificação/rascunho (adapter Groq).

## Histórico

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
