<?php
/*
Plugin Name: AI Content Optimizer
Description: Provides AI-driven content optimization suggestions for SEO, readability, and engagement.
Version: 1.0.0
Author: <a href="https://sergiocomella.it">Panezio</a>
License: GPLv2 or later
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly
require_once plugin_dir_path(__FILE__) . 'includes/Parsedown.php';

function ai_optimizer_enqueue_assets()
{
    wp_enqueue_style('ai-optimizer-style', plugin_dir_url(__FILE__) . 'assets/css/ai-content-optimizer-style.css', array(), filemtime(plugin_dir_path(__FILE__) . 'assets/css/ai-content-optimizer-style.css'));

    wp_enqueue_script('ai-optimizer-script', plugin_dir_url(__FILE__) . 'assets/js/ai-content-optimizer.js', array('jquery'), '1.0.0', true);

    // Generate and pass nonce to JavaScript
    wp_localize_script('ai-optimizer-script', 'aiOptimizerData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ai_optimizer_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'ai_optimizer_enqueue_assets');


// Include AI Analysis Functions
require_once plugin_dir_path(__FILE__) . 'includes/ai-content-analyzer.php';

function ai_optimize_content()
{
    // Check if the nonce is valid
    $nonce = isset($_POST['_ajax_nonce']) ? sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ai_optimizer_nonce')) {
        wp_die('Unauthorized request.');
    }

    // Retrieve and sanitize content
    $content = isset($_POST['content']) ? sanitize_text_field(wp_unslash($_POST['content'])) : '';

    if (empty($content)) {
        esc_html_e('Content is required.', 'ai-content-optimizer');
        wp_die();
    }

    // Process the sanitized content
    $suggestions = ai_content_optimizer_analyze($content);

    // Output the suggestions safely
    echo wp_kses_post($suggestions);
    wp_die(); // Properly end AJAX response
}
add_action('wp_ajax_ai_optimize_content', 'ai_optimize_content');


// Add meta box to the post editor
function ai_optimizer_add_meta_box()
{
    add_meta_box('ai_optimizer_meta', 'AI Content Suggestions', 'ai_optimizer_meta_box_callback', 'post', 'side', 'high');
}
add_action('add_meta_boxes', 'ai_optimizer_add_meta_box');

function ai_optimizer_meta_box_callback($post)
{
    echo '<div id="ai-content-suggestions" style="margin-bottom: 10px; font-size: 14px; color: #555;">';
    echo '<p>Loading suggestions...</p>';
    echo '<ul id="suggestions-list" style="list-style-type: none; padding: 0;"></ul>'; // Placeholder for suggestions
    echo '</div>';

    echo '<button type="button" id="analyze-content" class="button button-primary" style="background-color: #0073aa; color: #fff; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Analyze Content</button>';
    echo '<div id="loading-spinner" style="display: none; margin-top: 10px;"><img src="' . esc_attr(plugin_dir_url(__FILE__)) . 'assets/images/loading-spinner.gif" alt="Loading..." style="width: 20px; height: 20px; vertical-align: middle;" /> Analyzing...</div>';
}

// Register settings page
function ai_optimizer_register_settings_page()
{
    add_options_page(
        'AI Content Optimizer Settings',
        'AI Content Optimizer',
        'manage_options',
        'ai-content-optimizer',
        'ai_optimizer_settings_page'
    );
}
add_action('admin_menu', 'ai_optimizer_register_settings_page');

// Settings page content
function ai_optimizer_settings_page()
{
?>
    <div class="wrap">
        <h1>AI Content Optimizer Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ai_optimizer_settings');
            do_settings_sections('ai-content-optimizer');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// Register settings fields
function ai_optimizer_register_settings()
{
    register_setting('ai_optimizer_settings', 'ai_optimizer_api_key');
    register_setting('ai_optimizer_settings', 'ai_optimizer_endpoint');
    register_setting('ai_optimizer_settings', 'ai_optimizer_model');
    register_setting('ai_optimizer_settings', 'ai_optimizer_temperature');
    register_setting('ai_optimizer_settings', 'ai_optimizer_max_tokens');
    register_setting('ai_optimizer_settings', 'ai_optimizer_system_message');
    register_setting('ai_optimizer_settings', 'ai_optimizer_user_message');

    add_settings_field('ai_optimizer_system_message', 'System Message', 'ai_optimizer_system_message_callback', 'ai-content-optimizer', 'ai_optimizer_section');
    add_settings_field('ai_optimizer_user_message', 'User Message', 'ai_optimizer_user_message_callback', 'ai-content-optimizer', 'ai_optimizer_section');

    add_settings_section('ai_optimizer_section', 'API Settings', null, 'ai-content-optimizer');

    add_settings_field('ai_optimizer_api_key', 'API Key', 'ai_optimizer_api_key_callback', 'ai-content-optimizer', 'ai_optimizer_section');
    add_settings_field('ai_optimizer_endpoint', 'API Endpoint', 'ai_optimizer_endpoint_callback', 'ai-content-optimizer', 'ai_optimizer_section');
    add_settings_field('ai_optimizer_model', 'Model', 'ai_optimizer_model_callback', 'ai-content-optimizer', 'ai_optimizer_section');
    add_settings_field('ai_optimizer_temperature', 'Temperature', 'ai_optimizer_temperature_callback', 'ai-content-optimizer', 'ai_optimizer_section');
    add_settings_field('ai_optimizer_max_tokens', 'Max Tokens', 'ai_optimizer_max_tokens_callback', 'ai-content-optimizer', 'ai_optimizer_section');
}
add_action('admin_init', 'ai_optimizer_register_settings');

// Callback functions for each field
function ai_optimizer_api_key_callback()
{
    $api_key = get_option('ai_optimizer_api_key', '');
    echo "<input type='text' name='ai_optimizer_api_key' value='" . esc_attr($api_key) . "' />";
}

function ai_optimizer_endpoint_callback()
{
    $endpoint = get_option('ai_optimizer_endpoint', 'https://api.openai.com/v1/completions');
    echo "<input type='text' name='ai_optimizer_endpoint' value='" . esc_attr($endpoint) . "' />";
}

function ai_optimizer_model_callback()
{
    $model = get_option('ai_optimizer_model', 'gpt-3.5-turbo');
    echo "<input type='text' name='ai_optimizer_model' value='" . esc_attr($model) . "' />";
}

function ai_optimizer_temperature_callback()
{
    $temperature = get_option('ai_optimizer_temperature', '0.7');
    echo "<input type='number' step='0.1' name='ai_optimizer_temperature' value='" . esc_attr($temperature) . "' />";
}

function ai_optimizer_max_tokens_callback()
{
    $max_tokens = get_option('ai_optimizer_max_tokens', '500');
    echo "<input type='number' name='ai_optimizer_max_tokens' value='" . esc_attr($max_tokens) . "' />";
}

function ai_optimizer_system_message_callback()
{
    $system_message = get_option('ai_optimizer_system_message', 'You are an AI assistant that provides SEO, readability, keyword usage, and engagement improvements.');
    echo "<textarea name='ai_optimizer_system_message' rows='4' cols='50'>" . esc_textarea($system_message) . "</textarea>";
}

function ai_optimizer_user_message_callback()
{
    $user_message = get_option('ai_optimizer_user_message', 'Analyze the following content and suggest improvements for SEO, readability, keyword usage, and engagement:');
    echo "<textarea name='ai_optimizer_user_message' rows='4' cols='50'>" . esc_textarea($user_message) . "</textarea>";
}
