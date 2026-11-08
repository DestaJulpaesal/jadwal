import './bootstrap';
import '@fontsource/figtree/400.css';
import '@fontsource/figtree/500.css';
import '@fontsource/figtree/600.css';
import '@fontsource/figtree/700.css';
import './forms';
import { initializeMotion } from './motion';
import { initializeNetworkUi } from './network';
import { initializeNavigation } from './nav';
import { initializePasswordUi } from './password';
import { initializePrivacyUi } from './privacy';
import { initializeToastQueue } from './toast';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.rupiahInput = (initial = '') => ({
    display: '',
    raw: '',
    init() {
        const value = String(initial ?? '');
        const negative = value.startsWith('-');
        const digits = value.replace(/\D/g, '');
        this.raw = negative && digits ? `-${digits}` : digits;
        this.display = `${negative && digits ? '-' : ''}${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
    },
    get preview() {
        return this.raw ? `Rp ${this.display}` : 'Masukkan nominal dalam Rupiah';
    },
    format(event) {
        const value = event.target.value;
        const negative = value.trim().startsWith('-');
        const digits = value.replace(/\D/g, '');
        this.raw = negative && digits ? `-${digits}` : digits;
        this.display = `${negative && digits ? '-' : ''}${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
    },
});

Alpine.start();

const initializeApplicationUi = () => {
    initializeNavigation();
    initializeMotion();
    initializeNetworkUi();
    initializeToastQueue();
    initializePasswordUi();
    initializePrivacyUi();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApplicationUi, { once: true });
} else {
    initializeApplicationUi();
}
