document.addEventListener('DOMContentLoaded', function() {
    const replyButtons = document.querySelectorAll('.reply-button');

    replyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const replyFormContainer = document.getElementById('reply-form-' + commentId);
            
            if (replyFormContainer.classList.contains('show')) {
                replyFormContainer.classList.remove('show');
            } else {
                replyFormContainer.classList.add('show');
            }
        });
    });
});