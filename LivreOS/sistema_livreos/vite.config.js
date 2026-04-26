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

import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const base = env.VITE_BASE || '/';

    return {
        base,
        build: {
            sourcemap: false,
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
            host: '0.0.0.0',
            // URL que vai no arquivo "hot" e que o navegador usa para carregar os assets.
            origin: 'http://localhost:5173',
            // CORS: permitir requisições vindas de erp.test (senão o plugin usa só origin e bloqueia)
            cors: {
                origin: ['http://localhost:5173', 'http://erp.test'],
            },
            allowedHosts: ['erp.test', '.erp.test', 'localhost'],
        },
    };
});
