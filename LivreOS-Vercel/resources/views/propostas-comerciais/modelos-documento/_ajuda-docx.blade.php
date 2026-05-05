{{--
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 --}}

{{-- Ajuda: modelos Word (.docx) e HTML (.htm / .html) — mesmas tags ${...} que {@see PropostaComercialModeloVariaveisService} --}}
<div class="space-y-4">
    <div class="rounded-lg border border-teal-100 bg-teal-50/80 p-4 text-sm text-gray-800 dark:border-teal-500/25 dark:bg-teal-950/30 dark:text-gray-200">
        <h2 class="mb-2 text-base font-semibold text-teal-900 dark:text-teal-100">Modelo HTML (.html / .htm)</h2>
        <ol class="mb-2 list-decimal space-y-1 pl-5 text-xs leading-relaxed text-gray-700 dark:text-gray-300">
            <li>Pode ser um ficheiro completo (<code>&lt;!DOCTYPE html&gt;</code>…) ou só o conteúdo do <code>&lt;body&gt;</code> — o ERP envolve automaticamente se faltar.</li>
            <li>Use as <strong>mesmas variáveis</strong> <code class="rounded bg-white/80 px-1 dark:bg-gray-900">${nome}</code> que no Word (lista abaixo), mais <code class="rounded bg-white/80 px-1 dark:bg-gray-900">${itens_tabela_linhas}</code> para a grelha de itens.</li>
            <li>Para a tabela de itens, dentro de um <code>&lt;tbody&gt;</code>, coloque <code class="rounded bg-white/80 px-1 dark:bg-gray-900">${itens_tabela_linhas}</code> — o sistema insere as linhas <code>&lt;tr&gt;…&lt;/tr&gt;</code> (ou use uma linha modelo com <code>${item_linha}</code> … <code>${item_total}</code>, como no Word).</li>
            <li><strong>Logótipo:</strong> <code class="rounded bg-white/80 px-1 dark:bg-gray-900">${empresa_logo}</code> em <code>src</code> de <code>&lt;img&gt;</code> — data URI ou URL (empresa, depois logo do sistema em <code>public/</code>); vazio se não houver ficheiro. <strong>Não use só dentro de <code>&lt;span&gt;</code></strong> (não mostra imagem); o sistema converte <code>&lt;span&gt;${empresa_logo}&lt;/span&gt;</code> com só esse texto para <code>&lt;img&gt;</code>.</li>
            <li><strong>Cliente PF / PJ:</strong> use <code class="rounded bg-white/80 px-1 dark:bg-gray-900">${cliente_classe_tipo_body}</code> no <code>class</code> do <code>&lt;body&gt;</code> e blocos + CSS — ver <code class="rounded bg-white/80 px-1 dark:bg-gray-900">docs/PROPOSTA_HTML_02.md</code> (referência padrão <strong>02</strong>).</li>
            <li><strong>Texto rico (HTML) na proposta:</strong> <code>${proposta_descricao}</code>, <code>${observacoes}</code> e <code>${observacoes_internas}</code> são <strong>texto plano</strong> (tags removidas) — ideais para Word e texto simples no HTML. Para <strong>manter formatação só em modelos HTML</strong>, use <code>${proposta_descricao_html}</code>, <code>${observacoes_html}</code> e <code>${observacoes_internas_html}</code> (HTML tal como guardado; <strong>não</strong> existem no .docx).</li>
            <li><strong>Scripts</strong> e <strong>iframes</strong> são removidos no upload por segurança.</li>
        </ol>
    </div>

    <div class="rounded-lg border border-indigo-100 bg-indigo-50/80 p-4 text-sm text-gray-800 dark:border-indigo-500/25 dark:bg-indigo-950/30 dark:text-gray-200">
        <h2 class="mb-2 flex items-center gap-2 text-base font-semibold text-indigo-900 dark:text-indigo-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Modelo Word (.docx)
        </h2>
        <ol class="mb-4 list-decimal space-y-1 pl-5 text-xs leading-relaxed text-gray-700 dark:text-gray-300">
            <li>Crie o documento no Word e guarde como <strong>.docx</strong> (não .doc antigo).</li>
            <li>Digite as <strong>tags exatamente</strong> como abaixo, incluindo <code class="rounded bg-white/80 px-1 dark:bg-gray-900">${</code> e <code class="rounded bg-white/80 px-1 dark:bg-gray-900">}</code>.</li>
            <li><strong>Não</strong> formate só parte da tag (negrito no meio de <code>${cliente_nome}</code>) — o Word parte o texto e o sistema pode deixar de encontrar a variável.</li>
            <li><strong><code>${proposta_descricao}</code> e textos longos:</strong> prefira escrever a tag de seguida ou colar <em>só texto</em> (ex. copiar do Bloco de notas e <em>Ctrl+Shift+V</em> no Word). Assim evita caracteres Unicode que parecem <code>$</code> ou <code>{ }</code> mas não são — o preenchimento falha ou deixa a tag visível no documento final.</li>
            <li>No Word use sempre <code>${proposta_descricao}</code> (texto plano). <strong>Não</strong> use <code>${proposta_descricao_html}</code> no .docx — no ERP essa tag só é preenchida em modelos HTML.</li>
            <li>Para <strong>PDF com o mesmo aspeto do Word</strong>, descarregue o .docx e use <em>Ficheiro → Guardar como → PDF</em> no Microsoft Word.</li>
        </ol>

        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">Variáveis escalares (Word e HTML)</h3>
        <p class="mb-2 text-[11px] leading-relaxed text-gray-600 dark:text-gray-400">Lista alinhada ao código do ERP. Valores vazios quando não aplicável (ex.: CNPJ em PF).</p>
        <div class="mb-4 max-h-[28rem] overflow-y-auto rounded border border-indigo-100/80 bg-white/60 p-3 font-mono text-[11px] leading-relaxed dark:border-indigo-500/20 dark:bg-gray-900/40">
            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Proposta</p>
            <p class="mb-2">${proposta_id} ${proposta_versao} ${proposta_titulo}</p>
            <p class="mb-2">${proposta_status} ${proposta_status_label} ${proposta_referencia} ${proposta_numero_sequencial}</p>
            <p class="mb-3">${data_emissao} ${validade_em} ${validade_dias}</p>

            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Cliente</p>
            <p class="mb-2">${cliente_nome} ${cliente_razao_social} ${cliente_documento} ${cliente_documento_rotulo}</p>
            <p class="mb-2">${cliente_classe_tipo_body} ${cliente_tipo_pessoa} ${cliente_tipo_pessoa_label}</p>
            <p class="mb-2">${cliente_cpf} ${cliente_cnpj} ${cliente_rg} ${cliente_data_nascimento}</p>
            <p class="mb-3">${cliente_inscricao_estadual} ${cliente_ie_uf} ${cliente_inscricao_municipal} ${cliente_documento_estrangeiro} ${cliente_pais_origem}</p>

            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Comercial</p>
            <p class="mb-3">${vendedor_nome} ${tabela_preco_nome}</p>

            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Totais (pt-BR)</p>
            <p class="mb-3">${total_produtos} ${total_servicos} ${total_descontos} ${total_geral}</p>

            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Textos longos <span class="font-normal normal-case text-gray-500">(sem HTML)</span></p>
            <p class="mb-3">${proposta_descricao} ${observacoes} ${observacoes_internas}</p>

            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Empresa e geração</p>
            <p class="mb-2">${empresa_nome} ${empresa_razao_social} ${empresa_cnpj} ${empresa_endereco}</p>
            <p class="mb-2">${empresa_telefone} ${empresa_celular} ${empresa_email}</p>
            <p class="mb-2">${empresa_logo} ${gerado_em}</p>
            <p class="mb-3 font-sans text-[10px] text-gray-500 dark:text-gray-400">${empresa_logo} — no Word, marcador de <strong>imagem</strong>; no HTML, use em <code>src</code> do <code>&lt;img&gt;</code>.</p>

            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Só HTML — campos ricos (HTML cru, sem escape)</p>
            <p class="mb-2">${proposta_descricao_html} ${observacoes_html} ${observacoes_internas_html}</p>
            <p class="mb-1 font-sans text-[10px] font-normal text-gray-500 dark:text-gray-400">Envolva em <code>&lt;div class="…"&gt;</code> no modelo se precisar de CSS. Não use estas tags em modelos Word.</p>

            <p class="mb-1 font-sans text-[10px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Só HTML — corpo da tabela de itens</p>
            <p>${itens_tabela_linhas}</p>
        </div>

        <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">Bloco de itens (por linha)</h3>
        <p class="mb-2 text-xs leading-relaxed text-gray-700 dark:text-gray-300">
            Cada linha da proposta expõe as mesmas chaves. No <strong>Word</strong>: tabela com <code class="rounded bg-white/80 px-1 dark:bg-gray-900">${item_linha}</code> na primeira linha de dados — o sistema duplica a linha. No <strong>HTML</strong>: use <code>${itens_tabela_linhas}</code> no <code>&lt;tbody&gt;</code> ou um <code>&lt;tr&gt;</code> modelo com as tags abaixo.
        </p>
        <div class="mb-2 rounded border border-indigo-100/80 bg-white/60 p-2 font-mono text-[11px] dark:border-indigo-500/20 dark:bg-gray-900/40">
            ${item_linha} ${item_tipo} ${item_descricao} ${item_quantidade} ${item_preco_unitario} ${item_desconto} ${item_desconto_percentual} ${item_total}
        </div>

        <p class="mt-4 text-[11px] text-gray-500 dark:text-gray-500">
            <strong>Extensões:</strong> filtro <code>proposta_comercial.docx.variaveis</code> (Word e HTML escalares), <code>proposta_comercial.html_modelo.variaveis</code> (HTML), <code>proposta_comercial.html_modelo.*</code> — ver <code>docs/PLUGINS.md</code>. Referência HTML: <code>docs/PROPOSTA_HTML_02.md</code> (padrão <strong>02</strong>).
        </p>
    </div>
</div>
