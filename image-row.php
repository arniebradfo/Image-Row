<?php

/**
 * Image Row
 *
 * @link              https://codepen.io/arniebradfo/pen/JmrWPY
 * @since             1.1.0
 * @package           img_row
 *
 * @wordpress-plugin
 * Plugin Name:       Image Row
 * Plugin URI:        https://github.com/arniebradfo/Image-Row
 * GitHub Plugin URI: https://github.com/arniebradfo/Image-Row
 * GitHub Branch:     master
 * Description:       Align images in rows.
 * Version:           1.1.0
 * Author:            James Bradford
 * Author URI:        http://bradford.digital/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       img-row
 * Domain Path:       /languages
 */

 /**
  * TODO:
  * - replace and re-implement gallery shortcode
  * - media query options
  * - break options?
  * - global options: spacing, content_width, override gallery
  * - gutenberg?
  * - release
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
		remove_shortcode('gallery');
		add_shortcode( 'gallery', array(&$this, 'img_row_shortcode') );
		add_shortcode( 'imgrow',  array(&$this, 'img_row_shortcode') );
	}

	public function img_row_shortcode( $atts=[], $content=null, $tag='' ) {
		
		// set the global default
		// TODO: These should come from a wp option somewhere...
		if (!isset($GLOBALS['img_row_spacing']))
			$GLOBALS['img_row_spacing'] = '0.5rem';
		if (!isset($GLOBALS['img_row_tag']))
			$GLOBALS['img_row_tag'] = 'p';

		if (is_string($atts)) $atts = [];

		// extract turns the array['vars'] into individual $vars
		extract( shortcode_atts( array( 
			'spacing' => $GLOBALS['img_row_spacing'], // TODO: default should come from a wp option...
			'tag' => $GLOBALS['img_row_tag'], // how to handle this?
			// 'media' => '???', // TODO: @media query - how to handle this?

			// from [gallery/] shortcode // TODO: hook these up
			'ids' => '', // list of img ids
            // 'link' => 'file' // 'file' | 'link' | <empty string> (for linking to attachment page)
            // 'columns' => '3', // [1-9] as string
            // 'size' => 'full'
            // 'orderby' => 'post__in', // 'post__in' | 'rand'
		), $atts , 'imgrow' ));

		// if (strlen($ids) < 1) $ids = '1,2,3';
		
		$ids = explode(',',$ids);
		
		// What does this do?
		$GLOBALS['img_row_spacing'] = $spacing;
		$spacing_val = floatval($spacing);
		$spacing_unit = preg_replace('/[\d.]+/u', '', $spacing);

		if (!wp_style_is('img-row-style')){
			$style = <<<CSS
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

			// gallery 
			for ($i=2; $i < 9; $i++) {
				// do we need more than 9? 
				// [gallery columns="9"] only goes to nine
				$style .= "\r\n.img-row--$i-item";
				$padding = $spacing_val*($i-1) . $spacing_unit;
				$style .= "{ padding-right: $padding; }";
			}
			wp_register_style(   'img-row-style', false );
			wp_enqueue_style(    'img-row-style' );
			wp_add_inline_style( 'img-row-style', $style );

		}

		$count_class = 'img-row--'.count($ids).'-item';
		$atts['class'] = isset($atts['class']) ? "{$atts['class']} img-row" : 'img-row';
		$atts['class'] .= " $count_class";

		// unset $atts we don't want on our base tag
		unset(
			$atts['ids'], 
			$atts['spacing'],
			$atts['tag']
		);

		// generate base tag with any extra atts
		$output = "<$tag "; 
		foreach ($atts as $att => $val) {
			$output .= "$att=\"$val\"";
		}
		$output .= '>';

		$imgs = [];
		$ratioSum = 0;
		foreach ($ids as $i => $id) {

			if (!wp_get_attachment_url($id)) continue;

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
					// 'sizes' => '', // TODO?
				]
			];
		}

		foreach ($imgs as $id => $img) {
			$width = ( $img['ratio'] / $ratioSum * 100 );
			$output .= '<img'; 
			$output .= " style=\"width:$width%;\"";
			foreach ($img['atts'] as $att => $val) {
				$output .= " $att=\"$val\"";
			}
			$output .= " sizes=\"{$width}vw\"";
			// TODO: better sizes="" attribute
			// add a content-width option
			// $output .= "sizes=\"(min-width: {$content_width}px) ".($content_width / count($ids))."px, {$width}vw\"";
			// https://wycks.wordpress.com/2013/02/14/why-the-content_width-wordpress-global-kinda-sucks/
			// https://bitsofco.de/the-srcset-and-sizes-attributes/
			// content width doesn't come from here...
			$output .= '/>';
		}

		$output .= "</$tag>";

		return $output;

	}

}

// run it!
new Img_row();
