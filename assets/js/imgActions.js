document.addEventListener('DOMContentLoaded', function() {
    const imagesWrapper = document.getElementById('images-wrapper');
    let index = parseInt(imagesWrapper.dataset.index, 10);
    const prototype = imagesWrapper.dataset.prototype;
    const addImageButton = document.getElementById('add-image');

    const updateAddImageButtonState = () => {
        if (index >= 5) {
            addImageButton.disabled = true;
        } else {
            addImageButton.disabled = false;
        }
    };

    addImageButton.addEventListener('click', function() {
        if (index < 5) {
            const newForm = prototype.replace(/__name__/g, index);
            const div = document.createElement('div');
            div.classList.add('image-form', 'row', 'mb-3');
            div.innerHTML = '<div class="col">' + newForm + '</div><div class="col-auto"><button type="button" class="remove-image btn btn-danger mt-2">X</button></div>';
            imagesWrapper.appendChild(div);
            index++;
            updateAddImageButtonState();
        }
    });

    imagesWrapper.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-image')) {
            e.target.closest('.image-form').remove();
            index--;
            updateAddImageButtonState();
        }
    });

    updateAddImageButtonState(); // Initial state update
});
