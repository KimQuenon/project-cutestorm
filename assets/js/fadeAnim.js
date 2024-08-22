const slide = document.getElementById('fade-anim').querySelector('.container');
const cards = document.querySelectorAll('.card');

const observer = new IntersectionObserver((entries) => {
if (entries[0].isIntersecting) {
    let delay = 0;
    cards.forEach((card, index) => {
    setTimeout(() => {
        card.classList.add('card-visible');
    }, delay);
    delay += 300;
    });
}
}, { threshold: 1 });

observer.observe(slide);