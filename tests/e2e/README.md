# Testes E2E

Estes testes rodam com Playwright contra o servidor local.

Pré-requisitos:

- servidor Laravel ativo em `http://127.0.0.1:8003`;
- banco com os usuários seedados:
  - `admin@itapevi.sp.leg.br`
  - `professor@itapevi.sp.leg.br`
  - `aluno@itapevi.sp.leg.br`
  - senha: `password`

Comandos:

```bash
npm run e2e
npm run e2e:headed
```

Para usar outra URL:

```bash
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8004 npm run e2e
```
