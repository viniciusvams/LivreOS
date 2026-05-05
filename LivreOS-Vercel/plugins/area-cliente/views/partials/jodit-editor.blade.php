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

{{-- Editor Jodit: assets do núcleo do ERP (public/vendor/jodit); emoji/smiles --}}
@php
    $editorHeight = $height ?? 220;
    $formId = $formId ?? null;
@endphp
@include('partials.jodit-assets')
<script>
document.addEventListener('DOMContentLoaded', function initJoditAreaCliente() {
    if (typeof Jodit === 'undefined') return;
    var height = {{ (int) $editorHeight }};
    var emojiList = ['😀','😃','😄','😁','😅','😂','🤣','😊','😇','🙂','😉','😍','🥰','😘','👍','👎','👏','🙌','🤝','❤️','💙','💚','💛','🧡','💜','🖤','💯','✅','❌','⚠️','📌','🔔','💡','📎','📁','📂','📝','✏️','🔍','❓','❗','⭐','🌟','🔥','💪','🤔','😎','😢','😭','🙁','😤','😡','🤬','😴','👍','👋','🙏'];
    if (typeof Jodit.defaultOptions !== 'undefined' && Jodit.defaultOptions.controls) {
        Jodit.defaultOptions.controls.smiles = {
            name: 'smiles',
            tooltip: 'Inserir emoji',
            list: emojiList,
            exec: function(editor, current, btn) {
                if (!btn || !btn.control || !btn.control.args || !btn.control.args.length) return false;
                var emoji = btn.control.args[0];
                if (editor.selection && editor.selection.insertHTML) editor.selection.insertHTML(emoji);
                return true;
            }
        };
    }
    document.querySelectorAll('.js-jodit').forEach(function(textarea) {
        var opts = {
            theme: 'default',
            toolbarAdaptive: true,
            buttons: ['bold', 'italic', 'underline', '|', 'ul', 'ol', '|', 'link', 'smiles', '|', 'hr', 'source'],
            height: height
        };
        var editor = Jodit.make(textarea, opts);
        editor.events.on('change', function() { textarea.value = editor.value; });
    });
    @if($formId)
    var form = document.getElementById('{{ $formId }}');
    if (form) form.addEventListener('submit', function() {
        document.querySelectorAll('.js-jodit').forEach(function(ta) { if (ta.jodit) ta.value = ta.jodit.value; });
    });
    @endif
});
</script>
