document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#product-variants');
    const addButton = document.querySelector('#add-variant');
    const prototype = container.dataset.prototype;
    let index = container.querySelectorAll('.form-row').length;

    //add variant field (size + stock)
    addButton.addEventListener('click', () => {
        const newElement = document.createElement('div');
        newElement.classList.add('form-row', 'mb-3');
        const html = prototype.replace(/__name__/g, index);
        newElement.innerHTML = html + '<button type="button" class="badge badge-danger remove-variant">-</button>';
        index++;
        container.appendChild(newElement);
    });

    // remove variant field
    container.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-variant')) {
            event.target.closest('.form-row').remove();
        }
    });
});