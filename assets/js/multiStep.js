

let currentStep = 1;

function updateProgressBar() {
    const progressBar = document.getElementById('progressBar');
    const circles = progressBar.querySelectorAll('div.w-8');
    const lines = progressBar.querySelectorAll('div.h-1');

    circles.forEach((circle, index) => {
        // reset classes
        circle.classList.remove('bg-background', 'bg-secondary', 'bg-primary');
        
        if (index < currentStep - 1) {
            circle.classList.add('bg-primary'); // cleared step
        } else if (index === currentStep - 1) {
            circle.classList.add('bg-secondary'); // current step
        } else {
            circle.classList.add('bg-background'); // awaiting step
        }
    });

    lines.forEach((line, index) => {
        line.classList.remove('bg-background', 'bg-primary');
        line.classList.add(index < currentStep - 1 ? 'bg-primary' : 'bg-background');
    });
}

updateProgressBar();

function changeStep(step) {
    document.querySelectorAll('.step').forEach((stepDiv) => {
        stepDiv.classList.remove('active');
    });
    document.getElementById('step' + step).classList.add('active');
    currentStep = step; // current step
    updateProgressBar(); // progression bar updated
}

function nextStep(step) {
    changeStep(step);
}

function previousStep(step) {
    changeStep(step);
}

window.nextStep = nextStep;
window.previousStep = previousStep;
