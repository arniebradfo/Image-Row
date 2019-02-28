# Image Row Wordpress Plugin
Puts images into rows.

## Description
Replaces the `[gallery]` shortcode with a row of images.

### Attributes
- _ids_ : An array of media ids.
- _spacing_ : Css unit spacing: Default is `0.5em`. Percentage values don't really work.
- _tag_ : What tag is it wrapped in? Default is `p` for a `<p></p>` tag wrapper.
- Both _spacing_ & _tag_ will cascade to all `[gallery]` tags under them.
- _maxwidth_ : The max width of the row. Default is the $content_width global. 0 is infinity
- _contentclass_ : Classes to be added to the images in the row.
- _media_ : In the future, media query wrapping.
- All the gallery attributes, in the future.

## Release Process
- 

## TODO:
- replace and re-implement gallery shortcode
- media query options
- break options?
- global options: spacing, content_width, override gallery
- gutenberg?
- release
- percentage doesn't work on spacing because the sizes aren't calculated from the same reference
