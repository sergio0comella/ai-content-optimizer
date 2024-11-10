<?php

function ai_content_optimizer_analyze($content)
{
    // Retrieve settings from options
    $api_key = get_option('ai_optimizer_api_key');
    $endpoint = get_option('ai_optimizer_endpoint', 'https://api.openai.com/v1/completions');
    $model = get_option('ai_optimizer_model', 'gpt-3.5-turbo');
    $temperature = floatval(get_option('ai_optimizer_temperature', '0.7'));
    $max_tokens = intval(get_option('ai_optimizer_max_tokens', '500'));

    // Retrieve messages from options
    $system_message = get_option('ai_optimizer_system_message', 'You are an AI assistant that provides SEO, readability, keyword usage, and engagement improvements.');
    $user_message = get_option('ai_optimizer_user_message', 'Analyze the following content and suggest improvements for SEO, readability, keyword usage, and engagement:');

    // Data array using retrieved settings
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $system_message
            ],
            [
                'role' => 'user',
                'content' => "$user_message\n\n$content"
            ]
        ],
        'temperature' => $temperature,
        'max_tokens' => $max_tokens
    ];

    // Send the request to the API
    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($data)
    ]);

    if (is_wp_error($response)) {
        return 'Error connecting to AI service.';
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $markdown_text = isset($body['choices'][0]['message']['content']) ? $body['choices'][0]['message']['content'] : 'No suggestions available.';

    // Parse Markdown to HTML
    $Parsedown = new Parsedown();
    $html_output = $Parsedown->text($markdown_text);

    return $html_output;
}