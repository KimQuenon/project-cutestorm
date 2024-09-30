//add each element x seconds after the slide is visible
function observeAllSlides() {
    const slides = document.querySelectorAll('.slide');

    slides.forEach((slide) => {

        const cards = slide.querySelectorAll('.card');

        if (cards.length > 0) { 
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    let delay = 0;
                    cards.forEach((card) => {
                        setTimeout(() => {
                            card.classList.add('card-visible');
                        }, delay);
                        delay += 300;
                    });
                }
            }, { threshold: 0.5 });

            observer.observe(slide);
        }
    });
}

observeAllSlides();
