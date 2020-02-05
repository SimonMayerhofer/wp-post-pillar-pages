<?php
/**
 * Plugin Name: Post Pillar Pages
 * Description: Use posts as pillar pages and create a Custom Post Type for every pillar page.
 * Version:     1.0.1
 * Author:      Simon Mayerhofer
 * Author URI:  https://mayerhofer.it
 * Plugin URI:  https://github.com/SimonMayerhofer/wp-post-pillar-pages/
 * License:     GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pillar-pages
 * Domain Path: /languages
 *
 * Post Pillar Pages is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Post Pillar Pages is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Post Pillar Pages. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 *
 * @package pillar-pages
 */

namespace PillarPages;

require_once 'settings-page.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PillarPages plugin class.
 */
final class PillarPages {
	/**
	 * The single instance of the class.
	 *
	 * @var PillarPages
	 */
	protected static $instance = null;

	/**
	 * PillarPages Constructor.
	 */
	private function __construct() {
		$this->init_hooks();

		if ( is_admin() ) {
			$settings_page = new SettingsPage();
		}
	}

	/**
	 * Main PillarPages Instance.
	 *
	 * Ensures only one instance of PillarPages is loaded or can be loaded.
	 *
	 * @static
	 * @return PillarPages - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get all pillar pages.
	 */
	private function get_pillar_pages() {
		$post_ids = explode( ',', get_option( 'pillar_pages' )['post_ids'] );
		$pillar_pages = [];

		foreach ( $post_ids as $id ) {
			// skip if post with id not exists.
			if ( false === get_post_status( $id ) ) {
				continue;
			}

			$pillar_pages[] = get_post( $id );
		}

		return $pillar_pages;
	}

	/**
	 * Initializes all custom post types.
	 */
	public function init_custom_post_types() {
		$pillar_pages = $this->get_pillar_pages();

		foreach ( $pillar_pages as $page ) {
			/*
			 * we need to use the combination of pillar-page- + ID because WordPress
			 * only allows post-type-names with a length of 20.
			 */
			register_post_type( 'pillar-page-' . $page->ID,
				[
					'labels'       => [
						'name' => $page->post_title,
					],
					'public'       => true,
					'has_archive'  => false,
					'supports'     => [
						'title',
						'editor',
						'author',
						'thumbnail',
						'excerpt',
						'trackbacks',
						'custom-fields',
						'revisions',
						'post-formats',
						'comments',
					],
					'taxonomies'   => [
						'category',
						'post_tag',
					],
					'show_in_rest' => true,
					'rewrite'      => [
						'slug' => $page->post_name,
					],
				]
			);
		}
	}

	/**
	 * Add all custom post types to the main query.
	 *
	 * @param  WP_Query $query the query to customize.
	 */
	public function add_to_main_query( $query ) {
		$pillar_pages = $this->get_pillar_pages();
		$slugs = [];

		foreach ( $pillar_pages as $page ) {
			$slugs[] = 'pillar-page-' . $page->ID;
		}

		if ( $query->is_home() && $query->is_main_query() ) {
			$post_type = $query->get( 'post_type', 'post' );

			if ( is_string( $post_type ) ) {
				$post_type = [$post_type];
			}

			$query->set( 'post_type', array_merge( $post_type , $slugs ) );
		}
	}

	/**
	 * Flushes rewrite rules on activation.
	 *
	 * This functions should only be called during plugin activation!!
	 */
	public function flush_rewrite_rules() {
		$this->init_custom_post_types();
		flush_rewrite_rules();
	}

	/**
	 * Initializes all hooks
	 */
	public function init_hooks() {
		// Flushes rewrite on activation.
		register_activation_hook( __FILE__, [ $this, 'flush_rewrite_rules' ] );

		// Initializes all custom post types.
		add_action( 'init', [ $this, 'init_custom_post_types' ] );

		// Add CPT to main query.
		add_filter( 'pre_get_posts', [ $this, 'add_to_main_query' ] );
	}
}

PillarPages::get_instance();
