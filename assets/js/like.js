document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.like-post');

    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (button.disabled) {
                return;
            }

            const postSlug = button.getAttribute('data-post-slug');
            const likeCountElement = button.nextElementSibling;
            
            //save likes to database
            fetch(`/posts/${postSlug}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            })
            .then(response => {
                if (response.status === 403) {
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (data) {
                    //update like count
                    likeCountElement.textContent = data.likeCount;

                    //toggle colors
                    if (data.liked) {
                        button.classList.add('liked');
                        button.classList.remove('not-liked');
                        likeCountElement.classList.add('liked');
                        likeCountElement.classList.remove('not-liked');
                    } else {
                        button.classList.add('not-liked');
                        button.classList.remove('liked');
                        likeCountElement.classList.add('not-liked');
                        likeCountElement.classList.remove('liked');
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
