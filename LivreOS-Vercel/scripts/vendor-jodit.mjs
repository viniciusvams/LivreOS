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

/**
 * Copia o build es2021 do Jodit de node_modules para public/vendor/jodit/
 * Uso: npm run vendor:jodit
 */
import { copyFileSync, mkdirSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const srcDir = join(root, 'node_modules', 'jodit', 'es2021');
const destDir = join(root, 'public', 'vendor', 'jodit');
const files = ['jodit.min.js', 'jodit.min.css'];

if (!existsSync(srcDir)) {
    console.error('Jodit não encontrado em node_modules. Execute: npm install');
    process.exit(1);
}

mkdirSync(destDir, { recursive: true });
for (const f of files) {
    copyFileSync(join(srcDir, f), join(destDir, f));
    console.log('copiado:', f);
}
