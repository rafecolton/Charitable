<?php
/**
 * Charitable Shortcodes Hooks.
 *
 * Action/filter hooks used for Charitable shortcodes
 *
 * @package     Charitable/Functions/Shortcodes
 * @version     1.2.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2015, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Register shortcodes.
 *
 * @see Charitable_Campaigns_Shortcode::display()
 * @see Charitable_My_Donations_Shortcode::display()
 * @see Charitable_Donation_Receipt_Shortcode::display()
 * @see Charitable_Login_Shortcode::display()
 * @see Charitable_Registration_Shortcode::display()
 * @see Charitable_Profile_Shortcode::display()
 * @see Charitable_Forgot_Password_Shortcode::display()
 * @see Charitable_Reset_Password_Shortcode::display()
 */
add_shortcode( 'campaigns', array( 'Charitable_Campaigns_Shortcode', 'display' ) );
add_shortcode( 'donation_receipt', array( 'Charitable_Donation_Receipt_Shortcode', 'display' ) );
add_shortcode( 'charitable_my_donations', array( 'Charitable_My_Donations_Shortcode', 'display' ) );
add_shortcode( 'charitable_login', array( 'Charitable_Login_Shortcode', 'display' ) );
add_shortcode( 'charitable_registration', array( 'Charitable_Registration_Shortcode', 'display' ) );
add_shortcode( 'charitable_forgot_password', array( 'Charitable_Forgot_Password_Shortcode', 'display' ) );
add_shortcode( 'charitable_reset_password', array( 'Charitable_Reset_Password_Shortcode', 'display' ) );
add_shortcode( 'charitable_profile', array( 'Charitable_Profile_Shortcode', 'display' ) );
