<?php
/**
 * Plugin Name: Article Voting
 * Description: Allows users to vote on articles as helpful or not.
 * Version: 1.0
 * Author: Jon Daveyan
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Main plugin class
class ArticleVoting {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_article_vote', array($this, 'handle_vote'));
        add_action('wp_ajax_nopriv_article_vote', array($this, 'handle_vote'));
        add_filter('the_content', array($this, 'append_voting_buttons'));
        add_action('wp_ajax_fetch_vote_results', array($this, 'fetch_vote_results'));
        add_action('wp_ajax_nopriv_fetch_vote_results', array($this, 'fetch_vote_results'));
    }

    public function enqueue_scripts() {
        // Enqueue JavaScript for AJAX
        wp_enqueue_script('article-voting-script', plugins_url('/js/vote.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('article-voting-script', 'articleVoting', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('article-voting-nonce'),
        ));

        // Enqueue style.css
        wp_enqueue_style('article-voting-style', plugins_url('/css/style.css', __FILE__));
    }

    // Handle voting AJAX request
    public function handle_vote() {
        check_ajax_referer('article-voting-nonce', 'security');
    
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $vote = isset($_POST['vote']) ? sanitize_text_field($_POST['vote']) : '';
        $user_ip = $_SERVER['REMOTE_ADDR'];
    
        // Retrieve existing votes
        $votes = get_post_meta($post_id, '_votes_with_ip', true) ?: array();
    
        // Check if this IP has already voted
        if (isset($votes[$user_ip])) {
            wp_send_json_error('You have already voted!');
            return;
        }
    
        // Record new vote
        $votes[$user_ip] = $vote;
        update_post_meta($post_id, '_votes_with_ip', $votes);
    
        // Update vote counts
        $vote_counts = get_post_meta($post_id, '_vote_counts', true) ?: array('yes' => 0, 'no' => 0);
        if (in_array($vote, array('yes', 'no'))) {
            $vote_counts[$vote]++;
            update_post_meta($post_id, '_vote_counts', $vote_counts);
    
            // Calculate percentages
            $total_votes = $vote_counts['yes'] + $vote_counts['no'];
            $yes_percentage = $total_votes > 0 ? round(($vote_counts['yes'] / $total_votes) * 100) : 0;
            $no_percentage = 100 - $yes_percentage;
    
            wp_send_json_success(array('message' => 'Vote recorded', 'yes_percentage' => $yes_percentage, 'no_percentage' => $no_percentage));
        } else {
            wp_send_json_error('Invalid vote');
        }
    }
    

    public function fetch_vote_results() {
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $votes = get_post_meta($post_id, '_votes_with_ip', true) ?: array();

        // Calculate percentages
        $vote_counts = array_count_values($votes); // Count occurrences of 'yes' and 'no'
        $total_votes = count($votes);
        $yes_votes = isset($vote_counts['yes']) ? $vote_counts['yes'] : 0;
        $yes_percentage = $total_votes > 0 ? round(($yes_votes / $total_votes) * 100) : 0;
        $no_percentage = 100 - $yes_percentage;

        // Determine if and what the user voted
        $user_vote = isset($votes[$user_ip]) ? $votes[$user_ip] : null;

        wp_send_json_success(array('yes_percentage' => $yes_percentage, 'no_percentage' => $no_percentage, 'user_vote' => $user_vote));
    }


    // Append voting buttons to content
    public function append_voting_buttons($content) {
        if (is_single() && in_the_loop() && is_main_query()) {
            global $post;
            $content .= '<div id="article-voting" data-post-id="' . get_the_ID() . '">';
            $content .= '<div class="question">what this article helpful?</div>';
            $content .= '<div class="buttons">';
            $content .= '<button data-vote="yes">';
            $content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="#bbbbbb" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM164.1 325.5C182 346.2 212.6 368 256 368s74-21.8 91.9-42.5c5.8-6.7 15.9-7.4 22.6-1.6s7.4 15.9 1.6 22.6C349.8 372.1 311.1 400 256 400s-93.8-27.9-116.1-53.5c-5.8-6.7-5.1-16.8 1.6-22.6s16.8-5.1 22.6 1.6zM144.4 208a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm192-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>';
            $content .= '<span>Yes</span>';
            $content .= '</button>';
            $content .= '<button data-vote="no">';
            $content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="#bbbbbb" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM176.4 176a32 32 0 1 1 0 64 32 32 0 1 1 0-64zm128 32a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM160 336H352c8.8 0 16 7.2 16 16s-7.2 16-16 16H160c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/></svg>';
            $content .= '<span>No</span>';
            $content .= '</button>';
            $content .= '</div>';
            $content .= '</div>';
        }
        return $content;
    }
}

new ArticleVoting();
