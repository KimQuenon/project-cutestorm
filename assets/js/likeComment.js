// public/js/likeComment.js
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.like-button');

    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (button.disabled) {
                return; // Do nothing if the button is disabled
            }

            const commentId = button.getAttribute('data-comment-id');
            const likeCountElement = button.nextElementSibling; // Assuming the like count span is immediately after the button

            fetch(`/comments/${commentId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include' // Include cookies for authenticated requests
            })
            .then(response => {
                if (response.status === 403) {
                    // No alert here, just silently ignore the request
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (data) {
                    likeCountElement.textContent = data.likeCount;

                    if (data.liked) {
                        button.classList.add('liked'); // Add 'liked' class for styling
                        button.classList.remove('not-liked'); // Remove 'not-liked' class
                    } else {
                        button.classList.add('not-liked'); // Add 'not-liked' class for styling
                        button.classList.remove('liked'); // Remove 'liked' class
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
