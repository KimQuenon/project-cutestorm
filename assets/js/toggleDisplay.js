const arrow = document.querySelector('.fa-chevron-down');
const display = document.querySelector('.display');
const radius = document.querySelector('.radius');

arrow.addEventListener('click', () => {
    console.log("click")
    display.classList.toggle('hidden');
    radius.classList.toggle('rounded-b-none');
    arrow.classList.toggle('-rotate-180')
});