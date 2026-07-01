<div
    x-data="filamentLogo"
    x-on:dark-mode-toggled.window="toggle"
>
    <img src="<?php echo e(asset('/images/logo-black.png')); ?>" alt="<?php echo e(env('APP_NAME')); ?> Logo" class="h-10"
         x-show="isLightMode">

    <img src="<?php echo e(asset('/images/logo-white.png')); ?>" alt="<?php echo e(env('APP_NAME')); ?> Dark Logo" class="h-10"
         x-show="isDarkMode">
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('filamentLogo', () => ({
            init() {
                this.toggle()
            },
            mode: 'light',
            toggle() {
                this.mode = document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            },
            isDarkMode() {
                return this.mode === 'dark'
            },
            isLightMode() {
                return this.mode === 'light'
            }
        }))
    })
</script>
<?php /**PATH E:\xampp\htdocs\Upwork\Fannan-Backend-10Dec\resources\views/vendor/filament-panels/components/logo.blade.php ENDPATH**/ ?>