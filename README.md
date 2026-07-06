# EAD EPI - Escola do Parlamento de Itapevi

Plataforma institucional de ensino a distancia desenvolvida em Laravel, com portal publico, paineis administrativos, area do professor, area do aluno, certificacao, forum, chat e recursos de gestao academica.

> Projeto entregue em ambiente cPanel para a Escola do Parlamento de Itapevi.

## Visao Geral

O EAD EPI centraliza a oferta de cursos, inscricoes, acompanhamento de progresso, avaliacoes, certificados e comunicacao entre alunos, professores e administradores. A aplicacao foi estruturada para atender uma rotina real de educacao legislativa, cidadania e gestao publica.

## Principais Recursos

- Portal publico com home, catalogo de cursos, noticias, FAQ e validacao publica de certificados.
- Painel de gestao em Filament para administradores e equipe academica.
- Area do professor com cursos, aulas, materiais, avaliacoes, forum, chat e acompanhamento de alunos.
- Area do aluno com matriculas, aulas, progresso, provas, certificados, forum e comunicacao.
- Certificados em PDF com codigo unico e rota publica de validacao.
- Controle de papeis e permissoes com perfis de administrador, professor e aluno.
- Chat interno, forum academico, notificacoes e logs de atividade.
- Base preparada para deploy em hospedagem compartilhada com cPanel.

## Stack

- PHP 8.3+
- Laravel 12
- Filament 4
- Laravel Sanctum
- Spatie Laravel Permission
- MySQL/MariaDB em producao
- SQLite para ambiente local rapido
- Vue 3
- Pinia
- Vue Router
- Axios
- Bootstrap 5
- Vite
- DomPDF
- Simple QRCode
- Laravel Excel

## Estrutura De Acesso

```text
/                    Portal publico
/gestao              Painel administrativo
/professor           Painel do professor
/aluno               Painel do aluno
/certificado/{code}  Validacao publica de certificado
/api/documentation   Documentacao da API
```

## Requisitos

- PHP 8.3 ou superior
- Composer
- Node.js 20+ recomendado
- NPM
- MySQL 8+ ou MariaDB 10.4+
- Extensoes PHP comuns do Laravel habilitadas

## Instalacao Local

Clone o repositorio e instale as dependencias:

```bash
composer install
npm install
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

Configure o banco no `.env`. Para desenvolvimento local rapido, SQLite funciona bem:

```env
DB_CONNECTION=sqlite
```

Crie o arquivo SQLite, se necessario:

```bash
touch database/database.sqlite
```

Execute as migrations e seeders:

```bash
php artisan migrate --seed
```

Compile os assets:

```bash
npm run build
```

Suba o servidor local:

```bash
php artisan serve
```

## Usuarios De Demonstracao

Os seeders criam usuarios para validacao local:

```text
Administrador: admin@itapevi.sp.leg.br / password
Professor:     professor@itapevi.sp.leg.br / password
Aluno:         aluno@itapevi.sp.leg.br / password
```

> Em producao, troque as senhas imediatamente apos a primeira publicacao.

## Comandos Uteis

Limpar caches:

```bash
php artisan optimize:clear
```

Executar testes:

```bash
php artisan test
```

Build de frontend:

```bash
npm run build
```

Gerar link de storage:

```bash
php artisan storage:link
```

## Deploy Em cPanel

1. Envie o projeto para uma pasta da conta, preferencialmente fora de `public_html`.
2. Aponte o Document Root do dominio/subdominio para a pasta `public` do Laravel.
3. Crie o banco MySQL/MariaDB no cPanel e vincule um usuario com permissoes.
4. Configure o `.env` de producao:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nome_do_banco
DB_USERNAME=usuario_do_banco
DB_PASSWORD=senha_do_banco
```

5. Rode os comandos de otimizacao quando houver terminal/SSH:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Em hospedagens sem Node/Composer no servidor, gere `vendor` e `public/build` localmente e envie os arquivos prontos.

## API

Alguns endpoints principais:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/portal
GET  /api/portal/courses
GET  /api/dashboard
GET  /api/reports
GET  /api/certificate/validate/{code}
```

Rotas protegidas usam token Bearer:

```http
Authorization: Bearer {token}
```

## Seguranca

Arquivos sensiveis nao devem ser versionados:

```text
.env
database/database.sqlite
database/exports/*.sql
storage/logs/*
vendor/
node_modules/
public/build/
```

O projeto ja inclui regras de `.gitignore` para evitar que esses arquivos sejam enviados ao Git.

## Status

Versao inicial entregue e publicada em cPanel, com portal, paineis, banco de dados e fluxo de acesso funcionando.

## Licenca

Projeto privado/institucional. Verifique as condicoes de uso antes de redistribuir o codigo.
