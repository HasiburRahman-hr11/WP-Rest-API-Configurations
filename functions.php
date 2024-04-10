<?php
// ----------------- CUSTOM ENDPOINT FOR REST API -------------------------
add_action('rest_api_init', 'register_blog_rest_route');

function register_blog_rest_route()
{
	register_rest_route(
		'custom/v2',
		'/blogs',
		array(
			'methods' => 'GET',
			'callback' => 'get_blogs',
		)
	);
}

function get_blogs()
{
	$blogs = array();
	$args = array(
		'post_type' => 'blogs',
		'nopaging' => true,
	);
	$query = new WP_Query($args);
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$blog_data = array(
				'id' => get_the_ID(),
				'slug' => get_post_field('post_name', get_post()),
				'title' => get_the_title(),
				'excerpt' => get_the_excerpt(), // Get post excerpt
				'content' => get_the_content(),
				'featured_image' => get_the_post_thumbnail_url(get_the_ID(), 'full'), // Get featured image URL
				'acf_fields' => get_fields(get_the_ID()), // Get ACF fields
			);
			$blogs[] = $blog_data;
		}
		wp_reset_postdata();
	}
	return rest_ensure_response($blogs);
}

// -------------- SHow Featured Image In REST API --------
add_action('rest_api_init', 'register_rest_images');
function register_rest_images()
{
	register_rest_field(
		array('post'),
		'fimg_url',
		array(
			'get_callback'    => 'get_rest_featured_image',
			'update_callback' => null,
			'schema'          => null,
		)
	);
}
function get_rest_featured_image($object, $field_name, $request)
{
	if ($object['featured_media']) {
		$img = wp_get_attachment_image_src($object['featured_media'], 'app-thumb');
		return $img[0];
	}
	return false;
}

// -------------- SHow ACF FIELDS In REST API --------
function create_ACF_meta_in_REST() {
    $postypes_to_exclude = ['acf-field-group','acf-field'];
    $extra_postypes_to_include = ["page", "posts", "blogs"]; // Add post types
    $post_types = array_diff(get_post_types(["_builtin" => false], 'names'),$postypes_to_exclude);

    array_push($post_types, $extra_postypes_to_include);

    foreach ($post_types as $post_type) {
        register_rest_field( $post_type, 'ACF', [
            'get_callback'    => 'expose_ACF_fields',
            'schema'          => null,
       ]
     );
    }

}

function expose_ACF_fields( $object ) {
    $ID = $object['id'];
    return get_fields($ID);
}

add_action( 'rest_api_init', 'create_ACF_meta_in_REST' );