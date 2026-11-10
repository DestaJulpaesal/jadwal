export function initializeMotion() {
    const updateMotionState = () => {
        document.documentElement.classList.toggle('motion-paused', document.visibilityState === 'hidden');
    };

    const featureTiles = document.querySelectorAll('.concept-login-body .q');
    const heroIcons = document.querySelectorAll(
        '.concept-login-body .hero-mascot, .concept-login-body .hero-spark, .concept-login-body .hero-calendar, .concept-login-body .hero-wallet, .concept-login-body .register-mascot, .concept-login-body .register-spark'
    );
    const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');

    const animateElement = (element) => {
        if (motionPreference.matches) {
            return;
        }

        element.classList.remove('is-animating');
        void element.offsetWidth;
        element.classList.add('is-animating');
    };

    featureTiles.forEach((tile) => {
        tile.setAttribute('role', 'button');
        tile.setAttribute('tabindex', '0');

        tile.addEventListener('click', () => animateElement(tile));
        tile.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                animateElement(tile);
            }
        });
        tile.addEventListener('animationend', (event) => {
            if (event.animationName.startsWith('feature-')) {
                tile.classList.remove('is-animating');
            }
        });
    });

    heroIcons.forEach((icon) => {
        icon.addEventListener('click', () => animateElement(icon));
        icon.addEventListener('animationend', (event) => {
            if (event.animationName.startsWith('hero-')) {
                icon.classList.remove('is-animating');
            }
        });
    });

    document.addEventListener('visibilitychange', updateMotionState);
    updateMotionState();
}
