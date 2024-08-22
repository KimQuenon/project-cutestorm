document.addEventListener('DOMContentLoaded', function () {
    const texts = document.querySelectorAll('#keep-in-touch p');
    let index = 0;
    let forward = true;

    function animateText() {
        if (forward) {
            texts[index].classList.remove('opacity-0');
            texts[index].classList.add('opacity-100');
            index++;
            if (index >= texts.length) {
            forward = false;
            index = texts.length - 1;
            }
        } else {
            texts[index].classList.remove('opacity-100');
            texts[index].classList.add('opacity-0');
            index--;
            if (index < 0) {
            forward = true;
            index = 0;
            }
        }
    }

    setInterval(animateText, 100);
});