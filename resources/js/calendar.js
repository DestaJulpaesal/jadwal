const initializeCalendar = () => {
    const calendar = document.getElementById('user-calendar');
    if (!calendar) {
        return;
    }

    const selectDay = (day) => {
        calendar.querySelectorAll('[data-calendar-day]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.calendarDay === day));
        });
        calendar.querySelectorAll('[data-calendar-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.calendarPanel !== day;
        });
    };

    calendar.addEventListener('click', (event) => {
        const button = event.target.closest('[data-calendar-day]');
        if (button && calendar.contains(button)) {
            selectDay(button.dataset.calendarDay);
        }
    });

    selectDay(calendar.dataset.selectedDay);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCalendar, { once: true });
} else {
    initializeCalendar();
}
