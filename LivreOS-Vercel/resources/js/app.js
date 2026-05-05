/**
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
 */

import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
// FullCalendar
import { Calendar } from '@fullcalendar/core';



window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;
window.FullCalendar = Calendar;

/**
 * Aviso não bloqueante (substitui alert em fluxos onde faz sentido).
 * @param {string} message
 * @param {'info'|'warning'|'error'|'success'} [type]
 * @param {number} [durationMs] 0 = só fecha manualmente
 */
window.erpToast = function erpToast(message, type = 'warning', durationMs = 9000) {
    if (!message) return;
    const rootId = 'erp-toast-stack';
    let root = document.getElementById(rootId);
    if (!root) {
        root = document.createElement('div');
        root.id = rootId;
        root.setAttribute('aria-live', 'polite');
        root.className =
            'fixed bottom-4 right-4 z-[9999] flex max-w-md flex-col gap-2 p-0 sm:bottom-6 sm:right-6';
        document.body.appendChild(root);
    }
    const palette = {
        warning:
            'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-600 dark:bg-amber-950/90 dark:text-amber-50',
        error:
            'border-red-300 bg-red-50 text-red-950 dark:border-red-600 dark:bg-red-950/90 dark:text-red-50',
        success:
            'border-green-300 bg-green-50 text-green-950 dark:border-green-600 dark:bg-green-950/90 dark:text-green-50',
        info: 'border-blue-300 bg-blue-50 text-blue-950 dark:border-blue-600 dark:bg-blue-950/90 dark:text-blue-50',
    };
    const wrap = document.createElement('div');
    wrap.className = `flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg ${palette[type] || palette.warning}`;
    wrap.setAttribute('role', 'status');

    const text = document.createElement('p');
    text.className = 'min-w-0 flex-1 whitespace-pre-wrap leading-relaxed';
    text.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className =
        'shrink-0 rounded-md p-1 text-lg leading-none opacity-70 transition hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10';
    closeBtn.setAttribute('aria-label', 'Fechar');
    closeBtn.textContent = '×';

    let hideTimer = null;
    const remove = () => {
        if (hideTimer) clearTimeout(hideTimer);
        wrap.remove();
        if (root && root.childElementCount === 0) {
            root.remove();
        }
    };

    closeBtn.addEventListener('click', remove);
    wrap.appendChild(text);
    wrap.appendChild(closeBtn);
    root.appendChild(wrap);

    if (durationMs > 0) {
        hideTimer = setTimeout(remove, durationMs);
    }
};

Alpine.start();

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#chartSix')) {
        import('./components/chart/chart-6').then(module => module.initChartSix());
    }
    if (document.querySelector('#chartEight')) {
        import('./components/chart/chart-8').then(module => module.initChartEight());
    }
    if (document.querySelector('#chartThirteen')) {
        import('./components/chart/chart-13').then(module => module.initChartThirteen());
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }
});