window.addEventListener('load', () => {
    const animatedDiv = document.querySelector('.animatedDiv');
    const stillDiv = document.querySelector('.stillDiv');
    const textDiv = document.querySelector('.textDiv');

    setTimeout(() => {
        animatedDiv.classList.remove('w-full');
        animatedDiv.classList.add('lg:w-1/2', 'w-0');
        stillDiv.classList.remove('w-0');
        stillDiv.classList.add('lg:w-1/2', 'w-full');
    }, 75);

    setTimeout(() => {
        textDiv.classList.add('opacity-100');
    }, 800);
});