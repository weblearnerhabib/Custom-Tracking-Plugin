<?php
ob_start(); // Add this line
/*
 * Plugin Name:       Custom Tracking Plugin
 * Plugin URI:        https://github.com/weblearnerhabib/
 * Description:       Custom Tracking Plugin to Track.
 * Version:           2.0.1
 * Requires at least: 5.3
 * Requires PHP:      7.2
 * Author:            Freelancer Habib
 * Author URI:        https://freelancer.com/u/csehabiburr183/
 * Text Domain:       ctphabib
 */

// Start session
session_start();

// Create database table on plugin activation
function create_tracking_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tracking_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        tracking_code varchar(100) NOT NULL,
        name varchar(100) NOT NULL,
        status varchar(50),
        service varchar(50),
        delivery_mode varchar(50),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_tracking_table');

// Admin menu to manage tracking data
function tracking_data_menu_page() {
    add_menu_page(
        'Tracking Data',     // Page title
        'Tracking Data',     // Menu title
        'manage_options',    // Capability
        'tracking-data',     // Menu slug
        'display_tracking_data_page', // Callback function to display the page content
        'dashicons-list-view' // Icon
    );

    // Add "Add Data" submenu
    add_submenu_page(
        'tracking-data',     // Parent menu slug
        'Add Tracking Data', // Page title
        'Add Data',          // Menu title
        'manage_options',    // Capability
        'add-tracking-data', // Menu slug
        'add_tracking_data_page' // Callback function to display the add data page
    );
}

add_action('admin_menu', 'tracking_data_menu_page');

// Callback function to display the tracking data page
function display_tracking_data_page() {
    if (isset($_GET['action']) && $_GET['action'] === 'edit') {
        $tracking_id = isset($_GET['tracking_id']) ? absint($_GET['tracking_id']) : 0;
        edit_tracking_data_page($tracking_id);
    } else {
        ?>
        <div class="wrap">
            <h2>Tracking Data</h2>
            <!-- Display your tracking data table or any other content here -->
            <?php echo display_tracked_data(); ?>
        </div>
        <?php
    }
}

// Callback function to display the add data page
function add_tracking_data_page() {
    if (isset($_POST['submit_tracking'])) {
        handle_admin_tracking_form_submission();
    }
    ?>
    <div class="wrap">
        <h2>Add Tracking Data</h2>

        <!-- Display the add data form -->
        <form method="post" action="">
            <label for="name">Name:</label>
            <input type="text" name="name" required>

            <label for="status">Status:</label>
            <select name="status">
                <option value="Processing">Processing</option>
                <option value="Shipped">Shipped</option>
                <option value="In Transit">In Transit</option>
                <option value="Delivered">Delivered</option>
                <!-- Add more options as needed -->
            </select>

            <label for="service">Service:</label>
            <input type="text" name="service">

            <label for="delivery_mode">Delivery Mode:</label>
            <input type="text" name="delivery_mode">

            <input type="submit" name="submit_tracking" value="Submit">
        </form>
    </div>
    <?php
}

// Function to handle form submission from admin page
function handle_admin_tracking_form_submission() {
    if (isset($_POST['submit_tracking'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tracking_data';

        // Generate a simple tracking code (you can use a more complex algorithm if needed)
        $tracking_code = 'MicroGeeks' . sprintf('%04d', mt_rand(0, 99999));

        $name = sanitize_text_field($_POST['name']);
        $status = sanitize_text_field($_POST['status']);
        $service = sanitize_text_field($_POST['service']);
        $delivery_mode = sanitize_text_field($_POST['delivery_mode']);

        $wpdb->insert(
            $table_name,
            array(
                'tracking_code' => $tracking_code,
                'name' => $name,
                'status' => $status,
                'service' => $service,
                'delivery_mode' => $delivery_mode,
            )
        );

        // Redirect to the admin tracking data page after submission
        wp_redirect(admin_url('admin.php?page=tracking-data'));
        exit();
    }
}

// Function to display tracked data
function display_tracked_data() {
    ob_start();

    // Display default tracked data
    echo "<p>Displaying all tracked data:</p>";

    // Fetch all tracked data from the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'tracking_data';
    $tracked_data = $wpdb->get_results("SELECT * FROM $table_name");

    // Display a table header
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Tracking Code</th>';
    echo '<th>Name</th>';
    echo '<th>Status</th>';
    echo '<th>Service</th>';
    echo '<th>Delivery Mode</th>';
    echo '<th>Edit</th>';
    // echo '<th>Delete</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    // Display all tracked data in rows
    foreach ($tracked_data as $data) {
        echo '<tr>';
        echo '<td>' . $data->id . '</td>';
        echo '<td>' . $data->tracking_code . '</td>';
        echo '<td>' . $data->name . '</td>';
        echo '<td>' . $data->status . '</td>';
        echo '<td>' . $data->service . '</td>';
        echo '<td>' . $data->delivery_mode . '</td>';
        echo '<td><a href="' . admin_url('admin.php?page=tracking-data&action=edit&tracking_id=' . $data->id) . '">Edit</a></td>';
        // echo '<td><a href="' . admin_url('admin.php?page=tracking-data&action=delete&tracking_id=' . $data->id) . '">Delete</a></td>';
        echo '</tr>';
    }

    // Close the table
    echo '</tbody>';
    echo '</table>';

    return ob_get_clean();
}

// Hook to add menu pages for edit and delete actions
function add_edit_delete_menu_pages() {
    add_action('admin_menu', function () {
        // Check if an action is requested
        if (isset($_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);
            $tracking_id = isset($_GET['tracking_id']) ? absint($_GET['tracking_id']) : 0;

            switch ($action) {
                case 'edit':
                    add_action('load-tracking-data_page_edit-tracking-data', function () use ($tracking_id) {
                        edit_tracking_data_page($tracking_id);
                    });
                    break;
                case 'delete':
                    delete_tracking_data_page($tracking_id);
                    break;
                default:
                    break;
            }
        }
    });
}

add_action('admin_menu', 'add_edit_delete_menu_pages');



// Function to handle edit action
function edit_tracking_data_page($tracking_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tracking_data';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle form submission
        $name = sanitize_text_field($_POST['name']);
        $status = sanitize_text_field($_POST['status']);
        $service = sanitize_text_field($_POST['service']);
        $delivery_mode = sanitize_text_field($_POST['delivery_mode']);

        $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'status' => $status,
                'service' => $service,
                'delivery_mode' => $delivery_mode,
            ),
            array('id' => $tracking_id)
        );

        // Redirect back to the admin tracking data page after update
        wp_redirect(admin_url('admin.php?page=tracking-data'));
        exit();
    } else {
        // Display the edit form
        $tracking_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $tracking_id));

        ?>
        <div class="wrap">
            <h2>Edit Tracking Data</h2>

            <!-- Display the edit form -->
            <form method="post" action="">
                <label for="name">Name:</label>
                <input type="text" name="name" value="<?php echo esc_attr($tracking_data->name); ?>" required>

                <label for="status">Status:</label>
                <select name="status">
                    <option value="Processing" <?php selected($tracking_data->status, 'Processing'); ?>>Processing</option>
                    <option value="Shipped" <?php selected($tracking_data->status, 'Shipped'); ?>>Shipped</option>
                    <option value="In Transit" <?php selected($tracking_data->status, 'In Transit'); ?>>In Transit</option>
                    <option value="Delivered" <?php selected($tracking_data->status, 'Delivered'); ?>>Delivered</option>
                    <!-- Add more options as needed -->
                </select>

                <label for="service">Service:</label>
                <input type="text" name="service" value="<?php echo esc_attr($tracking_data->service); ?>">

                <label for="delivery_mode">Delivery Mode:</label>
                <input type="text" name="delivery_mode" value="<?php echo esc_attr($tracking_data->delivery_mode); ?>">

                <input type="submit" name="submit_tracking" value="Update">
            </form>
        </div>
        <?php
    }
}

// Function to handle delete action
function delete_tracking_data_page($tracking_id) {
    if ($tracking_id > 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tracking_data';
        $result = $wpdb->delete($table_name, array('id' => $tracking_id));

        if ($result === false) {
            // Log or display the error
            error_log($wpdb->last_error);
        }

        // Redirect back to the admin tracking data page after deletion
        wp_redirect(admin_url('admin.php?page=tracking-data'));
        exit();
    }
}


// Shortcode to display the search form and results on the front end
function tracking_search_form_results_shortcode() {
    ob_start();

    // Handle search form submission
    if (isset($_POST['submit_search'])) {
        $search_tracking_code = sanitize_text_field($_POST['search_tracking_code']);

        // Display search results based on the entered tracking code
        echo "<p>Search Results for Tracking Code: $search_tracking_code</p>";

        global $wpdb;
        $table_name = $wpdb->prefix . 'tracking_data';
        $search_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT tracking_code, name, status, service, delivery_mode FROM $table_name WHERE tracking_code = %s",
                $search_tracking_code
            )
        );

        // Display a Bootstrap table for search results
        echo '<table border="1" class="table table-striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Tracking Code</th>';
        echo '<th>Name</th>';
        echo '<th>Status</th>';
        echo '<th>Service</th>';
        echo '<th>Delivery Mode</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // Display search results in rows
        foreach ($search_results as $result) {
            echo '<tr>';
            echo '<td>' . $result->tracking_code . '</td>';
            echo '<td>' . $result->name . '</td>';
            echo '<td>' . $result->status . '</td>';
            echo '<td>' . $result->service . '</td>';
            echo '<td>' . $result->delivery_mode . '</td>';
            echo '</tr>';
        }

        // Close the table for search results
        echo '</tbody>';
        echo '</table>';
    }

    // Display the search form
    ?>
    <form method="post" action="">
        <label for="search_tracking_code">Enter Tracking Code:</label>
        <input type="text" name="search_tracking_code" required>
        <input type="submit" name="submit_search" value="Search">
    </form>
    <?php

    return ob_get_clean();
}

add_shortcode('tracking_search_form_results', 'tracking_search_form_results_shortcode');
