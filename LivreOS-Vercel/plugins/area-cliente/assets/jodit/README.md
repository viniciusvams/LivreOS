# Jodit (plugin área cliente)

O editor passa a ser carregado a partir do **núcleo do ERP**: `public/vendor/jodit/` (ver `resources/views/partials/jodit-assets.blade.php`).

Esta pasta pode ficar vazia ou ser removida no futuro; não é mais necessário duplicar `jodit.min.js` / `jodit.min.css` aqui.

Para atualizar o Jodit no projeto: `npm install` e `npm run vendor:jodit` (ou apenas `npm install`, que executa o `postinstall`).
