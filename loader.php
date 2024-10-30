<?php

/*
	Plugin Name: CC-MU-Plugins-Loader
	Plugin URI: https://wordpress.org/plugins/cc-mu-plugins-loader
	Description: This plugin automatically loads Must Use Plugins from WPMU_PLUGIN_DIR subdirectories.
	Version: 1.0.0
	Author: Clearcode
	Author URI: https://clearcode.cc
	Text Domain: cc-mu-plugins-loader
	Domain Path: /
	License: GPLv3
	License URI: http://www.gnu.org/licenses/gpl-3.0.txt

	Copyright (C) 2016 by Clearcode <https://clearcode.cc>
	and associates (see AUTHORS.txt file).

	This file is part of CC-MU-Plugins-Loader plugin.

	CC-MU-Plugins-Loader plugin is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	CC-MU-Plugins-Loader plugin is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with CC-MU-Plugins-Loader plugin; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Clearcode\MU_Plugins;

defined( 'ABSPATH' ) or exit;

if ( ! is_blog_installed() ) return;
if ( ! class_exists( __NAMESPACE__ . '\Loader' ) ) {
    class Loader {
        const SEPARATOR = '/';

        public function __construct() {
            add_filter( 'show_advanced_plugins', [ $this, 'show_advanced_plugins' ], 10, 2 );
            add_action( 'muplugins_loaded', [ $this, 'muplugins_loaded' ] );
        }

        public function show_advanced_plugins( $show, $type ) {
            if ( 'mustuse' != $type ) return $show;

            global $plugins;
            $plugins['mustuse'] = $this->get_plugins();

            return false;
        }

        public function muplugins_loaded() {
            $activated = get_option( 'cc-mu-plugins-loader', [] );

            foreach( array_keys( $this->get_plugins() ) as $plugin ) {
                if ( dirname( $plugin ) !== '.' ) {
                    if ( false === array_search( $plugin, $activated ) ) {
                        do_action( 'activate_' . $plugin );
                    }
                    $plugins[] = $plugin;
                    require_once WPMU_PLUGIN_DIR . self::SEPARATOR . $plugin;
                }
            }

            if ( $plugins != $activated )
                update_option( 'cc-mu-plugins-loader', $plugins );
        }

        protected function get_plugins() {
            if ( ! function_exists( 'get_plugins' ) )
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            $path = $this->rel_path( WP_PLUGIN_DIR, WPMU_PLUGIN_DIR );
            return get_plugins( self::SEPARATOR . $path );
        }

        protected function rel_path( $from, $to ) {
            $from = explode( self::SEPARATOR, $this->normalize( $from ) );
            $to   = explode( self::SEPARATOR, $this->normalize( $to ) );

            while( count( $from ) && count( $to ) && ( $from[0] == $to[0] ) ) {
                array_shift( $from );
                array_shift( $to );
            }

            return str_pad( '', count( $from ) * 3, '..' . self::SEPARATOR ) . implode( self::SEPARATOR, $to );
        }

        protected function normalize( $path ) {
            return rtrim( str_replace( '\\',self::SEPARATOR, $path ), self::SEPARATOR);
        }
    }
}

new Loader();
