<?php
/*
  Plugin Name: WPOrg Count
  Plugin URI: http://ciprianturcu.com
  Description: show the cdownload count of plugins
  Version: 1.0.0
  Author: ciprianturcu
  Author URI: http://ciprianturcu.com
  License: GPLv2 or later
  Text Domain: countdown-timer-one
 */

function plugins_api( $action, $args = array() ) {

    if ( is_array( $args ) ) {
        $args = (object) $args;
    }

    if ( ! isset( $args->per_page ) ) {
        $args->per_page = 24;
    }

    if ( ! isset( $args->locale ) ) {
        $args->locale = get_user_locale();
    }

    /**
     * Filters the WordPress.org Plugin Install API arguments.
     *
     * Important: An object MUST be returned to this filter.
     *
     * @since 2.7.0
     *
     * @param object $args   Plugin API arguments.
     * @param string $action The type of information being requested from the Plugin Install API.
     */
    $args = apply_filters( 'plugins_api_args', $args, $action );

    /**
     * Filters the response for the current WordPress.org Plugin Install API request.
     *
     * Passing a non-false value will effectively short-circuit the WordPress.org API request.
     *
     * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
     * If `$action` is 'hot_tags' or 'hot_categories', an array should be passed.
     *
     * @since 2.7.0
     *
     * @param false|object|array $result The result object or array. Default false.
     * @param string             $action The type of information being requested from the Plugin Install API.
     * @param object             $args   Plugin API arguments.
     */
    $res = apply_filters( 'plugins_api', false, $action, $args );

    if ( false === $res ) {
        $url = $http_url = 'http://api.wordpress.org/plugins/info/1.0/';
        if ( $ssl = wp_http_supports( array( 'ssl' ) ) )
            $url = set_url_scheme( $url, 'https' );

        $http_args = array(
            'timeout' => 15,
            'body' => array(
                'action' => $action,
                'request' => serialize( $args )
            )
        );
        $request = wp_remote_post( $url, $http_args );

        if ( $ssl && is_wp_error( $request ) ) {
            trigger_error(
                sprintf(
                    /* translators: %s: support forums URL */
                    __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
                    __( 'https://wordpress.org/support/' )
                ) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)' ),
                headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
            );
            $request = wp_remote_post( $http_url, $http_args );
        }

        if ( is_wp_error($request) ) {
            $res = new WP_Error( 'plugins_api_failed',
                sprintf(
                    /* translators: %s: support forums URL */
                    __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
                    __( 'https://wordpress.org/support/' )
                ),
                $request->get_error_message()
            );
        } else {
            $res = maybe_unserialize( wp_remote_retrieve_body( $request ) );
            if ( ! is_object( $res ) && ! is_array( $res ) ) {
                $res = new WP_Error( 'plugins_api_failed',
                    sprintf(
                        /* translators: %s: support forums URL */
                        __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
                        __( 'https://wordpress.org/support/' )
                    ),
                    wp_remote_retrieve_body( $request )
                );
            }
        }
    } elseif ( !is_wp_error($res) ) {
        $res->external = true;
    }

    /**
     * Filters the Plugin Install API response results.
     *
     * @since 2.7.0
     *
     * @param object|WP_Error $res    Response object or WP_Error.
     * @param string          $action The type of information being requested from the Plugin Install API.
     * @param object          $args   Plugin API arguments.
     */
    return apply_filters( 'plugins_api_result', $res, $action, $args );
}
add_action( 'init', 'get_plugin_install_count' );

function get_plugin_install_count( $plugin ) {
	$api = plugins_api( 'plugin_information', array(
		'slug' => $plugin,
		'fields' => array( 'active_installs' => true )
	) );

	if( ! is_wp_error( $api ) ) {
		return $api->downloaded;
	}
}

  echo get_plugin_install_count('contact-form-7');
  exit;
