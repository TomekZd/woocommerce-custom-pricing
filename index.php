<?php
/**
 * Plugin Name: Enhanced WooCommerce Role-Based Pricing
 * Plugin URI: 
 * Description: Set different prices in WooCommerce based on user roles, including separate pricing for guests.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: 
 * Text Domain: enhanced-wc-role-pricing
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class Enhanced_WC_Role_Based_Pricing {

    public function __construct() {
        // Add product tab for role-based pricing
        add_filter('woocommerce_product_data_tabs', array($this, 'add_role_pricing_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_role_pricing_fields'));
        
        // Save role-based pricing data
        add_action('woocommerce_process_product_meta', array($this, 'save_role_pricing_fields'));
        
        // Modify prices based on user role
        add_filter('woocommerce_product_get_price', array($this, 'get_role_based_price'), 10, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'get_role_based_regular_price'), 10, 2);
        add_filter('woocommerce_product_get_sale_price', array($this, 'get_role_based_sale_price'), 10, 2);
        
        // Handle variable products
        add_filter('woocommerce_variation_price_html', array($this, 'change_variation_price_html'), 10, 2);
        add_filter('woocommerce_variable_price_html', array($this, 'change_variable_price_html'), 10, 2);
        add_filter('woocommerce_product_variation_get_price', array($this, 'get_role_based_price'), 10, 2);
        add_filter('woocommerce_product_variation_get_regular_price', array($this, 'get_role_based_regular_price'), 10, 2);
        add_filter('woocommerce_product_variation_get_sale_price', array($this, 'get_role_based_sale_price'), 10, 2);

        // Add role pricing to admin columns
        add_filter('manage_edit-product_columns', array($this, 'add_role_pricing_column'));
        add_action('manage_product_posts_custom_column', array($this, 'show_role_pricing_column'), 10, 2);
        
        // Add settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    /**
     * Add role pricing tab to WooCommerce product data tabs
     */
    public function add_role_pricing_tab($tabs) {
        $tabs['role_pricing'] = array(
            'label'    => __('Role Pricing', 'enhanced-wc-role-pricing'),
            'target'   => 'role_pricing_data',
            'class'    => array(),
            'priority' => 61,
        );
        return $tabs;
    }

    /**
     * Add role pricing fields to the new product tab
     */
    public function add_role_pricing_fields() {
        global $post;
        
        // Get all user roles
        $user_roles = $this->get_user_roles();
        
        echo '<div id="role_pricing_data" class="panel woocommerce_options_panel">';
        
        echo '<div class="options_group">';
        echo '<p class="form-field"><strong>' . __('Set different prices based on user roles', 'enhanced-wc-role-pricing') . '</strong></p>';
        
        // Guest pricing section
        echo '<h4 style="padding-left: 12px; color: #23282d; background-color: #f9f9f9; padding-top: 5px; padding-bottom: 5px;">' . __('Guest Pricing (Not Logged In)', 'enhanced-wc-role-pricing') . '</h4>';
        
        // Guest regular price field
        woocommerce_wp_text_input(
            array(
                'id'          => '_guest_regular_price',
                'label'       => __('Regular Price', 'enhanced-wc-role-pricing') . ' (' . get_woocommerce_currency_symbol() . ')',
                'desc_tip'    => true,
                'description' => __('Regular price for guests (not logged in users)', 'enhanced-wc-role-pricing'),
                'type'        => 'text',
                'value'       => get_post_meta($post->ID, '_guest_regular_price', true),
                'data_type'   => 'price',
            )
        );
        
        // Guest sale price field
        woocommerce_wp_text_input(
            array(
                'id'          => '_guest_sale_price',
                'label'       => __('Sale Price', 'enhanced-wc-role-pricing') . ' (' . get_woocommerce_currency_symbol() . ')',
                'desc_tip'    => true,
                'description' => __('Sale price for guests (not logged in users)', 'enhanced-wc-role-pricing'),
                'type'        => 'text',
                'value'       => get_post_meta($post->ID, '_guest_sale_price', true),
                'data_type'   => 'price',
            )
        );
        
        echo '<hr style="margin: 15px 12px;" />';
        
        // Role-based pricing
        foreach ($user_roles as $role_key => $role_name) {
            if ($role_key === 'administrator') continue; // Skip admin role
            
            // Get saved role prices
            $role_regular_price = get_post_meta($post->ID, '_role_regular_price_' . $role_key, true);
            $role_sale_price = get_post_meta($post->ID, '_role_sale_price_' . $role_key, true);
            
            // Add heading for this role
            echo '<h4 style="padding-left: 12px; color: #23282d; background-color: #f9f9f9; padding-top: 5px; padding-bottom: 5px;">' . $role_name . '</h4>';
            
            // Regular price field
            woocommerce_wp_text_input(
                array(
                    'id'          => '_role_regular_price_' . $role_key,
                    'label'       => __('Regular Price', 'enhanced-wc-role-pricing') . ' (' . get_woocommerce_currency_symbol() . ')',
                    'desc_tip'    => true,
                    'description' => sprintf(__('Regular price for %s users', 'enhanced-wc-role-pricing'), $role_name),
                    'type'        => 'text',
                    'value'       => $role_regular_price,
                    'data_type'   => 'price',
                )
            );
            
            // Sale price field
            woocommerce_wp_text_input(
                array(
                    'id'          => '_role_sale_price_' . $role_key,
                    'label'       => __('Sale Price', 'enhanced-wc-role-pricing') . ' (' . get_woocommerce_currency_symbol() . ')',
                    'desc_tip'    => true,
                    'description' => sprintf(__('Sale price for %s users', 'enhanced-wc-role-pricing'), $role_name),
                    'type'        => 'text',
                    'value'       => $role_sale_price,
                    'data_type'   => 'price',
                )
            );
            
            if (array_key_last($user_roles) !== $role_key) {
                echo '<hr style="margin: 15px 12px;" />';
            }
        }
        
        echo '</div>'; // Close options_group
        echo '</div>'; // Close role_pricing_data panel
    }
    
    /**
     * Save role pricing fields
     */
    public function save_role_pricing_fields($post_id) {
        // Save guest pricing
        if (isset($_POST['_guest_regular_price'])) {
            update_post_meta($post_id, '_guest_regular_price', wc_format_decimal(sanitize_text_field($_POST['_guest_regular_price'])));
        }
        
        if (isset($_POST['_guest_sale_price'])) {
            update_post_meta($post_id, '_guest_sale_price', wc_format_decimal(sanitize_text_field($_POST['_guest_sale_price'])));
        }
        
        // Save role pricing
        $user_roles = $this->get_user_roles();
        
        foreach ($user_roles as $role_key => $role_name) {
            if ($role_key === 'administrator') continue; // Skip admin role
            
            // Save regular price
            $regular_price_field = '_role_regular_price_' . $role_key;
            if (isset($_POST[$regular_price_field])) {
                update_post_meta($post_id, $regular_price_field, wc_format_decimal(sanitize_text_field($_POST[$regular_price_field])));
            }
            
            // Save sale price
            $sale_price_field = '_role_sale_price_' . $role_key;
            if (isset($_POST[$sale_price_field])) {
                update_post_meta($post_id, $sale_price_field, wc_format_decimal(sanitize_text_field($_POST[$sale_price_field])));
            }
        }
    }
    
    /**
     * Get role-based regular price
     */
    public function get_role_based_regular_price($price, $product) {
        if (!is_user_logged_in()) {
            // Return guest price if set
            $guest_price = get_post_meta($product->get_id(), '_guest_regular_price', true);
            if ('' !== $guest_price) {
                return $guest_price;
            }
            return $price;
        }
        
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        // Check each role the user has
        foreach ($user_roles as $role) {
            $role_price = get_post_meta($product->get_id(), '_role_regular_price_' . $role, true);
            
            if ('' !== $role_price) {
                return $role_price;
            }
        }
        
        return $price;
    }
    
    /**
     * Get role-based sale price
     */
    public function get_role_based_sale_price($price, $product) {
        if (!is_user_logged_in()) {
            // Return guest price if set
            $guest_price = get_post_meta($product->get_id(), '_guest_sale_price', true);
            if ('' !== $guest_price) {
                return $guest_price;
            }
            return $price;
        }
        
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        // Check each role the user has
        foreach ($user_roles as $role) {
            $role_price = get_post_meta($product->get_id(), '_role_sale_price_' . $role, true);
            
            if ('' !== $role_price) {
                return $role_price;
            }
        }
        
        return $price;
    }
    
    /**
     * Get role-based price (uses sale price if available, otherwise regular price)
     */
    public function get_role_based_price($price, $product) {
        if (!is_user_logged_in()) {
            // Check for guest sale price first
            $guest_sale_price = get_post_meta($product->get_id(), '_guest_sale_price', true);
            if ('' !== $guest_sale_price) {
                return $guest_sale_price;
            }
            
            // Check for guest regular price
            $guest_regular_price = get_post_meta($product->get_id(), '_guest_regular_price', true);
            if ('' !== $guest_regular_price) {
                return $guest_regular_price;
            }
            
            return $price;
        }
        
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        // Check each role the user has for sale price first
        foreach ($user_roles as $role) {
            $role_sale_price = get_post_meta($product->get_id(), '_role_sale_price_' . $role, true);
            if ('' !== $role_sale_price) {
                return $role_sale_price;
            }
        }
        
        // Check each role for regular price
        foreach ($user_roles as $role) {
            $role_regular_price = get_post_meta($product->get_id(), '_role_regular_price_' . $role, true);
            if ('' !== $role_regular_price) {
                return $role_regular_price;
            }
        }
        
        return $price;
    }
    
    /**
     * Change variable product price HTML
     */
    public function change_variable_price_html($price_html, $product) {
        // Option to customize variable price display for different roles
        return $price_html;
    }
    
    /**
     * Change variation price HTML
     */
    public function change_variation_price_html($price_html, $variation) {
        // Option to customize variation price display for different roles
        return $price_html;
    }
    
    /**
     * Add role pricing column to products list
     */
    public function add_role_pricing_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            if ('price' === $key) {
                $new_columns['role_pricing'] = __('Role Pricing', 'enhanced-wc-role-pricing');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Show role pricing in product list column
     */
    public function show_role_pricing_column($column, $post_id) {
        if ('role_pricing' === $column) {
            // Display guest pricing
            $guest_regular_price = get_post_meta($post_id, '_guest_regular_price', true);
            $guest_sale_price = get_post_meta($post_id, '_guest_sale_price', true);
            
            if ('' !== $guest_regular_price || '' !== $guest_sale_price) {
                echo '<strong>' . __('Guest', 'enhanced-wc-role-pricing') . ':</strong> ';
                
                if ('' !== $guest_regular_price && '' === $guest_sale_price) {
                    echo wc_price($guest_regular_price);
                } elseif ('' !== $guest_sale_price) {
                    echo '<del>' . wc_price($guest_regular_price) . '</del> ' . wc_price($guest_sale_price);
                }
                
                echo '<br>';
            }
            
            // Display role pricing
            $user_roles = $this->get_user_roles();
            $has_role_pricing = false;
            
            foreach ($user_roles as $role_key => $role_name) {
                if ($role_key === 'administrator') continue; // Skip admin role
                
                $role_regular_price = get_post_meta($post_id, '_role_regular_price_' . $role_key, true);
                $role_sale_price = get_post_meta($post_id, '_role_sale_price_' . $role_key, true);
                
                if ('' !== $role_regular_price || '' !== $role_sale_price) {
                    echo '<strong>' . $role_name . ':</strong> ';
                    
                    if ('' !== $role_regular_price && '' === $role_sale_price) {
                        echo wc_price($role_regular_price);
                    } elseif ('' !== $role_sale_price) {
                        echo '<del>' . wc_price($role_regular_price) . '</del> ' . wc_price($role_sale_price);
                    }
                    
                    echo '<br>';
                    $has_role_pricing = true;
                }
            }
            
            if ('' === $guest_regular_price && '' === $guest_sale_price && !$has_role_pricing) {
                echo '—';
            }
        }
    }
    
    /**
     * Add plugin settings page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Role-Based Pricing', 'enhanced-wc-role-pricing'),
            __('Role-Based Pricing', 'enhanced-wc-role-pricing'),
            'manage_woocommerce',
            'role-based-pricing',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function settings_init() {
        register_setting('role_pricing', 'enhanced_wc_role_pricing_settings');
        
        add_settings_section(
            'enhanced_wc_role_pricing_section',
            __('Role-Based Pricing Settings', 'enhanced-wc-role-pricing'),
            array($this, 'settings_section_callback'),
            'role_pricing'
        );
        
        add_settings_field(
            'enable_guest_pricing',
            __('Enable Guest Pricing', 'enhanced-wc-role-pricing'),
            array($this, 'enable_guest_pricing_render'),
            'role_pricing',
            'enhanced_wc_role_pricing_section'
        );
        
        add_settings_field(
            'enable_role_pricing',
            __('Enable Role-Based Pricing', 'enhanced-wc-role-pricing'),
            array($this, 'enable_role_pricing_render'),
            'role_pricing',
            'enhanced_wc_role_pricing_section'
        );
        
        add_settings_field(
            'price_display_format',
            __('Price Display Format', 'enhanced-wc-role-pricing'),
            array($this, 'price_display_format_render'),
            'role_pricing',
            'enhanced_wc_role_pricing_section'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure how role-based pricing works in your store.', 'enhanced-wc-role-pricing') . '</p>';
    }
    
    /**
     * Enable guest pricing field
     */
    public function enable_guest_pricing_render() {
        $options = get_option('enhanced_wc_role_pricing_settings');
        $checked = isset($options['enable_guest_pricing']) ? $options['enable_guest_pricing'] : '1';
        ?>
        <input type='checkbox' name='enhanced_wc_role_pricing_settings[enable_guest_pricing]' <?php checked($checked, '1'); ?> value='1'>
        <span class="description"><?php _e('Allow different pricing for guests (not logged in users)', 'enhanced-wc-role-pricing'); ?></span>
        <?php
    }
    
    /**
     * Enable role pricing field
     */
    public function enable_role_pricing_render() {
        $options = get_option('enhanced_wc_role_pricing_settings');
        $checked = isset($options['enable_role_pricing']) ? $options['enable_role_pricing'] : '1';
        ?>
        <input type='checkbox' name='enhanced_wc_role_pricing_settings[enable_role_pricing]' <?php checked($checked, '1'); ?> value='1'>
        <span class="description"><?php _e('Allow different pricing based on user roles', 'enhanced-wc-role-pricing'); ?></span>
        <?php
    }
    
    /**
     * Price display format field
     */
    public function price_display_format_render() {
        $options = get_option('enhanced_wc_role_pricing_settings');
        $selected = isset($options['price_display_format']) ? $options['price_display_format'] : 'normal';
        ?>
        <select name='enhanced_wc_role_pricing_settings[price_display_format]'>
            <option value='normal' <?php selected($selected, 'normal'); ?>><?php _e('Normal (Show current role price)', 'enhanced-wc-role-pricing'); ?></option>
            <option value='show_original' <?php selected($selected, 'show_original'); ?>><?php _e('Show original price with role discount', 'enhanced-wc-role-pricing'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Role-Based Pricing Settings', 'enhanced-wc-role-pricing'); ?></h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('role_pricing');
                do_settings_sections('role_pricing');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="max-width: 100%; margin-top: 20px; padding: 20px; background-color: #fff; border-left: 4px solid #00a0d2;">
                <h2><?php _e('Role-Based Pricing Information', 'enhanced-wc-role-pricing'); ?></h2>
                <p><?php _e('This plugin allows you to set different prices for products based on user roles.', 'enhanced-wc-role-pricing'); ?></p>
                <p><?php _e('You can configure prices for guests (not logged in users) and for each user role independently.', 'enhanced-wc-role-pricing'); ?></p>
                
                <h3><?php _e('Available User Roles', 'enhanced-wc-role-pricing'); ?></h3>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <?php
                    $user_roles = $this->get_user_roles();
                    foreach ($user_roles as $role_key => $role_name) {
                        if ($role_key !== 'administrator') {
                            echo '<li><strong>' . $role_name . '</strong> (' . $role_key . ')</li>';
                        }
                    }
                    ?>
                </ul>
                
                <p><strong><?php _e('Important Note:', 'enhanced-wc-role-pricing'); ?></strong> <?php _e('Role-based prices must be set individually for each product.', 'enhanced-wc-role-pricing'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get available user roles
     */
    private function get_user_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        return $wp_roles->get_names();
    }
}

// Initialize the plugin
$enhanced_wc_role_based_pricing = new Enhanced_WC_Role_Based_Pricing();

// Add settings link on plugin page
function enhanced_wc_role_pricing_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=role-based-pricing') . '">' . __('Settings', 'enhanced-wc-role-pricing') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'enhanced_wc_role_pricing_settings_link');
