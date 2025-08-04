import preset from './vendor/filament/support/tailwind.config.preset'

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        './vendor/filament/**/*.blade.php',

        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',

        './resources/views/filament/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php',

        './plugins/*/src/Filament/**/*.php',
        './plugins/*/src/Livewire/**/*.php',

        './plugins/*/resources/views/filament/**/*.blade.php',
        './plugins/*/resources/views/livewire/**/*.blade.php',
    ],
};
