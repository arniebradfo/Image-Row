<?php

/**
 * Image Row
 *
 * @link              https://codepen.io/arniebradfo/pen/JmrWPY
 * @since             1.0.0
 * @package           img_row
 *
 * @wordpress-plugin
 * Plugin Name:       Image Row
 * Plugin URI:        https://github.com/arniebradfo/Image-Row
 * GitHub Plugin URI: https://github.com/arniebradfo/Image-Row
 * GitHub Branch:     master
 * Description:       Align images in rows.
 * Version:           1.2.3
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

// function var_dump_pre($mixed = null) { // for debug
// 	echo '<pre>';
// 	var_dump($mixed);
// 	echo '</pre>';
// 	return null;
// }

class Img_row {

	public function __construct() {
		remove_shortcode('gallery');
		add_shortcode( 'gallery', array(&$this, 'img_row_shortcode') );
		add_shortcode( 'imgrow',  array(&$this, 'img_row_shortcode') );

		add_action('wp_enqueue_scripts', array(&$this, 'imgrow_enqueue_scripts' ));
	
	}

	public function imgrow_enqueue_scripts () {
		wp_enqueue_style( 'img-row-style', plugin_dir_url(__FILE__).'image-row.css' );
	}
	
	public function find_svg_dimensions( $svgUrl ) {

		$svgString = file_get_contents($svgUrl);
		$svgElement = simplexml_load_string($svgString);

		// do a return check here to see if height and width even exist
		// if (check) return false;

		$height = intval($svgElement['height']);
		$width = intval($svgElement['width']);

		if ( ! isset($height) || ! isset($width) ) {
			$viewBox = 	explode(' ', $svgElement['viewBox']);
			
			// check if $viewBox is set
			if ( ! isset($viewBox) ) return false;

			// https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/viewBox
			$minX = intval($viewBox[0]);
			$minY = intval($viewBox[1]);
			$width = intval($viewBox[2]);
			$height = intval($viewBox[3]);

			$width = $width - $minX;
			$height = $height - $minY;
		}

		return array(
			"height" => $height,
			"width"  => $width
		);
	}

	public function img_row_shortcode( $atts=[], $content=null, $tag='' ) {
		
		// set the global default
		// TODO: These should come from a wp option somewhere...
		// TODO: percentage doesn't work on spacing because the sizes aren't calculated from the same reference
		if (empty($GLOBALS['img_row_spacing']))
			$GLOBALS['img_row_spacing'] = '0.5rem';
		if (empty($GLOBALS['img_row_tag']))
			$GLOBALS['img_row_tag'] = 'p';
		
		// https://wycks.wordpress.com/2013/02/14/why-the-content_width-wordpress-global-kinda-sucks/
		global $content_width; // we need this for the img sizes attribute

		if (is_string($atts)) $atts = [];

		// extract turns the array['vars'] into individual $vars
		extract( shortcode_atts( array( 
			'spacing' => $GLOBALS['img_row_spacing'], // TODO: default should come from a wp option...
			'tag' => $GLOBALS['img_row_tag'], // how to handle this?
			'maxwidth' => $content_width,
			'contentclass' => '',
			// 'media' => '???', // TODO: @media query - how to handle this?

			// from [gallery/] shortcode // TODO: hook these up
			'ids' => '',                    // list of img ids //TODO:  what happens if this is empty?
            // 'link' => 'file'             // 'file' | 'link' | <empty string> (for linking to attachment page)
            // 'columns' => '3',            // [1-9] as string
            // 'size' => 'full'
            // 'orderby' => 'post__in',     // 'post__in' | 'rand'
		), $atts , 'imgrow' ));
		
		$ids = explode(',',$ids);

		// What does this do?
		$GLOBALS['img_row_spacing'] = $spacing;
		$GLOBALS['img_row_tag'] = $tag;
		$spacing_val = floatval($spacing);
		$spacing_unit = preg_replace('/[\d.]+/u', '', $spacing);

		if (!wp_style_is('img-row-inline-style')){
			$style = ".img-row__img{ margin-right: $spacing ; /* margin-bottom: $spacing ; */ }";
			
			// gallery 
			for ($i=2; $i < 9; $i++) {
				// do we need more than 9? 
				// [gallery columns="9"] only goes to nine
				$style .= "\r\n.img-row--$i-item";
				$padding = $spacing_val*($i-1) . $spacing_unit;
				$style .= "{ padding-right: $padding; }";
			}
			wp_register_style(   'img-row-inline-style', false );
			wp_enqueue_style(    'img-row-inline-style' );
			wp_add_inline_style( 'img-row-inline-style', $style );

		}

		$count_class = 'img-row--'.count($ids).'-item';
		$atts['class'] = isset($atts['class']) ? "{$atts['class']} img-row" : 'img-row';
		$atts['class'] .= " $count_class";

		// unset $atts we don't want on our base tag
		unset(
			$atts['ids'], 
			$atts['spacing'],
			$atts['tag'],
			$atts['maxwidth'],
			$atts['contentclass']
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

			if ( strpos( get_post_mime_type($id), 'svg' ) !== false ) {
				$svgHeightAndWidth = $this->find_svg_dimensions(wp_get_attachment_url($id));
				$height = $svgHeightAndWidth['height'];
				$width = $svgHeightAndWidth['width'];
			} else {
				$height = $attachment_metadata['height'];
				$width = $attachment_metadata['width'];
			}

			$ratio = $width / $height;
			$ratioSum += $ratio;

			$imgs[$id] = [
				'ratio' => $ratio,
				'atts' => [
					'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
					'src' => wp_get_attachment_image_url($id, 'full'), // wp_get_attachment_image_src($id, 'full')['url'],
					'class' => "$contentclass img-row__img img-row__img--id-$id",
					'height' => $height,
					'width' => $width,
					'srcset' => wp_get_attachment_image_srcset($id),
				]
			];
		}

		foreach ($imgs as $id => $img) {
			$width_percent = ( $img['ratio'] / $ratioSum * 100 );
			$width_pixels = $width_percent/100 * $maxwidth; 

			$img['atts']['style'] = "width:$width_percent%;";
			
			// https://bitsofco.de/the-srcset-and-sizes-attributes/
			$img['atts']['sizes'] = ($maxwidth < 1) ? 
				"{$width_percent}vw" :
				"(min-width: {$maxwidth}px) {$width_pixels}px, {$width_percent}vw" ;

			$output .= '<img'; 
			foreach ($img['atts'] as $att => $val) {
				$output .= " $att=\"$val\"";
			}
			$output .= '/>';
		}

		$output .= "</$tag>";

		return $output;

	}

}

// run it!
new Img_row();
