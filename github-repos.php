<?php
/**
 * Plugin Name: GitHub Repos
 * Description: Displays your public GitHub repositories as cards using the GitHub API.
 * Version: 1.0.0
 * Author: supertiluca.it
 * Author URI: https://supertiluca.it
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GHR_VERSION', '1.0.0' );

// ─── Settings page ─────────────────────────────────────────────────────────
add_action( 'admin_menu', function() {
    add_options_page(
        'GitHub Repos',
        'GitHub Repos',
        'manage_options',
        'github-repos-settings',
        'ghr_settings_page'
    );
});

function ghr_settings_page() {
    if ( isset($_POST['ghr_save']) && check_admin_referer('ghr_settings') ) {
        update_option( 'ghr_username', sanitize_text_field($_POST['ghr_username']) );
        update_option( 'ghr_token',    sanitize_text_field($_POST['ghr_token']) );
        echo '<div class="notice notice-success"><p>Impostazioni salvate.</p></div>';
    }

    $username = esc_attr( get_option('ghr_username', '') );
    ?>
    <div class="wrap">
      <h1>GitHub Repos — Impostazioni</h1>
      <form method="post">
        <?php wp_nonce_field('ghr_settings'); ?>
        <table class="form-table">
          <tr>
            <th>GitHub Username</th>
            <td><input type="text" name="ghr_username" value="<?= $username ?>" class="regular-text" placeholder="es. supertiluca"></td>
          </tr>
          <tr>
            <th>Personal Access Token <span style="font-weight:normal">(opzionale)</span></th>
            <td>
              <input type="password" name="ghr_token" value="" class="regular-text" placeholder="lascia vuoto per non modificare">
              <p class="description">Necessario solo se vuoi aumentare il rate limit delle API GitHub (60 → 5000 req/ora). Crea un token su <a href="https://github.com/settings/tokens" target="_blank">github.com/settings/tokens</a> con scope <code>public_repo</code>.</p>
            </td>
          </tr>
        </table>
        <?php submit_button('Salva', 'primary', 'ghr_save'); ?>
      </form>
    </div>
    <?php
}

// ─── AJAX: fetch repos ─────────────────────────────────────────────────────
add_action( 'wp_ajax_ghr_get_repos',        'ghr_ajax_get_repos' );
add_action( 'wp_ajax_nopriv_ghr_get_repos', 'ghr_ajax_get_repos' );
function ghr_ajax_get_repos() {
    check_ajax_referer( 'ghr_nonce', 'nonce' );

    $username = get_option('ghr_username', '');
    $token    = get_option('ghr_token', '');

    if ( empty($username) ) {
        wp_send_json_error('GitHub username non configurato. Vai su Impostazioni → GitHub Repos.');
        return;
    }

    $cache_key = 'ghr_repos_' . md5($username);
    $cached    = get_transient($cache_key);
    if ( $cached !== false ) {
        wp_send_json_success($cached);
        return;
    }

    $headers = [
        'User-Agent' => 'WordPress/GitHub-Repos-Plugin',
        'Accept'     => 'application/vnd.github+json',
    ];
    if ( $token ) {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    $response = wp_remote_get(
        'https://api.github.com/users/' . urlencode($username) . '/repos?per_page=100&sort=updated',
        [ 'headers' => $headers, 'timeout' => 15 ]
    );

    if ( is_wp_error($response) ) {
        wp_send_json_error($response->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode( wp_remote_retrieve_body($response), true );

    if ( $code !== 200 ) {
        wp_send_json_error('GitHub API error: HTTP ' . $code);
        return;
    }

    // Filtra solo i campi necessari
    $repos = array_map(function($r) {
        return [
            'name'        => $r['name']        ?? '',
            'description' => $r['description'] ?? '',
            'html_url'    => $r['html_url']     ?? '',
            'fork'        => $r['fork']         ?? false,
        ];
    }, $body);

    // Escludi fork
    $repos = array_values( array_filter($repos, fn($r) => !$r['fork']) );

    // Cache 10 minuti
    set_transient($cache_key, $repos, 10 * MINUTE_IN_SECONDS);

    wp_send_json_success($repos);
}

// ─── Shortcode ─────────────────────────────────────────────────────────────
add_shortcode( 'github_repos', 'ghr_shortcode' );
function ghr_shortcode() {
    ob_start();
    wp_enqueue_script( 'ghr-js',  plugin_dir_url(__FILE__) . 'github-repos.js',  [], GHR_VERSION, true );
    wp_enqueue_style(  'ghr-css', plugin_dir_url(__FILE__) . 'github-repos.css', [], GHR_VERSION );
    wp_localize_script( 'ghr-js', 'ghrConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ghr_nonce'),
    ]);
    echo '<div id="ghr-root"></div>';
    return ob_get_clean();
}
