const nav = document.getElementById('nav-link');
const megaMenu = document.getElementById('mega-menu');
const burgerMenu = document.getElementById('burger-menu');
const burgerMenuContainer = document.getElementById('burger-menu-container');
const bar1 = document.getElementById('burger-bar-1');
const bar2 = document.getElementById('burger-bar-2');
const bar3 = document.getElementById('burger-bar-3');


//display mega menu on while hovering the nav
nav.addEventListener('mouseover', () => {
  megaMenu.classList.remove('hidden');
});

//hide the mega menu
megaMenu.addEventListener('mouseout', () => {
  megaMenu.classList.add('hidden');
});

burgerMenu.addEventListener('click', () => {
    // toggle to display/hide menu burger
    if (burgerMenuContainer.classList.contains('hidden')) {
        burgerMenuContainer.classList.remove('hidden');
        burgerMenuContainer.classList.add('slide-in');
        document.body.classList.add('overflow-hidden');
    } else {
        burgerMenuContainer.classList.remove('slide-in');
        burgerMenuContainer.classList.add('slide-out');
        document.body.classList.remove('overflow-hidden');

        // reset classes
        burgerMenuContainer.addEventListener('animationend', () => {
            if (burgerMenuContainer.classList.contains('slide-out')) {
                burgerMenuContainer.classList.add('hidden');
                burgerMenuContainer.classList.remove('slide-out');
            }
        }, { once: true });
    }

    // Transformer les barres en croix
    bar1.classList.toggle('rotate-45');
    bar1.classList.toggle('translate-y-2');

    bar2.classList.toggle('opacity-0');

    bar3.classList.toggle('-rotate-45');
    bar3.classList.toggle('-translate-y-2');
});

// Fermer le menu burger en cliquant en dehors
document.addEventListener('click', (e) => {
    if (!e.target.closest('#burger-menu-container') && !e.target.closest('#burger-menu')) {
        burgerMenuContainer.classList.remove('slide-in');
        burgerMenuContainer.classList.add('slide-out');
        document.body.classList.remove('overflow-hidden');

        // Réinitialiser les classes après l'animation
        burgerMenuContainer.addEventListener('animationend', () => {
            if (burgerMenuContainer.classList.contains('slide-out')) {
                burgerMenuContainer.classList.add('hidden');
                burgerMenuContainer.classList.remove('slide-out');
            }
        }, { once: true });

        // Réinitialiser les barres du menu burger
        bar1.classList.remove('rotate-45', 'translate-y-2');
        bar2.classList.remove('opacity-0');
        bar3.classList.remove('-rotate-45', '-translate-y-2');
    }
});

document.querySelector('button').addEventListener('click', function() {
    const dropdown = document.querySelector('[role="menu"]');
    dropdown.classList.toggle('hidden');
});