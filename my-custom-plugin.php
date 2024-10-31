<?php
/*
    Plugin Name: My Custom Plugin
    Version: 0.0.0
    GitHub URI: https://github.com/valchevio/my-custom-plugin
*/


function check_for_github_update()
{
    $plugin_data = get_plugin_data(__FILE__); // For themes, use wp_get_theme()
    $current_version = $plugin_data['Version'];
    $github_repo_url = 'https://api.github.com/repos/valchevio/my-custom-plugin/releases/latest';

    // Fetch the latest release info from GitHub
    $response = wp_remote_get($github_repo_url);

    if (is_wp_error($response)) {
        return; // Exit if there’s an error
    }

    $release_info = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($release_info['tag_name'])) {
        $latest_version = ltrim($release_info['tag_name'], 'v'); // Remove 'v' if used as prefix

        // Store the latest version info in a transient for an hour
        set_transient('my_plugin_latest_version', $latest_version, HOUR_IN_SECONDS);
        set_transient('my_plugin_latest_zip', $release_info['zipball_url'], HOUR_IN_SECONDS);

        if (version_compare($current_version, $latest_version, '<')) {
            // Trigger update notification if there's a newer version
            add_action('admin_notices', function () use ($latest_version) {
                echo '<div class="notice notice-info"><p>';
                echo 'A new version (' . esc_html($latest_version) . ') of the plugin is available. ';
                echo '<a href="' . esc_url(admin_url('update.php')) . '">Update now</a>';
                echo '</p></div>';
            });
        }
    }
}
add_action('admin_init', 'check_for_github_update');



function modify_update_package($transient) //($update, $plugin_data)
{
    // Ensure the transient update process is in place
    if (!isset($transient->response['my-custom-plugin/my-custom-plugin.php'])) {
        return $transient;
    }

    // Get the stored version and zip URL from transients
    $latest_version = get_transient('my_plugin_latest_version');
    $latest_zip_url = get_transient('my_plugin_latest_zip');

    if ($latest_version && $latest_zip_url) {
        $transient->response['my-custom-plugin/my-custom-plugin.php'] = (object) [
            'slug'        => 'my-custom-plugin',
            'new_version' => $latest_version,
            'package'     => $latest_zip_url,
            'url'         => 'https://github.com/your-username/your-repository',
        ];
    }

    return $transient;


    // if ($plugin_data['GitHub URI'] === 'https://github.com/valchevio/my-custom-plugin') {
    //     $latest_zip_url = 'https://github.com/valchevio/my-custom-plugin/archive/refs/tags/' . $release_info['tag_name'] . '.zip';

    //     $update->package = $latest_zip_url; // Set the update package to the latest GitHub zip
    // }
    // return $update;
}
// add_filter('pre_set_site_transient_update_plugins', 'modify_update_package', 10, 2);
