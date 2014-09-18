<?php
/**
 * Plugin Name: WPThumb Bridge
 * Description: Provide backwards compat for WP Thumb
 * Author: Human Made Limited
 */

/**
 * Resizes a given image (local).
 *
 * Compatibility function
 *
 * @param mixed absolute path to the image
 * @param array $args {
 *     @type string $width
 *     @type string $height
 *     @type boolean $crop
 * }
 * @return string url to the image
 */
function wpthumb( $url, $args = array() ) {

	$args = wpthumb_parse_args( $args );

	$is_local = ( strpos( $url, DOMAIN_CURRENT_SITE ) !== false );

	/**
	 * In cases where $url is a path inside the content dir, we can convert it to a URL
	 * so we can use the thumbnail from HT_S3_Switcher() which is preferred as it will
	 * use imgix instead of WP Thumb. WP Thumb older and requires writing the cache file
	 * to S3 and an entry in the database.
	 */
	if ( ! $is_local && strpos( $url, WP_CONTENT_DIR ) === 0 ) {
		$url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $url );
		$is_local = false;
	}

	if ( strpos( $url, 's3://' ) === 0 ) {
		$upload_dir = wp_upload_dir();
		$url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $url );

		// if it's still a path, just convert it to a URL
		if ( strpos( $url, 's3://' ) === 0 ) {
			$url = preg_replace( '#s3://([^/]+)/(.+)#', 'https://$1.s3.amazonaws.com/$2', $url );
		}
		$is_local = false;
	}

	if ( class_exists( 'WP_Imgix' ) && ! $is_local )
		return WP_Imgix::get_instance()->get_thumbnail_url( $url, $args );
	else
		return $url;
}

function wpthumb_parse_args( $args ) {
	// check if $args is a WP Thumb argument list, or native WordPress one
	// wp thumb looks like this: 'width=300&height=120&crop=1'
	// native looks like 'thumbnail'
	if ( is_string( $args ) && ! strpos( (string) $args, '=' ) ) {

		// if there are no "special" wpthumb args, then we shouldn' bother creating a WP Thumb, just use the WordPress one
		if ( $args === ( $args = apply_filters( 'wpthumb_create_args_from_size', $args ) ) )
			return $null;
	} else if ( is_int( $args ) ) {
		$args = array( 'width' => $args, 'height' => $args );
	}

	$args = wp_parse_args( $args );

	if ( ! empty( $args[0] ) )
		$args['width'] = $args[0];

	if ( ! empty( $args[1] ) )
		$args['height'] = $args[1];

	if ( ! empty( $args['crop'] ) && $args['crop'] && empty( $args['crop_from_position'] ) )
		 $args['crop_from_position'] = get_post_meta( $id, 'wpthumb_crop_pos', true );

	return $args;
}

// declare the class so other stuff doens't load WP
class WP_Thumb {

}
