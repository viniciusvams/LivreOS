


<?php
    $editorHeight = $height ?? 220;
    $formId = $formId ?? null;
?>
<?php echo $__env->make('partials.jodit-assets', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
document.addEventListener('DOMContentLoaded', function initJoditAreaCliente() {
    if (typeof Jodit === 'undefined') return;
    var height = <?php echo e((int) $editorHeight); ?>;
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
    <?php if($formId): ?>
    var form = document.getElementById('<?php echo e($formId); ?>');
    if (form) form.addEventListener('submit', function() {
        document.querySelectorAll('.js-jodit').forEach(function(ta) { if (ta.jodit) ta.value = ta.jodit.value; });
    });
    <?php endif; ?>
});
</script>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\plugins\area-cliente\views\partials\jodit-editor.blade.php ENDPATH**/ ?>