jQuery(document).ready(function($) {
    var post_id = $('#article-voting').data('post-id');

    // Function to update UI with vote results
    function updateVoteUI(user_vote, yes_percentage, no_percentage) {
        $('#article-voting .question').html('THANK YOU FOR YOUR FEEDBACK.');
        if (user_vote) {
            $('#article-voting button').removeClass('active'); // Remove active class from all buttons
            $('#article-voting button[data-vote="' + user_vote + '"]').addClass('active'); // Highlight the user's voted button
        }
        $('#article-voting button[data-vote="yes"] span').html(yes_percentage + '%');
        $('#article-voting button[data-vote="no"] span').html(no_percentage + '%');
    }

    // Check if user has already voted and update UI accordingly
    $.ajax({
        type: 'POST',
        url: articleVoting.ajax_url,
        data: {
            action: 'fetch_vote_results',
            post_id: post_id,
            security: articleVoting.nonce
        },
        success: function(response) {
            if(response.data.user_vote) {
                updateVoteUI(response.data.user_vote, response.data.yes_percentage, response.data.no_percentage);
            }
        }
    });

    $('#article-voting button').click(function() {
        var vote = $(this).data('vote');
        
        $.ajax({
            type: 'POST',
            url: articleVoting.ajax_url,
            data: {
                action: 'article_vote',
                post_id: post_id,
                vote: vote,
                security: articleVoting.nonce
            },
            success: function(response) {
                updateVoteUI(vote, response.data.yes_percentage, response.data.no_percentage);
            }
        });
    });
});
