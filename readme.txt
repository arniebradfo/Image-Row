=== Image Row ===
Contributors: arniebradfo
Donate link: https://www.paypal.me/arniebradfo
Tags: image, shortcode, row, rows, gallery
Requires at least: 4.0
Tested up to: 5.1
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Puts images into rows.

== Description ==

Replaces the [gallery] shortcode with a row of images.

= Attributes =

- _ids_ = An array of media ids.
- _spacing_ = Css unit spacing: Default is `0.5em`. Percentage values don't really work.
- _tag_ = What tag is it wrapped in? Default is `p` for a `<p></p>` tag wrapper.
- Both _spacing_ & _tag_ will cascade to all [gallery] tags under them.
- _contentwidth_ = The max width of the row. Default is the $content_width global. 0 is infinity
- _media_ = In the future, media query wrapping.
- All the gallery attributes, in the future.
