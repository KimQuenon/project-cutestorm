// go to previous/next step in a multi-step form
function nextStep(step) {
    document.querySelectorAll('.step').forEach(function (stepDiv) {
        stepDiv.classList.remove('active');
    });
    document.getElementById('step' + step).classList.add('active');
}

function previousStep(step) {
    document.querySelectorAll('.step').forEach(function (stepDiv) {
        stepDiv.classList.remove('active');
    });
    document.getElementById('step' + step).classList.add('active');
}

window.nextStep = nextStep;
window.previousStep = previousStep;
