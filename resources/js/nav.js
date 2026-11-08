const blockedPrefetchPaths = new Set(['/dashboard/money', '/dashboard/reports', '/reports']);

export function initializeNavigation() {
    const prefetchTimers = new WeakMap();
    const prefetchedUrls = new Set();

    const prefetchNavigation = (link) => {
        const url = new URL(link.href, window.location.href);
        if (
            !link.hasAttribute('data-prefetch')
            || url.origin !== window.location.origin
            || url.hash
            || blockedPrefetchPaths.has(url.pathname)
            || prefetchedUrls.has(url.href)
        ) {
            return;
        }

        prefetchedUrls.add(url.href);
        const hint = document.createElement('link');
        hint.rel = 'prefetch';
        hint.href = url.href;
        hint.as = 'document';
        document.head.append(hint);
    };

    document.addEventListener('pointerover', (event) => {
        const link = event.target.closest?.('a[data-prefetch]');
        if (!link || link.contains(event.relatedTarget)) {
            return;
        }

        const timer = window.setTimeout(() => prefetchNavigation(link), 65);
        prefetchTimers.set(link, timer);
    });

    document.addEventListener('pointerdown', (event) => {
        const link = event.target.closest?.('a[data-prefetch]');
        if (link) {
            prefetchNavigation(link);
        }
    });

    document.addEventListener('focusin', (event) => {
        const link = event.target.closest?.('a[data-prefetch]');
        if (link) {
            prefetchNavigation(link);
        }
    });

    document.addEventListener('pointerout', (event) => {
        const link = event.target.closest?.('a[data-prefetch]');
        if (!link || link.contains(event.relatedTarget)) {
            return;
        }

        window.clearTimeout(prefetchTimers.get(link));
    });

    document.addEventListener('touchstart', (event) => {
        const link = event.target.closest?.('a[data-prefetch]');
        if (link) {
            prefetchNavigation(link);
        }
    }, { passive: true });

}

export function handleNavigationClick(event) {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (
        !link
        || event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
        || link.target
        || link.hasAttribute('download')
    ) {
        return;
    }

    const url = new URL(link.href, window.location.href);
    if (
        url.origin !== window.location.origin
        || (url.pathname === window.location.pathname && url.search === window.location.search)
    ) {
        return;
    }

    window.setTimeout(() => document.documentElement.setAttribute('data-navigation-pending', ''), 300);
}
