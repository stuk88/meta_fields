<?php
 /**
  * Plugin Name: Custom Meta Boxes
  * Author:      Stas Arshanski
  * Author URI:  http://webbo.co.il/
  * Version:     1.5
  * License:     GPLv2 or later
  * License URI: http://www.gnu.org/licenses/gpl-2.0.html
  */


$prefix = 'webbo_';

$meta_box = array(
	'id' => 'my-meta-box',
	'title' => 'אפשרויות',
	'post_type' => array('estate'),
	'context' => 'normal',
	'priority' => 'high',
	'fields' => array(
		array(
			'id' => $prefix.'price',
			'name' => "מחיר",
			'desc' => '',
			'type' => 'text',
			'std'  => ''
		),
		array(
			'id' => $prefix.'id',
			'name' => 'מספר נכס',
			'desc' => '',
			'type' => 'text',
			'std'  => ''
			
		),
		array(
			'id' => $prefix.'field_size',
			'name' => 'גודל מגרש',
			'desc' => '',
			'type' => 'text',
			'std'  => ''
			
		),
		array(
			'id' => $prefix.'built_size',
			'name' => 'גודל בנוי',
			'desc' => '',
			'type' => 'text',
			'std'  => ''
			
		),
		array(
			'id' => $prefix.'rooms',
			'name' => "מס' חדרים",
			'desc' => '',
			'type' => 'text',
			'std'  => ''
		),			
		array(
			'id' => $prefix.'sold',
			'name' => "האם נמכר? ",
			'desc' => '',
			'type' => 'checkbox',
			'std'  => ''
			
		)
	)
);

add_action('admin_menu', 'webbo_add_box');

// Add meta box
function webbo_add_box() {
	global $meta_box;
	while(count($meta_box['post_type']) > 0) {
		$post_type = array_pop($meta_box['post_type']);
		add_meta_box($meta_box['id'], $meta_box['title'], 'webbo_show_box', $post_type, $meta_box['context'], $meta_box['priority']);
	}	
}

// Callback function to show fields in meta box
function webbo_show_box() {
	global $meta_box, $post;

	if($post->post_type == "page") {
		$template = get_post_meta($post->ID, '_wp_page_template', true);
	}
	
	// Use nonce for verification
	echo '<input type="hidden" name="webbo_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
	
	echo '<table class="form-table">';


	foreach ($meta_box['fields'] as $field) {

		if(fieldRestictedByPostType($field, $post) )
		{
			continue;
		}

		if(isset($template) && fieldRestrictedByPageTemplate($field, $post, $template)) {
			continue;
		}

		// get current post meta data
		$meta = get_post_meta($post->ID, $field['id'], true);
		
		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', $field['name'], '</label></th>',
				'<td>';
		switch ($field['type']) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" />',
					'<br />', $field['desc'];
				break;
			case 'textarea':
				echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="4" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>',
					'<br />', $field['desc'];
				break;
			case 'select':
				echo '<select name="', $field['id'], '" id="', $field['id'], '">';
				foreach ($field['options'] as $option) {
					echo '<option', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
				}
				echo '</select>';
				break;
			case 'radio':
				foreach ($field['options'] as $option) {
					echo '<input type="radio" name="', $field['id'], '" value="', $option['value'], '"', ($meta == $option['value'] || ($meta == "" && $option['default']))? ' checked="checked"' : '', ' />', $option['name'];
				}
				break;
			case 'checkbox':
				echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
				break;
		}
		echo 	'<td>',
			'</tr>';
	}
	
	echo '</table>';
}

add_action('save_post', 'webbo_save_data');

// Save data from meta box
function webbo_save_data($post_id) {
	global $meta_box;
	
	if(!isset($_POST['webbo_meta_box_nonce']))
		return;
	
	// verify nonce
	if (!wp_verify_nonce($_POST['webbo_meta_box_nonce'], basename(__FILE__))) {
		return $post_id;
	}

	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}

	// check permissions
	if ('page' == $_POST['post_type']) {
		if (!current_user_can('edit_page', $post_id)) {
			return $post_id;
		}
	} elseif (!current_user_can('edit_post', $post_id)) {
		return $post_id;
	}
	
	foreach ($meta_box['fields'] as $field) {
		$old = get_post_meta($post_id, $field['id'], true);
		$new = $_POST[$field['id']];
		
		if (isset($new) && $new != $old) {
			update_post_meta($post_id, $field['id'], $new);
		} elseif ('' == $new && $old) {
			delete_post_meta($post_id, $field['id'], $old);
		}
	}
}

function fieldRestrictedByPageTemplate($field, $post, $template) {
	if(isset($field['page_template_limits'])) {
		return false;
	}
	return ($post->post_type == "page" && $field['page_limits'] != $template);
}

function fieldRestictedByPostType($field, $post) {
	return (isset($field['type_limit']) && $field['type_limit'] != $post->post_type);
}