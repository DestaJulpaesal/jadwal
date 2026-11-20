export function initializeNavigation() {
    document.querySelectorAll('[data-subnav]').forEach((nav) => {
        const track = nav.querySelector('.user-subnav-track');
        const activeLink = nav.querySelector('[data-subnav-active]');
        if (!(track instanceof HTMLElement) || !(activeLink instanceof HTMLElement) || track.scrollWidth <= track.clientWidth) {
            return;
        }

        const trackRect = track.getBoundingClientRect();
        const linkRect = activeLink.getBoundingClientRect();
        const left = track.scrollLeft + linkRect.left - trackRect.left - (track.clientWidth - linkRect.width) / 2;
        const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

        track.scrollTo({ left, behavior });
    });

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
