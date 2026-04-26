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

{{-- Bloco de configurações do plugin (Exportar, Importar, Ajuda) na página do plugin --}}
<div class="mt-8 rounded-lg border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Dados e Ajuda</h2>
    </div>
    <div class="p-6 space-y-8">
        {{-- Exportar --}}
        <section>
            <h3 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">Exportar todos os dados</h3>
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                Gera um arquivo ZIP com todas as tabelas do plugin (tickets, respostas, anexos) e os arquivos anexados. Use para backup ou para restaurar em outro sistema.
            </p>
            <a href="{{ route('plugin.area-cliente.configuracoes.export') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 dark:bg-brand-600 dark:hover:bg-brand-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Exportar dados (ZIP)
            </a>
        </section>

        {{-- Importar --}}
        <section>
            <h3 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">Importar dados</h3>
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                Restaure um backup exportado (arquivo .zip ou .json). Os registros serão inseridos mantendo clientes e usuários existentes. Anexos são restaurados se o ZIP contiver a pasta de arquivos.
            </p>
            <a href="{{ route('plugin.area-cliente.configuracoes.import') }}" class="inline-flex items-center gap-2 rounded-lg border-2 border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4 4m0 0l-4-4m4 4V4"/></svg>
                Abrir tela de importação
            </a>
        </section>

        {{-- Ajuda: passo a passo para administradores --}}
        <section id="ajuda-plugin-area-cliente">
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Ajuda — Como funciona e como usar o plugin</h3>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Passo a passo para quem está começando. O plugin oferece um <strong>portal do cliente</strong> (onde o cliente vê ordens de serviço e tickets) e uma <strong>central de atendimento</strong> no sistema (onde sua equipe atende os tickets).
            </p>

            <div class="mb-6 rounded-xl border-2 border-brand-200 bg-brand-50 p-4 dark:border-brand-700 dark:bg-brand-900/20">
                <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">URL para o cliente acessar a Área do Cliente</h4>
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Informe ao cliente o endereço abaixo para ele acessar o portal (após fazer login no sistema com o usuário vinculado ao cliente):</p>
                <p class="break-all rounded-lg bg-white px-3 py-2 font-mono text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{ url()->route('plugin.area-cliente.index') }}</p>
            </div>

            <div class="space-y-6 text-sm">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">1. Ativar o plugin e liberar o portal para o cliente</h4>
                    <ul class="list-inside list-disc space-y-1 text-gray-600 dark:text-gray-400">
                        <li>Em <strong>Configurações do Sistema → Plugins</strong>, ative o plugin <strong>Área do Cliente</strong> (se ainda não estiver ativo).</li>
                        <li>No cadastro do <strong>Cliente</strong>, marque a opção <strong>Portal do cliente / Portal suporte</strong> para que esse cliente possa acessar a área do cliente.</li>
                        <li>Crie um <strong>usuário</strong> vinculado a esse cliente (campo Cliente no usuário) e informe o login/senha ao cliente.</li>
                    </ul>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">2. O que o cliente vê no portal</h4>
                    <ul class="list-inside list-disc space-y-1 text-gray-600 dark:text-gray-400">
                        <li><strong>Minhas Ordens:</strong> lista e detalhes das ordens de serviço daquele cliente (conforme permissões).</li>
                        <li><strong>Tickets:</strong> abrir novos tickets de suporte e acompanhar respostas da equipe.</li>
                        <li><strong>Meu Perfil:</strong> alterar senha e dados de acesso.</li>
                    </ul>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">3. Atendimento no sistema (sua equipe)</h4>
                    <ul class="list-inside list-disc space-y-1 text-gray-600 dark:text-gray-400">
                        <li>No menu do sistema, acesse <strong>Central de tickets</strong> ou <strong>Listar tickets</strong> (conforme seu menu).</li>
                        <li>Você verá todos os tickets abertos pelos clientes. Altere o <strong>status</strong> (Aberto, Em atendimento, Aguardando cliente, Resolvido etc.) e adicione <strong>respostas</strong> (visíveis ao cliente) ou <strong>observações internas</strong> (só para a equipe).</li>
                        <li>Se precisar transformar um ticket em ordem de serviço, use o botão <strong>Converter em OS</strong> na tela do ticket.</li>
                    </ul>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">4. Fluxo do ticket (resumo)</h4>
                    <ul class="list-inside list-disc space-y-1 text-gray-600 dark:text-gray-400">
                        <li>Cliente abre o ticket no portal (título, descrição, prioridade, anexos).</li>
                        <li>Equipe responde e altera status (ex.: Aguardando cliente, Resolvido aguardando confirmação).</li>
                        <li>Cliente pode confirmar a solução, reabrir o ticket ou desistir, conforme o status.</li>
                    </ul>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
                    <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">5. Exportar e importar</h4>
                    <ul class="list-inside list-disc space-y-1 text-gray-600 dark:text-gray-400">
                        <li><strong>Exportar:</strong> gera um ZIP com todos os tickets, respostas e anexos (backup completo).</li>
                        <li><strong>Importar:</strong> use um arquivo .zip ou .json exportado por este plugin para restaurar dados em outro ambiente ou após migração.</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</div>
