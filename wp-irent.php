<?php 
/**
 * PHP version 7.4
 *
 * Plugin wp-irent is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * Plugin Name: i-rent.net
 * Description: Property management system for holiday homes (PMS)
 * Author: i-rent.net
 * Author URI:  https://i-rent.net
 * Version:     1.0.0
 * Text Domain: single-page-booking-system
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @category   Plugins
 * @package    Wordpress_I-rent.net
 * @subpackage I-rent.net/WordpressPMS
 * @author     Irent <soporte@i-rent.net>
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL-2.0-or-later
 * @link       https://i-rent.net
 */

// Prevent direct execution
if (!defined('ABSPATH')) {
    exit;
}

/**
 * This function grants access to the plugin menu in the admin panel.
 *
 * @return void
 */
function Irent_Grant_Access()
{
    if (is_admin()) {
        $config_file = plugin_dir_path(__FILE__) . 'wp-irent.php';
        require_once($config_file);
    
        if (current_user_can('edit_pages')) {
            add_action('admin_menu', 'Irent_Plugin_menu');
        }
    }
}
add_action('admin_init', 'Irent_Grant_Access');

/**
 * Function to be executed when the plugin is activated
 *
 * @return void
 */
function Irent_Plugin_activate()
{
    // Register the option in the database when activating the plugin
    add_option('irent_plugin_options', '');
}
register_activation_hook(__FILE__, 'Irent_Plugin_activate');

/**
 * Function to be executed when the plugin is deactivated
 *
 * @return void
 */
function Irent_Plugin_deactivate()
{
    // Remove database option when deactivating the plugin
    delete_option('irent_plugin_options');
}
register_deactivation_hook(__FILE__, 'Irent_Plugin_deactivate');

/**
 * Function that adds the options page in the administration panel
 *
 * @return void
 */
function Irent_Plugin_menu()
{
    $title = sanitize_text_field('i-rent.net'); // Page and menu title
    $capability = sanitize_text_field('manage_options'); // Capacity required for access
    $menu_slug = sanitize_key('irent-plugin-options'); // Slug page
    $function = 'Irent_Plugin_Options_Page'; // Callback function to render the page

    add_menu_page($title, $title, $capability, $menu_slug, $function);

    // Define file paths
    $file_paths = array(
        'irent-styles' => 'admin/css/wp-irent.css',
        'irent-script' => 'admin/js/wp-irent.js',
    );

    // Enqueue styles and scripts
    array_walk($file_paths, function ($file_path, $handle) {
        $full_path = plugin_dir_path(__FILE__) . $file_path;
        $ext = pathinfo($full_path, PATHINFO_EXTENSION);
        if (file_exists($full_path)) {
            if ($ext === 'css') {
                wp_enqueue_style($handle, plugins_url($file_path, __FILE__), array(), null);
            } elseif ($ext === 'js') {
                wp_enqueue_script($handle, plugins_url($file_path, __FILE__), array(), null, true);
            }
        }
    });
}
// Register the menu
add_action('admin_menu', 'Irent_Plugin_menu');

/**
 * This function retrieves data from the database.
 *
 * @return array The data retrieved from the database.
 */
function Irent_Get_Data_DB()
{
    global $wpdb;

    // Define the table name
    $table_name = $wpdb->prefix . 'irent_configuration';

    // Prepare the SQL statement to check if the table exists
    $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);

    // Execute the query
    $result = $wpdb->get_var($sql);

    // Check if the table exists
    if ($result != $table_name) {
        // Prepare the SQL statement to create or update the table
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id INT,
            username VARCHAR(255),
            password VARCHAR(255),
            login_status BOOLEAN,
            pageId INT,
            language VARCHAR(10),
            theme VARCHAR(255),
            code VARCHAR(255),
            map_key VARCHAR(255),
            custom_code TEXT,
            short_code BOOLEAN
        )";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Prepare the SQL statement to insert or update data in the table
        $sql = $wpdb->prepare(
            "INSERT INTO {$table_name} (id, username, password, login_status, pageId, language, theme, code, map_key, custom_code, short_code)
            VALUES (%d, %s, %s, %d, %d, %s, %s, %s, %s, %s, %d)
            ON DUPLICATE KEY UPDATE
            id = VALUES(id),
            username = VALUES(username),
            password = VALUES(password),
            login_status = VALUES(login_status),
            pageId = VALUES(pageId),
            language = VALUES(language),
            theme = VALUES(theme),
            code = VALUES(code),
            map_key = VALUES(map_key),
            custom_code = VALUES(custom_code),
            short_code = VALUES(short_code)",
            1,
            '',
            '',
            false,
            0,
            'en',
            'beige',
            0,
            '',
            '',
            false
        );
        $wpdb->query($sql);
    }

    // Prepare the SQL statement to select all data from the table
    $sql = "SELECT * FROM {$table_name}";

    // Get the results and return them
    $response = $wpdb->get_results($sql, ARRAY_A);
    return $response;
}

/**
 * This function logs out the user by updating the database.
 *
 * @return void
 */
function Irent_LogOut()
{
    Irent_Update_DB(
        array(
            'login_status'  => 0,
            'password'      => ''
        )
    );
}

/**
 * This function checks if the user is logged in.
 *
 * @return bool True if the user is logged in, false otherwise.
 */
function Irent_Get_Logged()
{
    $configuration_table = Irent_Get_Data_DB();
    $logged = isset($configuration_table[0]['login_status']) ? filter_var($configuration_table[0]['login_status'], FILTER_VALIDATE_BOOLEAN) : false;

    return $logged;
}

/**
 * This function updates the database with the provided key-value pairs.
 *
 * @param array $arrayKeys The key-value pairs to update in the database.
 * @return void
 */
function Irent_Update_DB($arrayKeys)
{
    global $wpdb;

    // Define the table name
    $table_name = $wpdb->prefix . 'irent_configuration';

    if (!is_array($arrayKeys)) {
        return;
    }

    // If the function is called with arguments, update each field in the table with its corresponding value
    foreach ($arrayKeys as $key => $value) {
        // Determine the placeholder based on the type of the value
        $placeholder = is_int($value) ? '%d' : '%s';

        // Prepare the SQL statement
        $sql = $wpdb->prepare(
            "UPDATE {$table_name} SET {$key} = {$placeholder} WHERE id = %d",
            $value,
            1
        );

        // Execute the query
        $wpdb->query($sql);
    }
}

/**
 * This function restores the database to its default state.
 *
 * @return void
 */
function Irent_Restore_DB()
{
    global $wpdb;

    // Define the table name
    $table_name = $wpdb->prefix . 'irent_configuration';

    $sql = $wpdb->prepare(
        "UPDATE $table_name SET 
        username = %s,
        password = %s,
        login_status = %d,
        pageId = %d,
        language = %s,
        theme = %s,
        code = %s,
        map_key = %s,
        custom_code = %s,
        short_code = %d
        WHERE id = %d",
        '',
        '',
        false,
        0,
        'en',
        'beige',
        0,
        '',
        '',
        false,
        1
    );
    $wpdb->query($sql);
}

/**
 * This function clears all shortcodes from all pages.
 *
 * @return void
 */
function Irent_Clear_Shortcode()
{
    $page_ids = get_posts(array(
        'post_type' => 'page', // Post type is 'page'
        'post_status'   => 'publish', // Post status is 'publish'
        'posts_per_page'    => -1, // Get all pages
        'fields'    => 'ids', // Only get post IDs
    ));

    // For each page ID
    foreach ($page_ids as $page_id) {
        // Call the Irent_Update_Page_Content function with the page ID and an empty shortcode
        Irent_Update_Page_Content($page_id, '');
    }
}

/**
 * This function updates the content of a page.
 *
 * @param int $page_id The ID of the page to update.
 * @param string $custom_shortcode The shortcode to add to the page content.
 *
 * @return void
 */
function Irent_Update_Page_Content($page_id, $custom_shortcode = '')
{
    // Global WordPress database object
    global $wpdb;

    // Validate and sanitize the page ID
    $page_id = absint($page_id);

    if (!$page_id && $page_id != 0) {
        return  array(
                    'type'      =>  'error',
                    'content'   =>esc_html__('Error: Invalid page ID.', 'single-page-booking-system'),
                );
    }

    // Prepare the SQL query to get the original content of the page
    $sql = $wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $page_id);

    // Execute the SQL query and get the original page content
    $page_content = $wpdb->get_var($sql);

    // Remove all occurrences of shortcodes starting with [irent_shortcode and any preceding newline
    $page_content = preg_replace("/\n*\[irent_shortcode.*?\]\n*/", "\n", $page_content);

    // If $custom_shortcode is not an empty string, add it to the end of the page content
    if ($custom_shortcode !== '') {
        // Sanitize the custom shortcode
        $custom_shortcode = sanitize_text_field($custom_shortcode);

        // Add the custom shortcode to the end of the page content
        $page_content = rtrim($page_content) . "\n" . $custom_shortcode . "\n";
    }

    // Prepare the data for the update
    $data = array( 'post_content' => $page_content );
    $where = array( 'ID' => $page_id );
    $data_format = array( '%s' );
    $where_format = array( '%d' );

    // Update the page content
    $updated = $wpdb->update($wpdb->posts, $data, $where, $data_format, $where_format);

    if (false === $updated) {
        return  array(
                    'type'      =>  'error',
                    'content'   =>  esc_html__('Error: Failed to update the page content.', 'single-page-booking-system')
        );
    } else {
        return  array(
                    'type'      =>  esc_html('updated'),
                    'content'   =>  esc_html__('Your settings were saved correctly, please check your landing page to display the search engine.', 'single-page-booking-system'),
                );
    }
}

/**
 * This function generates the shortcode to add the plugin.
 *
 * @param array $atts An array of attributes including:
 *                    'code'     => JavaScript code.
 *                    'map_key'  => The key to the map.
 *                    'theme'    => The colour palette. Default 'beige'.
 *                    'language' => Language. Default 'en'.
 *
 * @return string The HTML code for the shortcode.
 */
function Irent_Generate_shortcode($atts)
{
    // Define default attributes
    $default_atts = array(
        'code' => '',       // Attribute for JavaScript code
        'map_key' => '',    // Attribute for the map key
        'theme' => 'beige', // Attribute for the colour palette
        'language' => 'en', // Attribute for language
    );

    // Merge user-defined attributes with default attributes
    $shortcode_atts = shortcode_atts($default_atts, $atts);

    // Sanitize and escape attributes
    array_walk($shortcode_atts, function (&$value) {
        $value = esc_attr(sanitize_text_field($value));
    });

    // Validate attributes
    if (empty($shortcode_atts['code']) || empty($shortcode_atts['map_key'])) {
        return 'Error: code or map_key is missing.';
    }

    // Generate output
    $shortcode_output = '';
    $shortcode_output .= sprintf(
        '<irent-script website-code="%s" map-key="%s" theme="%s" language="%s"></irent-script>',
        $shortcode_atts['code'],
        $shortcode_atts['map_key'],
        $shortcode_atts['theme'],
        $shortcode_atts['language']
    );
    wp_enqueue_script('irent-script', 'https://scriptvjs.i-rent.net/js/app.js', array(), null, true);

    // Return the output
    return $shortcode_output;
}
// Register the shortcode for users to use it
add_shortcode('irent_shortcode', 'Irent_Generate_shortcode');

/**
 * This function connects to the I-Rent API.
 *
 * @return mixed It returns a string with an error message if there's an error, or the response code from the API if the request is successful.
 */
function Irent_Connect_API()
{
    $configuration_table = Irent_Get_Data_DB();

    // Prepare the API data
    $api_data = array(
        'username'  => sanitize_text_field($configuration_table[0]['username']),
        'password'  => sanitize_text_field($configuration_table[0]['password']),
        'type'      => 'plugin'
    );

    // API URL
    $api_url = esc_url('https://api.auth.i-rent.net/apps/authenticate');

    // Check if the API data is an array
    if (!is_array($api_data)) {
        return '<div class="error"><p>'. esc_html__('Invalid API data.', 'single-page-booking-system').'</p></div>';
    }

    // API request
    $response = wp_remote_post(
        $api_url,
        array(
            'method'    => 'POST',
            'body'      => wp_json_encode($api_data),
            'headers'   => array(
                'Content-Type'  => 'application/json',
            ),
        )
    );

    // Handle the API response
    if (is_wp_error($response)) {
        return '<div class="error"><p>'. esc_html__('Error when making the API request.', 'single-page-booking-system').'</p></div>';
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        if (!is_numeric($response_code)) {
            return '<div class="error"><p>'. esc_html__('Invalid response code.', 'single-page-booking-system').'</p></div>';
        }
        $response_code = intval($response_code);
        $messages = array(
            401 =>  array(
                        'type'      =>  'error',
                        'content'   =>  esc_html__('Username or password is incorrect.', 'single-page-booking-system')
                    ),
            400 =>  array(
                        'type'      =>  'error',
                        'content'   =>  esc_html__('Error: You must fill in all fields (username and password).', 'single-page-booking-system')
                    ),
        );
        return $response_code === 200 ? $response : ($messages[$response_code] ?? array('type'  =>  'error','content'   =>  esc_html__('Unexpected response code: ', 'single-page-booking-system') . $response_code));
    }
}

// Execute in terminal the command wp i18n make-pot . languages/wp-irent.pot
if (!function_exists('Irent_Load_textdomain')) {
    /**
     * Function to load translations.
     *
     * @return void
     */
    function Irent_Load_textdomain()
    {
        // Load the text domain for translation of the plugin
        load_plugin_textdomain('single-page-booking-system', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}
add_action('plugins_loaded', 'Irent_Load_textdomain');

if (!function_exists('Irent_Set_translations')) {
    /**
     * Function to manage the index of translations.
     *
     * @param string $mofile The URL of the file.
     * @param string $domain The URL of the site.
     *
     * @return string Returns the language to display.
     */
    function Irent_Set_translations($mofile, $domain)
    {
        // Check if the domain is 'single-page-booking-system' and not 'default'
        if ('single-page-booking-system' === $domain && 'default' !== $domain) {
            // Get the locale of the plugin
            $locale = apply_filters('plugin_locale', determine_locale(), $domain);
            $locale = substr($locale, 0, 3);

            // Define an associative array with the language codes as keys
            $locales = array(
                'en_' => 'GB.mo',
                'es_' => 'ES.mo',
                'fr_' => 'FR.mo',
                'de_' => 'DE.mo',
                'nl_' => 'NL.mo',
                'ru_' => 'RU.mo',
            );

            // If the selected language is in the array, set its status to 'selected'
            if (isset($locales[$locale])) {
                $locale .= $locales[$locale];
            } else {
                $locale = 'en_GB.mo';
            }

            // Set the path of the .mo file
            $mofile = plugin_dir_path(__FILE__) . 'languages/wp-irent_' . $locale;
        }
        return $mofile;
    }
}
add_filter('load_textdomain_mofile', 'Irent_Set_translations', 10, 2);

/**
 * Function rendering the options page in the administration panel.
 * Inside your main plugin file, add the hook to process the form.
 *
 * @return void
 */
function Irent_Plugin_Options_Page()
{
    global $wpdb;
    $message = '';

    // Check if the form has been submitted
    if (isset($_POST['irent_submit'])) {
        // Verify the nonce
        if (!isset($_POST['irent_submit_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['irent_submit_nonce'])), 'irent_submit_action')) {
            die('Invalid nonce for irent_submit');
        }
 
        // Sanitize the username and password
        $username = sanitize_user($_POST['irent_username']);
        $password = sanitize_text_field($_POST['irent_password']);

        // Get the configuration table
        $configuration_table = Irent_Get_Data_DB();
        
        // Check if the username is set and different from the input or if the table does not exist
        if (isset($configuration_table[0]['username']) && $configuration_table[0]['username'] !== $username) {
            // Restore the database
            Irent_Restore_DB();
        }
        
        // Update the database with the new username and password
        Irent_Update_DB(
            array(
                'username'  => $username,
                'password'  => $password
            )
        );

        // Receive the API response code
        $response = Irent_Connect_API();

        // Check the API response
        if (wp_remote_retrieve_response_code($response) === 200) {
            // Update the login status in the database
            Irent_Update_DB(
                array(
                    'login_status' => true
                )
            );
        } else {
            // Store the error message
            $message = $response ;
        }
    }

    // Check if the form has been submitted
    if (isset($_POST['irent_save_changes'])) {
        // Verify the nonce
        if (!isset($_POST['irent_save_changes_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['irent_save_changes_nonce'])), 'irent_save_changes_action')) {
            die('Invalid nonce for irent_save_changes');
        }

        // Get the previous configurations
        $previous_configuration_data = Irent_Get_Data_DB();
        $previous_page_id = $previous_configuration_data[0]['pageId'];

        // Set the new configurations
        $new_settings = array(
            'pageId'        => intval($_POST['irent_destination']),
            'language'      => sanitize_text_field($_POST['irent_language']),
            'theme'         => sanitize_text_field($_POST['irent_theme']),
            'code'          => sanitize_text_field($_POST['irent_accommodation']),
            'map_key'       => sanitize_text_field($_POST['irent_map']),
            'short_code'    => true
        );

        $hasError = false;
        $message = '';
    
        $errors = array(
            'pageId'    => esc_html__('Wrong Page ID.', 'single-page-booking-system'),
            'language'  => esc_html__('No language selected.', 'single-page-booking-system'),
            'theme'     => esc_html__('No colour selected.', 'single-page-booking-system'),
            'code'      => esc_html__('No accommodation selected.', 'single-page-booking-system')
        );
                
        foreach ($errors as $field => $error) {
            // Check if the field is empty and is not 'pageId', or if it's 'pageId' and not zero
            if ((empty($new_settings[$field]) && $field != 'pageId') || ($field == 'pageId' && $new_settings[$field] != 0 && !get_post($new_settings[$field]))) {
                $message['content'] .= $error . "\n";
                $hasError = true;
            }
        }
        $hasError? $message['type'] = 'error' : '';

        if (!$hasError) {
            // Set the pages IDs
            $page_id = $new_settings['pageId'];
            // Set the short using the new data
            $custom_shortcode = sprintf(
                '[irent_shortcode code="%s" map_key="%s" theme="%s" language="%s"]',
                esc_attr($new_settings['code']),
                esc_attr($new_settings['map_key']),
                esc_attr($new_settings['theme']),
                esc_attr($new_settings['language'])
            );

            // Remove the custom code from the previously selected page if you have changed the selection
            if ($previous_page_id !== $page_id) {
                if ($previous_page_id != 0) {
                    $message = Irent_Update_Page_Content($previous_page_id);
                }
            
                if ($page_id != 0) {
                    // Update the content of the new selected page
                    $message = Irent_Update_Page_Content($page_id, $custom_shortcode);
                }
            } else {
                if ($page_id != 0) {
                    $message = Irent_Update_Page_Content($page_id, $custom_shortcode);
                } else {
                    $message =  array(
                        'type'      =>  esc_html('updated'),
                        'content'   =>  esc_html__('The shortcode has been created successfully', 'single-page-booking-system'),
                    );
                }
            }

            Irent_Update_DB(
                array(
                    'pageId'=>$page_id,
                    'language'=>$new_settings['language'],
                    'theme'=>$new_settings['theme'],
                    'code'=>$new_settings['code'],
                    'map_key'=>$new_settings['map_key'],
                    'custom_code'=>$custom_shortcode,
                    'short_code'=>$new_settings['short_code']
                )
            );
        }
    }

    if (isset($_POST['irent_close_session'])) {
        // Verify the nonce
        if (!isset($_POST['irent_close_session_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['irent_close_session_nonce'])), 'irent_close_session_action')) {
            die('Invalid nonce for irent_close_session');
        }
        
        // Call the function to log out the user
        Irent_LogOut();
    
        // Display a message to the user
        $message =  array(
            'type'      =>  esc_html('updated'),
            'content'   =>  esc_html__('You are logged out', 'single-page-booking-system'),
        );
    }

    if (isset($_POST['irent_clear_shortcode'])) {
        // Verify the nonce
        if (!isset($_POST['irent_clear_shortcode_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['irent_clear_shortcode_nonce'])), 'irent_clear_shortcode_action')) {
            die('Invalid nonce for irent_clear_shortcode');
        }
    
        // Call the function to clear the shortcode
        Irent_Clear_Shortcode();
    
        // Display a message to the user
        $message    =   array(
            'type'      =>  esc_html('updated'),
            'content'   =>  esc_html__('The ShortCode has been cleared from all published pages.', 'single-page-booking-system'),
        );
    }
    
    
    if (Irent_Get_Logged()) {
        // Receive the API response code
        $response = Irent_Connect_API();
    
        if (wp_remote_retrieve_response_code($response) === 200) {
            // Process the API response
            $api_response_body = wp_remote_retrieve_body($response);
            $api_response_data = json_decode($api_response_body, true);
            $json_output = json_encode($api_response_data, JSON_PRETTY_PRINT);
    
            // Check if the response contains an array of elements with "name" and "code".
            $elements = isset($api_response_data['websites']) && is_array($api_response_data['websites']) ? $api_response_data['websites'] : array();
        } else {
            Irent_Logout();
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    
        $configuration_data = Irent_Get_Data_DB();
        $required_fields = array('pageId', 'language', 'theme', 'code', 'map_key', 'short_code');
        // Check if the data contains the required fields
        if (!array_diff($required_fields, array_keys($configuration_data[0]))) {
            // Sanitize and escape the data
            $sanitized_data = array_map('sanitize_text_field', $configuration_data[0]);
            $escaped_data = array_map('esc_attr', $sanitized_data);
    
            // Create the custom shortcode
            $custom_shortcode = sprintf(
                '[irent_shortcode code="%s" map_key="%s" theme="%s" language="%s"]',
                $escaped_data['code'],
                $escaped_data['map_key'],
                $escaped_data['theme'],
                $escaped_data['language']
            );
        } else {
            $message = array(
                'type'      =>  'error',
                'content'   =>  esc_html__('Missing required configuration data.', 'single-page-booking-system'),
            );
        }
    
        ?>
        <form method="post" action="">
            <div class="wrap">
                <div class="box1"><img  src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'admin/images/imagen.png'); ?>" alt="Logo"></div>
                <p><?php echo esc_html__('Booking system - Channel Gateway - Management', 'single-page-booking-system'); ?></p>
                <hr style='height: 1px;  background-color: black;'>
                <?php
                if ($message !== '') {
                    ?>
                    <div class="<?php echo esc_attr($message['type']); ?>">
                        <p><?php echo esc_html(nl2br($message['content'])); ?>
                    </div>
                    <?php
                }
                ?>
                <div>
                    <h4><?php echo esc_html__('Configuration of the booking script', 'single-page-booking-system'); ?></h4>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">  <label for="blogname"><?php echo esc_html__('Booking Source/WebSite:', 'single-page-booking-system'); ?></label></th>
                                <td>
                                    <select class="custom-select" id="irent_accommodation" name="irent_accommodation">
                                        <?php
                                        // Create the <select> element with the element options
                                        foreach ($elements as $element) {
                                            $name = esc_attr($element['name'] ?? '');
                                            $code = esc_attr($element['code'] ?? '');
                                            $selected = ($configuration_data[0]['code'] === $code) ? ' selected' : '';
                                            printf('<option value="%s"%s>%s</option>', $code, esc_attr($selected), $name);
                                        }                                        
                                        ?>
                                    </select>
                                    <p class="description"><?php echo esc_html__('Choose from the list any of your websites published from your i-rent.net account to list the accommodations published on it.', 'single-page-booking-system'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">  <label for="blogname"><?php echo esc_html__('Language:', 'single-page-booking-system'); ?></label></th>
                                <td>
                                    <select class="custom-select" id="irent_language" name="irent_language" >
                                        <?php
                                        //Define an associative array with the language code keys and their display names
                                        $languages = array(
                                            'en' => esc_html__('English', 'single-page-booking-system'),
                                            'es' => esc_html__('Spanish', 'single-page-booking-system'),
                                            'nl' => esc_html__('Dutch', 'single-page-booking-system'),
                                            'fr' => esc_html__('French', 'single-page-booking-system'),
                                            'de' => esc_html__('German', 'single-page-booking-system')
                                        );
                                        
                                        foreach ($languages as $code => $name) {
                                            $selected = ($configuration_data[0]['language'] === $code) ? ' selected' : '';
                                            printf('<option value="%s"%s>%s</option>', esc_attr($code), esc_attr($selected), esc_html($name));
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php echo esc_html__('Choose the language in which the accommodation search and booking engine.', 'single-page-booking-system'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">  <label for="blogname"><?php echo esc_html__('Theme:', 'single-page-booking-system'); ?></label></th>
                                <td>
                                    <select class="custom-select" id="irent_theme" name="irent_theme" >
                                        <?php
                                        //Define an associative array with the language code keys and their display names
                                        $theme = array(
                                            'beige' => esc_html__('Beige', 'single-page-booking-system'),
                                            'blue' => esc_html__('Blue', 'single-page-booking-system'),
                                            'green' => esc_html__('Green', 'single-page-booking-system'),
                                            'red' => esc_html__('Red', 'single-page-booking-system'),
                                            'lblue' => esc_html__('Light blue', 'single-page-booking-system'),
                                            'orange' => esc_html__('Orange', 'single-page-booking-system')
                                        );
                                        
                                        foreach ($theme as $code => $name) {
                                            $selected = ($configuration_data[0]['theme'] === $code) ? ' selected' : '';
                                            printf('<option value="%s"%s>%s</option>', esc_attr($code), esc_attr($selected), esc_html($name));
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php echo esc_html__('Select from the options the colour palette that best suits your website.', 'single-page-booking-system'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="blogname"><?php echo esc_html__('Google Map Api Key:', 'single-page-booking-system'); ?></label></th>
                                <td>
                                    <input type="text" class="regular-text" id="irent_map" name="irent_map" value="<?php echo esc_attr($configuration_data[0]['map_key'])?>">
                                    <p class="description"><?php echo esc_html__('For the accommodation map to display correctly, you must have the map code generated in your Google Cloud Platform account.', 'single-page-booking-system'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">  <label for="blogname"><?php echo esc_html__('Destination Page:', 'single-page-booking-system');?></label></th>
                                <td>
                                    <select class="custom-select" id="irent_destination" name="irent_destination">
                                        <option value="0"><?php echo esc_html__('None Page', 'single-page-booking-system') ?></option>
                                        <?php
                                        // Get the list of pages
                                        $pages = get_pages();
                                        foreach ($pages as $page) {
                                            $selected = ($configuration_data[0]['pageId'] == esc_attr($page->ID)) ? ' selected' : '';
                                            printf('<option value="%s"%s>%s</option>', esc_attr($page->ID), esc_attr($selected), esc_html($page->post_title));
                                        }
                                        ?>
                                    </select>
                                    <input type="hidden" name="previous_page_id" value="<?php echo esc_attr($configuration_data[0]['pageId']) ?>">
                                    <p class="description"><?php echo esc_html__('Select the page where you want the accommodation search and booking engine to be displayed.', 'single-page-booking-system')?></p>
                                </td>
                            </tr>
                            <?php
                            if ($configuration_data[0]['short_code']) {
                                ?>
                                <tr>
                                    <th scope="row">  <label for="blogname"><?php echo esc_html__('ShortCode:', 'single-page-booking-system')?></label></th>
                                    <td>
                                        <?php
                                        if ($configuration_data[0]['pageId'] !== 0) {
                                            printf('<p><a target="_blank" href="%s">%s</a></p>', esc_url(get_permalink($configuration_data[0]['pageId'])), esc_html__('View current page', 'single-page-booking-system'));
                                        }
                                        ?>
                                        <p><?php echo esc_html__('Copy and paste the following shortcode into the desired page:', 'single-page-booking-system');?></p>
                                        <?php
                                        printf('<pre>%s</pre>', esc_html($custom_shortcode));
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">  <label for="blogname"><?php echo esc_html__('Clear ShortCode:', 'single-page-booking-system')?></label></th>
                                    <input type="hidden" name="irent_clear_shortcode_nonce" value="<?php echo esc_attr(wp_create_nonce('irent_clear_shortcode_action')); ?>">
                                    <td>
                                        <span><input type="submit" name="irent_clear_shortcode" value="<?php echo esc_html__('Clear', 'single-page-booking-system');?>" class="button-primary error"></span>
                                        <p><?php echo esc_html__('If you need to clear the ShortCode from all your pages, please click the button. Please note, this action cannot be undone.', 'single-page-booking-system');?></p>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <td></td>
                                <input type="hidden" name="irent_close_session_nonce" value="<?php echo esc_attr(wp_create_nonce('irent_close_session_action')); ?>">
                                <input type="hidden" name="irent_save_changes_nonce" value="<?php echo esc_attr(wp_create_nonce('irent_save_changes_action')); ?>">
                                <td>
                                    <span><input type="submit" name="irent_save_changes" value="<?php echo esc_html__('Save Changes', 'single-page-booking-system');?>" class="button-primary"></span>
                                    <span style="margin-left:20px"><input type="submit" name="irent_close_session" value="<?php echo esc_html__('Log Out', 'single-page-booking-system'); ?>" class="button-secondary">
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <br>
                </div>
            </div>
        </form>
        <?php
    } else {
        // Get the current values of the plugin option
        $plugin_options = get_option('irent_plugin_options');
        $username = isset($plugin_options['username']) ? $plugin_options['username'] : '';
        $password = isset($plugin_options['password']) ? $plugin_options['password'] : '';
    
        ?>
        <div style="width: 100%; margin: 1% auto 0; border: 0px solid #000; padding: 20px; ">
            <form method="post">
                <div class="box1">  <img  src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'admin/images/imagen.png'); ?>" alt="Logo"></div>
                <p><?php echo esc_html__('Booking System - Channel Gateway - Management', 'single-page-booking-system') ?></p>
                <hr style='height: 1px;  background-color: black;'>
                <?php
                if ($message !== '') {
                    ?>
                        <div class="<?php echo esc_attr($message['type']); ?>">
                        <p><?php echo esc_html(nl2br($message['content'])); ?>
                    </div>
                    <?php
                }
                ?>
                <h4><?php echo esc_html__('Welcome to your holiday rental booking system', 'single-page-booking-system') ?></h4>
                <table>
                    <tr>
                        <td><label for="irent_username"><?php echo esc_html__('User:', 'single-page-booking-system') ?></label></td>
                        <td><input type="text" id="irent_username" name="irent_username" value="<?php echo esc_attr($username); ?>"><br></td>
                    </tr>
                    <tr>
                        <td><label for="irent_password"><?php echo esc_html__('Password:', 'single-page-booking-system') ?></label></td>
                        <td><input type="password" id="irent_password" name="irent_password" value="<?php echo esc_attr($password); ?>"><br></td>
                    </tr>
                    <tr>
                        <td><input type="hidden" name="irent_submit_nonce" value="<?php echo esc_attr(wp_create_nonce('irent_submit_action')) ?>"></td>
                        <td><input type="submit" name="irent_submit" value="<?php echo esc_html__('Login', 'single-page-booking-system'); ?>" class="button-primary"></td>
                    </tr>
                </table>
                <br>
            </form>
        </div>
        <?php
    }
}
