<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class Medlink {
	private $fields;
	private $image_key;

	function __construct() {
		$this->image_key = 'page_hero_image';
		
		$this->fields = array(
			'page_call_to_action' => array(
				'key' => 'page_call_to_action',
				'label' => 'Call to action',
				'name' => 'page_call_to_action',
				'type' => 'text',
				'required' => 1,
				'wrapper' => array(
					'width' => '',
					'class' => 'medlink-metadata',
					'id'    => 'page_call_to_action',
				),
				'placeholder'       => 'Call to action text',
				'maxlength'         => '100',
				'instructions' => 'up to 100 symbols',
			),
			'page_intro_text' => array(
				'key' => 'page_intro_text',
				'label' => 'Intro',
				'name' => 'page_intro_text',
				'type' => 'textarea',
				'required' => 1,
				'wrapper' => array(
					'width' => '',
					'class' => 'medlink-metadata',
					'id'    => 'page_intro_text',
				),
				'maxlength' => '300',
				'instructions' => 'up to 300 symbols',
			),
			$this->image_key => array(
				'key' => $this->image_key,
				'label' => 'Hero image',
				'name' => $this->image_key,
				'type' => 'image',
				'required' => 1,
				'wrapper' => array(
					'width' => '',
					'class' => 'medlink-metadata',
					'id'    => $this->image_key,
				),
				'return_format' => 'ID',
				'min_size' => 0.1,
				'max_size' => 5,
				'mime_types' => 'jpg,jpeg,png,webp',
				'instructions' => '0.1-2Mb; jpg, png, or webp formats allowed',
			),
		);		
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'acf/init', array( $this, 'acf_fields_init' ) );

		add_action( 'wp_ajax_save_form_data', array( $this, 'save_form_data' ) );
// 		add_action( 'wp_ajax_nopriv_save_form_data', array( $this, 'save_form_data' ) );
	}

	private function get_image_key() {
		return $this->image_key;
	}

	private function get_fields() {
		return $this->fields;
	}
	
	private function get_field( $key ) {
		return empty( $value = $this->fields[$key] ) ? false : $value;
	}
	
	private function render_image( $id ) {
		return wp_get_attachment_image( intval( $id ), 'large', false, array( 'class' => 'medlink-hero-image' ) );
	}

	function enqueue_scripts() {
		wp_enqueue_style( 'medlink-css', plugin_dir_url( __FILE__ ) . 'public.css' );

		wp_enqueue_script( 'medlink-js', plugin_dir_url( __FILE__ ) . 'public.js', array( 'jquery' ) );

		$image_options = $this->get_field( $this->get_image_key() );
		wp_localize_script( 'medlink-js', 'mlHelper',
			array( 
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'heroImage' => array(
					'mimeTypes' => explode( ',', $image_options['mime_types']),
					'minSize' => $image_options['min_size'] * 1000000,
					'maxSize' => $image_options['max_size'] * 1000000,
				),
				'mlSecurity' => wp_create_nonce( 'ml_nonce' ),
				'userID' => get_current_user_id(),
			)
		);
	}
	
	function acf_fields_init() {
		acf_add_local_field_group( array(
			'key'    => 'medlink',
			'title'  => 'Medlink',
			'fields' => array_values( $this->get_fields() ),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
				),
			),
		) );
	}

	function save_form_data() {
		check_ajax_referer( 'ml_nonce', 'nonce' );
		
		if ( empty( $user_id = $_REQUEST['user_id'] ) || intval( $user_id ) == 0 ) {
			echo false;
			wp_die();
		}		
		
		$post_id = url_to_postid( wp_get_referer() ); 		
		$acf_raw_data = $_REQUEST['acf'];
		$results = array();
		foreach ( $this->get_fields() as $key => $field_info ) {
			if ( empty( $raw_value = $acf_raw_data[$key] ) ) continue;

			$value = substr( sanitize_text_field( $raw_value ), 0, $field_info['maxlength'] );
			update_field( $key, $value, $post_id );
			$results[$key] = $value;
		}
		
		$img_key = $this->get_image_key();
		$img_data = $_FILES['acf'];

		$file = array(
			'name' => sanitize_file_name( $img_data['name'][$img_key] ),
			'type' => $img_data['type'][$img_key],
			'tmp_name' => $img_data['tmp_name'][$img_key],
			'error' => $img_data['error'][$img_key],
			'size' => $img_data['size'][$img_key],
		);

		if ( !$file['error'] || $file['error'] && empty( $_REQUEST['img_thumbnail'] ) ) {
			if ( empty( $file['name'] ) ) {
				echo false;
				wp_die();
			}

			$img_type = str_replace( 'image/', '', $file['type'] );
			$image_options = $this->get_field( $this->get_image_key() );
			if ( !in_array( $img_type, explode( ',', $image_options['mime_types'] ) ) ) {
				echo false;
				wp_die();
			}

			$img_size = floatval( $file['size'] ) / 1000000;
			if ( $img_size == 0 
				|| $img_size < $image_options['min_size'] 
				|| $img_size > $image_options['max_size'] 
			   ) {
				echo false;
				wp_die();
			}		

			if ( !function_exists( 'media_handle_sideload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
			}

			$attachment = array(
				'post_title' => '',
				'post_content' => '',
				'post_excerpt' => '',
				'post_author' => $user_id,
			);

			$attachment_id = media_handle_sideload(
				$file, 
				0,
				$attachment['post_title'],
				$attachment
			);

			update_field( $img_key, $attachment_id, $post_id );

			$results[ $this->get_image_key() ] = wp_get_attachment_image( $attachment_id, 'large', false, array( 'class' => 'medlink-hero-image' ) );
		} 
				
		echo json_encode( $results );		
		wp_die();
	}

	function get_form() {
		if ( !is_user_logged_in() ) return;
		
		ob_start();
		acf_form( array(
			'id' => 'medlink-form',
			'form_attributes' => array( 
				'class' => 'medlink-form',
				'enctype' => 'multipart/form-data',
			),
			'field_groups' => array( 'medlink' ),
			'submit_value' => esc_html__( 'Update', 'ml' ),
			'instruction_placement' => 'field',
			'uploader' => 'basic',
			'html_after_fields' => 
				'<div class="save-result success">' . esc_html__('The data is saved successfilly', 'ml') . '</div>'.
				'<div class="save-result error">' . esc_html__('There was some error on saving data', 'ml') . '</div>',
		) );
		$form_data = ob_get_clean();

		return $form_data;
	}

	function render() {
		$image = $this->render_image( get_field( $this->get_image_key() ) );
		
		$html = "<section class='medlink-container'>
			%s
			<h1 class='medlink-call-to-action'>%s</h1>
			<p class='medlink-intro'>%s</p>
		</section>";
				
		return sprintf( $html, $image, 
					   esc_html( get_field( 'page_call_to_action' ) ), 
					   esc_html( get_field( 'page_intro_text' ) ) );
	}
};
$Medlink = new Medlink();