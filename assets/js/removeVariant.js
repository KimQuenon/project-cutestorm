document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#product-variants');
    const addButton = document.querySelector('#add-variant');
    const prototype = container.dataset.prototype;
    let index = container.querySelectorAll('.form-row').length;

    addButton.addEventListener('click', () => {
        const newElement = document.createElement('div');
        newElement.classList.add('form-row', 'mb-3');
        const html = prototype.replace(/__name__/g, index);
        newElement.innerHTML = html + '<button type="button" class="btn btn-danger remove-variant">-</button>';
        index++;
        container.appendChild(newElement);
    });

    container.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-variant')) {
            event.target.closest('.form-row').remove();
        }
    });
});