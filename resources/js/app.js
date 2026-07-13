import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Toggle tema terang/gelap. Preferensi disimpan di localStorage; penerapan
// awal (sebelum render) dilakukan oleh skrip inline di <head> agar tidak FOUC.
window.toggleTheme = function () {
    const el = document.documentElement;
    el.classList.toggle('dark');
    try {
        localStorage.setItem('theme', el.classList.contains('dark') ? 'dark' : 'light');
    } catch (e) {}
};
