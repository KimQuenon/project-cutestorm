const flipCards = document.querySelectorAll('.flip-card');

flipCards.forEach((link) => {
    link.addEventListener('click', () => {
        link.classList.toggle('active');
    });
})