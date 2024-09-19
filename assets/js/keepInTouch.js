document.addEventListener('DOMContentLoaded', function () {
    const texts = document.querySelectorAll('#keep-in-touch p');
    let index = 0;

    function animateText() {
        // Afficher le texte actuel
        texts[index].classList.remove('opacity-0');
        texts[index].classList.add('opacity-100');

        // Masquer le texte d'il y a deux cycles
        let previousIndex = index - 2;
        if (previousIndex < 0) {
            previousIndex += texts.length;
        }
        texts[previousIndex].classList.remove('opacity-100');
        texts[previousIndex].classList.add('opacity-0');

        // Passer au texte suivant
        index++;
        if (index >= texts.length) {
            index = 0; // Réinitialiser à la première élément
        }
    }

    setInterval(animateText, 150);
});
