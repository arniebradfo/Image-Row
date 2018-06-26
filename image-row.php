<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           img_row
 *
 * @wordpress-plugin
 * Plugin Name:       Image Row
 * Plugin URI:        http://example.com/img-row-uri/
 * Description:       Align images in rows.
 * Version:           1.0.0
 * Author:            James Bradford
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       img-row
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function var_dump_pre($mixed = null) {
	echo '<pre>';
	var_dump($mixed);
	echo '</pre>';
	return null;
  }

class Img_row {

	public function __construct() {
		add_shortcode( 'imgrow', array(&$this, 'img_row_shortcode') );
		add_filter('image_send_to_editor', 'picfill_insert_image', 10, 9);
	}

	public function img_row_shortcode( $atts, $content=null, $tag='' ) {

		// extract turns the array['vars'] into individual $vars
		extract( shortcode_atts( array( 
			'ids' => '',
			'spacing' => '1'
		), $atts , 'imgrow' ));

		unset($atts['ids']);
		$ids = explode(',',$ids);

		unset($atts['spacing']);
		$spacing = floatval($spacing);

		// get img
		// get height and width
		// wp_get_attachment_image($id)
		// wp_get_attachment_image_src($id)
		// wp_get_attachment_image_srcset($id)
		// wp_get_attachment_metadata($id)

		// add style option
		// wp_register_style( 'dummy-handle', false );
		// wp_enqueue_style( 'dummy-handle' );
		// wp_add_inline_style( 'dummy-handle', '* { color: red; }' );

		$atts['class'] = isset($atts['class']) ? $atts['class'].' img-row' : 'img-row';

		$output = '<div '; 
		foreach ($atts as $att => $val) {
			$output .= $att.'="'.$val.'" ';
		}
		$output .= '>';

		$imgs = [];
		$ratioSum = $spacing;
		foreach ($ids as $i => $id) {

			$attachment_metadata = wp_get_attachment_metadata($id);
			$ratio = $attachment_metadata['width'] / $attachment_metadata['height'];
			$ratioSum += $ratio;

			$imgs[$id] = [
				'ratio' => $ratio,
				'atts' => [
					'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
					'src' => wp_get_attachment_image_url($id, 'full'), // wp_get_attachment_image_src($id, 'full')['url'],
					'class' => 'img-row__img img-row__img--id-'.$id,
					'height' => $attachment_metadata['height'],
					'width' => $attachment_metadata['width'],
					'srcset' => wp_get_attachment_image_srcset($id),
					'sizes' => '', // TODO
				]
			];
		}

		$style = <<<CSS
			.img-row{
				display: flex;
				flex-wrap: wrap;
				justify-content: space-between;
				align-items: stretch;
			}
			.img-row__img{
				height: 100%;
			}
CSS;

		foreach ($imgs as $id => $img) {
			$style .= '.img-row__img--id-'.$id.'{ width:' .( $img['ratio'] / $ratioSum * 100 ). '%;}';
			$output .= '<img '; 
			$output .= 'style="'.''.'"';
			foreach ($img['atts'] as $att => $val) {
				$output .= $att.'="'.$val.'" ';
			}
			$output .= '/>';
		}
		$output .= '<style>' . $style . '</style>';
		
		// $output .= do_shortcode($content);

		$output .= '</div>';

		return $output;

	}

	// altering media uploader output into the post editor - outputs shortcode instead of image
	private function picfill_insert_image($html, $id, $caption, $title, $align, $url) {
    	return "[picfill imageid='$id' sizeXS='0' sizeS='250' sizeM='500' sizeL='750' sizeXL='1000' size2XL='1500' size3XL='2000' size4XL='3000' ]";
	}

}

// run it
new Img_row();
