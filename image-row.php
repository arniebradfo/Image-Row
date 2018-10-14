<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://codepen.io/arniebradfo/pen/JmrWPY
 * @since             1.0.0
 * @package           img_row
 *
 * @wordpress-plugin
 * Plugin Name:       Image Row
 * Plugin URI:        http://example.com/img-row-uri/
 * Description:       Align images in rows.
 * Version:           1.0.0
 * Author:            James Bradford
 * Author URI:        http://bradford.digital/
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
	}

	public function img_row_shortcode( $atts, $content=null, $tag='' ) {
		
		// set the global default
		// TODO: this should come from a wp option somewhere...
		if (!isset($GLOBALS['img_row_spacing'])){
			$GLOBALS['img_row_spacing'] = '1rem';
		}

		// extract turns the array['vars'] into individual $vars
		extract( shortcode_atts( array( 
			'ids' => '',
			'spacing' => $GLOBALS['img_row_spacing'], // TODO: default  should come from a wp option...
		), $atts , 'imgrow' ));

		unset($atts['ids']);
		$ids = explode(',',$ids);

		unset($atts['spacing']);
		$GLOBALS['img_row_spacing'] = $spacing;
		$spacing_val = intval($spacing);
		$spacing_unit = preg_replace('/[\d.]+/u', '', $spacing);

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

		$count_class = 'img-row--'.count($ids).'-item';
		$atts['class'] = isset($atts['class']) ? "{$atts['class']} img-row" : 'img-row';
		$atts['class'] .= ' '.$count_class;

		$output = '<div '; 
		foreach ($atts as $att => $val) {
			$output .= $att.'="'.$val.'" ';
		}
		$output .= '>';

		$imgs = [];
		$ratioSum = 0;
		foreach ($ids as $i => $id) {

			$attachment_metadata = wp_get_attachment_metadata($id);
			$ratio = $attachment_metadata['width'] / $attachment_metadata['height'];
			$ratioSum += $ratio;

			$imgs[$id] = [
				'ratio' => $ratio,
				'atts' => [
					'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
					'src' => wp_get_attachment_image_url($id, 'full'), // wp_get_attachment_image_src($id, 'full')['url'],
					'class' => "img-row__img img-row__img--id-$id",
					'height' => $attachment_metadata['height'],
					'width' => $attachment_metadata['width'],
					'srcset' => wp_get_attachment_image_srcset($id),
					'sizes' => '', // TODO
				]
			];
		}

		if (!wp_style_is('img-row-css')){
			$globalStyle = <<<CSS
.img-row{
	display: flex;
	width: 100%;
}
.img-row__img{
	height: 100%;
	flex: 0 0 auto;
	margin-right: $spacing ;
	/* margin-bottom: $spacing ; */
}
CSS;
			for ($i=2; $i < 12; $i++) { 
				$globalStyle .= "\r\n.img-row--$i-item";
				$padding = $spacing_val*($i-1) . $spacing_unit;
				$globalStyle .= "{ padding-right: $padding; test:here; }";
			}
			wp_register_style(   'img-row-css', false );
			wp_enqueue_style(    'img-row-css' );
			wp_add_inline_style( 'img-row-css', $globalStyle );

		}


		foreach ($imgs as $id => $img) {
			$width = ( $img['ratio'] / $ratioSum * 100 );
			$output .= '<img '; 
			$output .= "style=\"width:$width%;\"";
			foreach ($img['atts'] as $att => $val) {
				$output .= $att.'="'.$val.'" ';
			}
			$output .= '/>';
		}

		$output .= '</div>';

		return $output;

	}

}

// run it
new Img_row();
