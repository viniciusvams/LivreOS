


<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'videoId' => '',
    'aspectRatio' => '16:9',
    'title' => 'YouTube video',
    'className' => ''
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'videoId' => '',
    'aspectRatio' => '16:9',
    'title' => 'YouTube video',
    'className' => ''
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $aspectRatioClasses = [
        '16:9' => 'aspect-video',
        '4:3' => 'aspect-4/3',
        '21:9' => 'aspect-21/9',
        '1:1' => 'aspect-square',
    ];
    
    $aspectRatioClass = $aspectRatioClasses[$aspectRatio] ?? $aspectRatioClasses['16:9'];
?>

<div class="overflow-hidden rounded-lg <?php echo e($aspectRatioClass); ?> <?php echo e($className); ?>">
    <iframe
        src="https://www.youtube.com/embed/<?php echo e($videoId); ?>"
        title="<?php echo e($title); ?>"
        frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        allowfullscreen
        class="w-full h-full"
    ></iframe>
</div><?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\ui\youtube-embed.blade.php ENDPATH**/ ?>