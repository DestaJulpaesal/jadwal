export function initializeNetworkUi() {
    const networkBanner = document.querySelector('[data-network-banner]');
    const updateNetworkState = () => {
        if (networkBanner) {
            networkBanner.hidden = navigator.onLine;
        }
    };

    updateNetworkState();
    window.addEventListener('online', updateNetworkState);
    window.addEventListener('offline', updateNetworkState);
}
