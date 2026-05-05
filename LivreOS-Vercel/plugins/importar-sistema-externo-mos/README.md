# Plugin: Importar de sistema externo (M-OS)

Importação de backup `.zip` / `.sql` gerado pelo export compatível com LivreOS (SQL + ficheiros + manifest em multi-parte v2).

## Utilidades

| Opção | Ficheiros |
|-------|-----------|
| **Importar de sistema externo (M-OS)** | `.sql` ou `.zip` com um ou mais `.sql`; ZIP completo com SQL + pasta de binários + `manifest.json`. Multi-parte v2: vários ZIPs, envio sequencial na página de Utilidades. |

- O handler lista todos os `.sql` no ZIP (ignora `__MACOSX`). Vários `.sql` são concatenados por ordem (prioridade: `livreos_m_os_export.sql`, depois `livreos_export.sql`).
- Manifest: aceita `m_os_arquivos/manifest.json` (export `exportacao_livreos_m_os.php`), `arquivos_mos/manifest.json` ou estrutura legada; `no_zip` com o mesmo prefixo.
- Memória: `MOS_IMPORT_MEMORY_LIMIT` no `.env` (ex.: `2048M`).

## Marcadores e metadados

- OS: `[MOS_OS_ID:X]` nas observações quando o id LivreOS difere do número na origem.
- Anexos OS: `metadata.mos_source_id` para cruzar com o manifest.
- Referências financeiras: prefixo `mos:lancamentos:` e `mos:vendas:` nas contas quando aplicável.

## Histórico da OS

Entradas importadas dos logs de origem usam `tipo` = `log_sistema_mos` no histórico da OS.
