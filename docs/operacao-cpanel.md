# Operacao LMS em cPanel

Checklist minimo para manter o EAD EPI saudavel em ambiente cPanel.

## Scheduler

Configurar cron para executar a cada minuto:

```bash
* * * * * cd /home/USUARIO/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Rotinas agendadas atuais:

- `lms:retention-alerts`: gera alertas de abandono e duvidas sem resposta.

## Filas

Para producao, configurar `QUEUE_CONNECTION=database` ou Redis quando disponivel.

Com database:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work --tries=3 --timeout=120
```

Em cPanel sem supervisor, usar cron a cada minuto:

```bash
* * * * * cd /home/USUARIO/public_html && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

## Backup

Rotina minima diaria:

- Backup do banco MySQL.
- Backup de `storage/app/public`.
- Backup do arquivo `.env`.
- Teste mensal de restore em ambiente separado.

Sugestao de comando MySQL:

```bash
mysqldump -u USUARIO -p BANCO > backups/ead-epi-$(date +\%F).sql
```

## Storage

Garantir:

```bash
php artisan storage:link
```

Pastas criticas:

- `storage/app/public/certificates`
- uploads de aulas/materiais

## Logs e auditoria

Monitorar:

- `storage/logs/laravel.log`
- tabela `activity_logs`
- eventos de certificado, nota, login e LGPD

## Certificados

Todo certificado deve possuir:

- `code`
- `verification_hash`
- `status`
- QR Code apontando para `/certificado/{code}`

Certificados revogados devem permanecer consultaveis como revogados, nunca apagados silenciosamente.

## LGPD

Operacoes obrigatorias:

- Exportacao de dados do aluno.
- Solicitacao de anonimização.
- Registro de versao de termos.
- Log de eventos sensiveis.
