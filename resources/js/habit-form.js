const initializeHabitForm = () => {
    const form = document.querySelector('.habit-form');
    if (!form) {
        return;
    }

    const frequencyFields = form.querySelectorAll('[name="frequency"]');
    const weekdays = document.getElementById('weekdays-field');
    const weeklyTarget = document.getElementById('weekly-target-field');
    const help = document.getElementById('frequency-help');
    const goalValue = document.getElementById('goal_value');
    const goalUnit = document.getElementById('goal_unit');
    const goalUnitOther = document.getElementById('goal_unit_other');
    const targetPreview = document.getElementById('habit-target-preview');

    const updateFrequency = () => {
        const frequency = form.querySelector('[name="frequency"]:checked')?.value ?? 'daily';
        const isWeekdays = frequency === 'weekdays';
        const isWeekly = frequency === 'weekly_count';

        weekdays.hidden = !isWeekdays;
        weeklyTarget.hidden = !isWeekly;
        weekdays.querySelectorAll('input').forEach((input) => {
            input.disabled = !isWeekdays;
        });
        weeklyTarget.querySelectorAll('input').forEach((input) => {
            input.disabled = !isWeekly;
            input.required = isWeekly;
        });
        help.textContent = isWeekdays
            ? 'Pilih hari yang memang ingin kamu lakukan. Hari lain tidak dihitung sebagai gagal.'
            : isWeekly
                ? 'Contoh: target 3 kali berarti cukup selesai tiga kali dalam seminggu.'
                : 'Setiap hari berarti semua hari masuk perhitungan.';
    };

    const updateTargetPreview = () => {
        const value = goalValue.value.trim();
        const unit = goalUnit.value === 'other' ? goalUnitOther.value.trim() : goalUnit.value.trim();
        targetPreview.querySelector('span').textContent = value && unit
            ? `Targetmu: ${value} ${unit} setiap kali dilakukan.`
            : 'Targetmu akan terlihat di sini setelah angka dan satuan diisi.';
        targetPreview.classList.toggle('is-ready', Boolean(value && unit));
    };

    const updateGoalUnit = () => {
        const isOther = goalUnit.value === 'other';
        goalUnitOther.hidden = !isOther;
        goalUnitOther.disabled = !isOther;
        if (!isOther) {
            goalUnitOther.value = '';
        }
    };

    frequencyFields.forEach((field) => field.addEventListener('change', updateFrequency));
    goalValue.addEventListener('input', updateTargetPreview);
    goalUnit.addEventListener('input', updateTargetPreview);
    goalUnitOther.addEventListener('input', updateTargetPreview);
    goalUnit.addEventListener('change', updateGoalUnit);

    form.querySelectorAll('input[type="number"]').forEach((input) => {
        input.addEventListener('wheel', () => input.blur(), { passive: true });
    });
    document.querySelectorAll('.habit-checkin-fields input[type="number"]').forEach((input) => {
        input.addEventListener('wheel', () => input.blur(), { passive: true });
    });

    updateFrequency();
    updateGoalUnit();
    updateTargetPreview();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeHabitForm, { once: true });
} else {
    initializeHabitForm();
}
