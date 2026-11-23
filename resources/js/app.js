const formatThousands = (value) => {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-thousands]');
    if (!input) return;
    const cursor = input.selectionStart;
    const before = input.value.slice(0, cursor).replace(/\D/g, '').length;
    input.value = formatThousands(input.value);
    let position = 0, seen = 0;
    while (position < input.value.length && seen < before) {
        if (/\d/.test(input.value[position])) seen++;
        position++;
    }
    input.setSelectionRange(position, position);
});

document.addEventListener('submit', (event) => {
    event.target.querySelectorAll('[data-thousands]').forEach((input) => {
        input.value = input.value.replace(/\./g, '');
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-thousands]').forEach((input) => {
        input.value = formatThousands(input.value);
    });
});
