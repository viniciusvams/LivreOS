

<div x-data="{
    isOpen: false,
    popperInstance: null,
    init() {
        this.$nextTick(() => {
            this.popperInstance = createPopper(this.$refs.button, this.$refs.content, {
                placement: 'bottom-end',
                strategy: 'fixed',
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, 4],
                        },
                    },
                ],
            });
        });
    },
    toggle() {
        this.isOpen = !this.isOpen;
        if (this.popperInstance) {
            this.popperInstance.update();
        }
    }
}"
@click.away="isOpen = false">
    <div @click="toggle()" x-ref="button" class="cursor-pointer">
        <?php echo e($button); ?>

    </div>

    <div class="z-50 fixed" x-ref="content">
        <div x-show="isOpen" x-cloak class="p-2 bg-white border border-gray-200 rounded-2xl shadow-lg dark:border-gray-800 dark:bg-gray-dark w-40">
            <div class="space-y-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                <?php echo e($content); ?>

            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\LivreOS\LivreOS\sistema_livreos\resources\views\components\common\table-dropdown.blade.php ENDPATH**/ ?>