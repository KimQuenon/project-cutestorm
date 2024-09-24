document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.change-quantity').forEach(button => {
        button.addEventListener('click', () => {
            const input = button.closest('td').querySelector('.stock-input');
            let currentValue = parseInt(input.value, 10);
            const change = parseInt(button.dataset.change, 10);
            const newValue = Math.max(currentValue + change, 0);
            input.value = newValue;
            input.dispatchEvent(new Event('change'));
        });
    });

    document.querySelectorAll('.stock-input').forEach(input => {
        input.addEventListener('change', () => {
            const initialStock = parseInt(input.dataset.initial, 10);
            const changeValue = parseInt(input.value, 10);
            const newStock = initialStock + changeValue;
            input.closest('tr').querySelector('.new-stock').textContent = newStock;
        });
    });
});