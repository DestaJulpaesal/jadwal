export function initializeMotion() {
    const updateMotionState = () => {
        document.documentElement.classList.toggle('motion-paused', document.visibilityState === 'hidden');
    };
    document.addEventListener('visibilitychange', updateMotionState);
    updateMotionState();
}
