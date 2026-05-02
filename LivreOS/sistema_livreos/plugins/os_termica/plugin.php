<?php

/**
 * Plugin Name: OS Impressão Térmica
 * Description: Adiciona botões para impressão de Ordens de Serviço e Comprovantes de Entrega em formato térmico (80mm ou 58mm).
 * Version: 1.0.0
 * Author: LivreOS
 * Requires PHP: 8.1
 */

require_once __DIR__ . '/src/OsTermicaSettingsController.php';
require_once __DIR__ . '/src/OsTermicaPrintController.php';

erp_register_activation_hook(__FILE__, function () {
    // Nenhuma tabela precisa ser criada, as opções usam a api interna update_option
});

// Hook para o index: entity.index.after_table
add_action('entity.index.after_table', function($entity) {
    if ($entity === 'ordem_servico') {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const rows = document.querySelectorAll("table tbody tr");
                rows.forEach(row => {
                    const checkbox = row.querySelector(".os-bulk-checkbox");
                    if (checkbox) {
                        const osId = checkbox.value;
                        const actionDiv = row.querySelector("td:last-child .flex");
                        if (actionDiv) {
                            const btnUrl = "/plugin/os_termica/imprimir/" + osId;
                            const btnHtml = `<a href="${btnUrl}" target="_blank" class="inline-flex items-center justify-center rounded p-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200" title="Imprimir OS Térmica">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </a>`;
                            actionDiv.insertAdjacentHTML("beforeend", btnHtml);
                        }
                    }
                });
            });
        </script>';
    }
});

// Hook para a edição: ordens_servico.form.scripts
add_action('ordens_servico.form.scripts', function($ordem, $isEdit) {
    if ($isEdit && $ordem) {
        $urlOs = url("/plugin/os_termica/imprimir/{$ordem->id}");
        $urlComprovante = url("/plugin/os_termica/imprimir-comprovante/{$ordem->id}");
        $isClosed = in_array($ordem->status, ['Encerrado', 'Encerrada'], true);
        
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const topBars = document.querySelectorAll(".mb-6 .flex-wrap.lg\\\\:justify-end, .mb-6 > .flex.w-full.lg\\\\:w-auto");
                
                // Tenta achar a div de botões correta na tela de edição
                let targetDiv = null;
                topBars.forEach(bar => {
                    if (bar.innerHTML.includes("Imprimir OS")) {
                        targetDiv = bar;
                    }
                });
                
                if (!targetDiv && topBars.length > 0) {
                    targetDiv = topBars[0];
                }

                if (targetDiv) {
                    const btnHtmlOs = `<a href="'.$urlOs.'" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" title="Imprimir OS Térmica">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M5 2.5A1.5 1.5 0 0 1 6.5 1h7A1.5 1.5 0 0 1 15 2.5V5H5V2.5zM4 6h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1.5v2.5A1.5 1.5 0 0 1 13 18H7a1.5 1.5 0 0 1-1.5-1.5V14H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm3 8v2h6v-2H7z"/></svg> OS Térmica
                    </a>`;
                    targetDiv.insertAdjacentHTML("beforeend", btnHtmlOs);
                    
                    ';
        if ($isClosed) {
            echo '
                    const btnHtmlComp = `<a href="'.$urlComprovante.'" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" title="Imprimir Comprovante Térmico">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M5 2.5A1.5 1.5 0 0 1 6.5 1h7A1.5 1.5 0 0 1 15 2.5V5H5V2.5zM4 6h12a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1.5v2.5A1.5 1.5 0 0 1 13 18H7a1.5 1.5 0 0 1-1.5-1.5V14H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm3 8v2h6v-2H7z"/></svg> Comprovante Térmico
                    </a>`;
                    targetDiv.insertAdjacentHTML("beforeend", btnHtmlComp);
            ';
        }
        echo '
                }
            });
        </script>';
    }
}, 10, 2);

// Menu item (inserir no menu principal)
add_filter('menu.items', function($items) {
    $items[] = [
        'name' => 'OS Térmica',
        'icon' => 'print', // Ícone de impressão
        'subItems' => [
            ['name' => 'Configurações', 'path' => '/plugin/os_termica/settings', 'pro' => false],
        ],
    ];
    return $items;
}, 20);
