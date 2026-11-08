import Alpine from '@alpinejs/csp';

Alpine.data('dropdown', () => ({
    open: false,

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    },
}));

Alpine.data('adminSidebar', () => ({
    sidebarOpen: false,

    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
    },

    closeSidebar() {
        this.sidebarOpen = false;
    },

    get sidebarClass() {
        return this.sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0';
    },
}));

Alpine.data('modal', (initiallyShown = false) => ({
    show: initiallyShown,

    init() {
        this.$watch('show', (isShown) => {
            document.body.classList.toggle('overflow-y-hidden', isShown);

            if (isShown && this.$el.hasAttribute('data-focusable')) {
                window.setTimeout(() => this.focusables()[0]?.focus(), 100);
            }
        });
    },

    openModal(event) {
        if (event.detail === this.$el.dataset.modalName) {
            this.show = true;
        }
    },

    closeModal(event) {
        if (event.detail === this.$el.dataset.modalName) {
            this.show = false;
        }
    },

    focusables() {
        const selector = 'a, button, input:not([type="hidden"]), textarea, select, details, [tabindex]:not([tabindex="-1"])';

        return [...this.$el.querySelectorAll(selector)].filter((element) => !element.hasAttribute('disabled'));
    },

    trapFocus(event) {
        const focusables = this.focusables();
        if (!focusables.length) {
            return;
        }

        const activeIndex = focusables.indexOf(document.activeElement);
        const nextIndex = event.shiftKey
            ? (activeIndex <= 0 ? focusables.length - 1 : activeIndex - 1)
            : (activeIndex < 0 || activeIndex === focusables.length - 1 ? 0 : activeIndex + 1);

        focusables[nextIndex].focus();
    },

    requestClose() {
        const form = this.$el.querySelector('form[data-dirty-guard]');
        if (form && window.isDirtyForm(form)) {
            window.confirmDiscardChanges(() => {
                this.show = false;
            });
            return;
        }

        this.show = false;
    },
}));

Alpine.data('rupiahInput', (initial = '') => ({
    display: '',
    raw: '',

    init() {
        this.formatValue(initial);
    },

    get preview() {
        return this.raw ? `Rp ${this.display}` : 'Masukkan nominal dalam Rupiah';
    },

    format(event) {
        this.formatValue(event.target.value);
    },

    formatValue(value) {
        const text = String(value ?? '');
        const negative = text.trim().startsWith('-');
        const digits = text.replace(/\D/g, '');
        const sign = negative && digits ? '-' : '';

        this.raw = `${sign}${digits}`;
        this.display = `${sign}${digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;
    },
}));

Alpine.start();
