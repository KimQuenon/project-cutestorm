document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-replies').forEach(function(button) {
        button.addEventListener('click', function() {
            var commentId = this.getAttribute('data-comment-id');
            var replies = document.getElementById('replies-' + commentId);
            var repliesCount = replies.querySelectorAll('.list-group-item').length;

            if (replies.classList.contains('show')) {
                replies.classList.remove('show');
                this.textContent = repliesCount === 1 ? 'View reply' : 'View ' + repliesCount + ' replies';
            } else {
                replies.classList.add('show');
                this.textContent = repliesCount === 1 ? 'Hide reply' : 'Hide replies';
            }
        });
    });
});