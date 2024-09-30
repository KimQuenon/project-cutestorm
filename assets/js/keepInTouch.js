document.addEventListener('DOMContentLoaded', function () {
    const texts = document.querySelectorAll('#keep-in-touch p');
    let index = 0;

    function animateText() {
        // display current text
        texts[index].classList.remove('opacity-0');
        texts[index].classList.add('opacity-100');

        // hide after 2 cycles
        let previousIndex = index - 2;
        if (previousIndex < 0) {
            previousIndex += texts.length;
        }
        texts[previousIndex].classList.remove('opacity-100');
        texts[previousIndex].classList.add('opacity-0');

        // next text
        index++;
        if (index >= texts.length) {
            index = 0; // reset to first element
        }
    }

    setInterval(animateText, 150);
});
