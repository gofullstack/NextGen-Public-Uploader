<?php
/**
 * Upload tools things.
 *
 * @package NextGenGallery Public Uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'npuGalleryUpload' ) ) {

	// Public Variables.
	class npuGalleryUpload {

		public $arrImageIds          = array();
		public $arrUploadedThumbUrls = array();
		public $arrUploadedImageUrls = array();
		public $arrErrorMsg          = array();
		public $arrImageMsg          = array();
		public $arrErrorMsg_widg     = array();
		public $arrImageMsg_widg     = array();
		public $arrImageNames        = array();
		public $arrImageMeta         = array();
		public $arrShortcodeArgs     = array();
		public $strTitle             = '';
		public $strDescription       = '';
		public $strKeywords          = '';
		public $strTimeStamp         = '';
		public $strGalleryPath       = '';
		public $blnRedirectPage      = false;

		/**
		 * The npuGalleryUpload constructor.
		 */
		public function __construct() {
			add_shortcode( 'ngg_uploader', array( $this, 'shortcode_show_uploader' ) ); // Shortcode Uploader.
		}

		/**
		 * Add our scripts.
		 *
		 * @since unknown
		 */
		public function add_scripts() {
			wp_register_script( 'ngg-ajax', NGGALLERY_URLPATH . 'admin/js/ngg.ajax.js', array( 'jquery' ), '1.0.0' );
			// Setup Array.
			wp_localize_script(
				'ngg-ajax',
			    'nggAjaxSetup', array(
					'url'        => admin_url( 'admin-ajax.php' ),
					'action'     => 'ngg_ajax_operation',
					'operation'  => '',
					'nonce'      => wp_create_nonce( 'ngg-ajax' ),
					'ids'        => '',
					'permission' => esc_html__( 'You do not have the correct permission', 'nextgen-uploader' ),
					'error'      => esc_html__( 'Unexpected Error', 'nextgen-uploader' ),
					'failure'    => esc_html__( 'Upload Failed', 'nextgen-uploader' ),
				)
			);
			wp_register_script( 'ngg-progressbar', NGGALLERY_URLPATH . 'admin/js/ngg.progressbar.js', array( 'jquery' ), '1.0.0' );
			wp_register_script( 'swfupload_f10', NGGALLERY_URLPATH . 'admin/js/swfupload.js', array( 'jquery' ), '2.2.0' );
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'mutlifile', NGGALLERY_URLPATH . 'admin/js/jquery.MultiFile.js', array( 'jquery' ), '1.1.1' );
			wp_enqueue_script( 'ngg-swfupload-handler', NGGALLERY_URLPATH . 'admin/js/swfupload.handler.js', array( 'swfupload_f10' ), '1.0.0' );
			wp_enqueue_script( 'ngg-ajax' );
			wp_enqueue_script( 'ngg-progressbar' );
		}

		/**
		 * Abstracts the image upload field.
		 * Used in the uploader, uploader widget, and Gravity Forms custom input field.
		 *
		 * @since unknown
		 *
		 * @param integer $gal_id  Gallery ID for NextGen Gallery.
		 * @param string  $context Context.
		 * @param bool    $disable Whether ort not to disable.
		 * @param string  $name    Name to use.
		 * @return string  $strOutput  HTML output for image upload input.
		 */
		public function display_image_upload_input( $gal_id = 0, $context = 'shortcode', $disable = false, $name = 'galleryselect' ) {

			$strOutput = wp_nonce_field( 'ngg_addgallery', '_wpnonce', true , false );
			$strOutput .= apply_filters( 'npu_gallery_upload_display_uploader_pre_input', '', $this, $context );

			$disabled   = $disable ? " disabled='disabled'" : '';

			$strOutput .= "\n\t<div class=\"uploader\">";
			$strOutput .= "\n\t<input type=\"file\" name=\"imagefiles\" id=\"imagefiles\" {$disabled} />";
			$strOutput .= "\n</div>";
			$strOutput .= "\n<input type=\"hidden\" name=\"{$name}\" value=\"{$gal_id}\">";

			return $strOutput;
		}

		/**
		 * Display uploader.
		 *
		 * @since unknown
		 *
		 * @param int  $gal_id         Gallery ID.
		 * @param bool $strDetailsPage Details page.
		 * @param bool $blnShowAltText Show alt text.
		 * @param bool $echo           Whether or not to echo.
		 *
		 * @return string
		 */
		public function display_uploader( $gal_id, $strDetailsPage = false, $blnShowAltText = true, $echo = true ) {
			$strOutput = '';
			if ( count( $this->arrErrorMsg ) > 0 ) {
				$strOutput .= '<div class="upload_error">';
				foreach ( $this->arrErrorMsg as $msg ) {
					$strOutput .= $msg;
				}
				$strOutput .= '</div>';
			}
			if ( count( $this->arrImageMsg ) > 0 ) {
				$strOutput .= '<div class="upload_error">';
				foreach ( $this->arrImageMsg as $msg ) {
					$strOutput .= $msg;
				}
				$strOutput .= '</div>';
			}
			if ( !is_user_logged_in() && get_option( 'npu_user_role_select' ) != 99 ) {
				$strOutput .= '<div class="need_login">';
				$notlogged = get_option( 'npu_notlogged' );
				if( !empty( $notlogged ) ) {
					$strOutput .= $notlogged;
				} else {
					$strOutput .= esc_html__( 'You must be registered and logged in to upload images.', 'nextgen-uploader' );
				}
				$strOutput .= '</div>';
			} else {
				$npu_selected_user_role = get_option( 'npu_user_role_select' );
				if ( current_user_can( 'level_' . $npu_selected_user_role ) || get_option( 'npu_user_role_select' ) == 99 ) {

					$strOutput .= apply_filters( 'npu_gallery_upload_display_uploader_before_form', '', $this, 'shortcode' );

					$strOutput .= '<div id="uploadimage">';
					$strOutput .= "\n\t<form name=\"uploadimage\" id=\"uploadimage_form\" method=\"POST\" enctype=\"multipart/form-data\" accept-charset=\"utf-8\" >";

					$strOutput .= $this->display_image_upload_input( $gal_id );

					if ( !$strDetailsPage ) {
						$strOutput .= "\n\t<div class=\"image_details_textfield\">";
						if ( $blnShowAltText ) {}
						$strOutput .= "\n\t</div>";
					}

					$strOutput .= $this->maybe_display_image_description();

					$strOutput .= apply_filters( 'npu_gallery_upload_display_uploader_before_submit', '', $this, 'shortcode' );

			   	 	$strOutput .= "\n\t<div class=\"submit\"><br />";
					if ( get_option( 'npu_upload_button' ) ) {
						$strOutput .= "\n\t\t<input class=\"button-primary\" type=\"submit\" name=\"uploadimage\" id=\"uploadimage_btn\" ";
						$strOutput .= 'value="' . get_option( 'npu_upload_button' ) . '">';
					} else {
						$strOutput .= "\n\t\t<input class=\"button-primary\" type=\"submit\" name=\"uploadimage\" id=\"uploadimage_btn\" value=\"Upload\" />";
					}
					$strOutput .= "\n\t\t</div>";
					$strOutput .= "\n</form>";
					$strOutput .= "\n</div>";

					$strOutput .= apply_filters( 'npu_gallery_upload_display_uploader_after_form', '', $this, 'shortcode' );
				}
			}

			$strOutput = apply_filters( 'npu_gallery_upload_display_uploader', $strOutput, $gal_id, $strDetailsPage, $blnShowAltText, $echo, 'shortcode', $this );

			if ( $echo ) {
	   			echo $strOutput;
				return '';
			}

			return $strOutput;
		}

		/**
		 * Maybe display image description.
		 *
		 * @since unknown
		 *
		 * @param bool $i Image.
		 * @return string
		 */
		public function maybe_display_image_description( $i = false ) {

			$strOutput = '';

			if ( 'Enabled' == get_option( 'npu_image_description_select' ) ) {
				$strOutput .= '<br />' . get_option( 'npu_description_text',  __( 'Description:', 'nextgen-uploader' ) ) . '<br />';

				$name = is_numeric( $i ) ? 'imagedescription_' . $i : 'imagedescription';

				$strOutput .= "\n\t<input type=\"text\" name=\"" . esc_attr( $name ) . "\" id=\"" . esc_attr( $name ) . "\"/>";
			}

			return $strOutput;
		}

		/**
		 * Handle our upload.
		 *
		 * @since unknown
		 */
		public function handleUpload() {
			require_once( dirname(__FILE__) . '/class.npu_uploader.php' );
			require_once( NGGALLERY_ABSPATH . '/lib/meta.php' );

			if ( isset( $_POST['uploadimage'] ) ) {
				check_admin_referer( 'ngg_addgallery' );

				if ( !isset( $_FILES['MF__F_0_0']['error'] ) || $_FILES['MF__F_0_0']['error'] == 0 ) {
					$objUploaderNggAdmin = new UploaderNggAdmin();
					$objUploaderNggAdmin->upload_images();

					$this->arrImageIds = $objUploaderNggAdmin->arrImageIds;
					$this->strGalleryPath = $objUploaderNggAdmin->strGalleryPath;
					$this->arrImageNames = $objUploaderNggAdmin->arrImageNames;

					if ( is_array( $objUploaderNggAdmin->arrThumbReturn ) && count( $objUploaderNggAdmin->arrThumbReturn ) > 0 ) {
						foreach ( $objUploaderNggAdmin->arrThumbReturn as $strReturnMsg ) {
							if ( $strReturnMsg != '1' ) {
								$this->arrErrorMsg[] = $strReturnMsg;
							}
						}
					}
					if ( is_array( $this->arrImageIds ) && count( $this->arrImageIds ) > 0 ) {
						foreach ( $this->arrImageIds as $imageId ) {
							$pic                  = nggdb::find_image( $imageId );
							$objEXIF              = new nggMeta( $pic->imagePath );
							$this->strTitle       = $objEXIF->get_META( 'title' );
							$this->strDescription = $objEXIF->get_META( 'caption' );
							$this->strKeywords    = $objEXIF->get_META( 'keywords' );
							$this->strTimeStamp   = $objEXIF->get_date_time();
							// What are we doing with this stuff? It's just reassigning, unless there's only ever 1 index in the array.
						}


						if ( get_option( 'npu_upload_success' ) ) {
							$this->arrImageMsg[] = get_option( 'npu_upload_success' );
						} else {
							$this->arrImageMsg[] = esc_html__( 'Thank you! Your image has been submitted and is pending review.', 'nextgen-uploader' );
						}

						$this->sendEmail();

					} else {
						if ( get_option( 'npu_no_file' ) ) {
							$this->arrErrorMsg[] = get_option( 'npu_no_file' );
						} else {
							$this->arrErrorMsg[] = esc_html__( 'You must select a file to upload', 'nextgen-uploader' );
						}
					}
					$this->update_details();
				} else {
					if ( get_option( 'npu_upload_failed' ) ) {
						$this->arrErrorMsg[] = get_option( 'npu_upload_failed' );
					} else {
						$this->arrErrorMsg[] = esc_html__( 'Upload failed!', 'nextgen-uploader' );
					}
				}
				if ( count( $this->arrErrorMsg ) > 0 && ( is_array( $this->arrImageIds ) && count( $this->arrImageIds ) > 0 ) ) {
					$storage = C_Gallery_Storage::get_instance();
					foreach ( $this->arrImageIds as $intImageId ) {
						$storage->delete_image($intImageId);
					}
				}
			}
		}

		/**
		 * Update our details.
		 *
		 * @since unknown
		 */
		public function update_details() {
			$update_description = false;
			$update_alttext     = false;

			$image_mapper = C_Image_Mapper::get_instance();
			if ( isset( $_POST['imagedescription'] ) && !empty( $_POST['imagedescription'] ) ) {
				$this->strDescription = $_POST['imagedescription'];
				$update_description = true;
			} else {
				return;
			}
			if ( isset( $_POST['alttext'] ) && !empty( $_POST['alttext'] ) ) {
				$this->strTitle = $_POST['alttext'];
				$update_alttext = true;
			}
			if ( isset( $_POST['tags'] ) && !empty( $_POST['tags'] ) ) {
				$this->strKeywords = $_POST['tags'];
			}
			if ( count( $this->arrImageIds ) > 0 && ( $update_description || $update_alttext ) ) {
				foreach ( (array) $this->arrImageIds as $pid ) {
					$image = $image_mapper->find( $pid );
					if ( get_option( 'npu_exclude_select' ) ) {
						$image->exclude = 1;
					}
					if ( $update_description ) {
						$image->description = $this->strDescription;
					}
					if ( $update_alttext ) {
						$image->alttext = $this->strTitle;
					}
					$image_mapper->save( $image );

					$arrTags = explode( ',', $this->strKeywords );
					wp_set_object_terms( $pid, $arrTags, 'ngg_tag' );
				}
			}

			do_action( 'npu_gallery_upload_update_details', $this );
		}

		/**
		 * Output our shortcode.
		 *
		 * @since unkown
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
		public function shortcode_show_uploader( $atts ) {

			$default_args = apply_filters( 'npu_gallery_upload_shortcode_atts', array(
				'id'       => get_option( 'npu_default_gallery' ),
				'template' => ''
			), $this );

			$this->arrShortcodeArgs = version_compare( $GLOBALS['wp_version'], '3.6', '>=' )
					? shortcode_atts( $default_args, $atts, 'ngg_uploader' )
					: shortcode_atts( $default_args, $atts );

			$this->handleUpload();

			return $this->display_uploader( $this->arrShortcodeArgs['id'], false, true, false );
		}

		/**
		 * Send our email.
		 *
		 * @since unknown
		 */
		public function sendEmail() {

			if ( get_option( 'npu_notification_email' ) ) {

				$to      = apply_filters( 'npu_gallery_upload_send_email_to'     , get_option( 'npu_notification_email' ), $this );
				$subject = apply_filters( 'npu_gallery_upload_send_email_subject', esc_html__( 'New Image Pending Review - Nextgen Uploader', 'nextgen-uploader' ), $this );
				$message = apply_filters( 'npu_gallery_upload_send_email_message', esc_html__( 'A new image has been submitted and is waiting to be reviewed.', 'nextgen-uploader' ), $this );

				wp_mail( $to, $subject, $message );
			}
		}

		/**
		 * Display the form on the frontend widget.
		 *
		 * @since unknown
		 *
		 * @param int  $gal_id         Gallery ID.
		 * @param bool $strDetailsPage Something.
		 * @param bool $blnShowAltText Something.
		 * @param bool $echo           Whether or not to echo.
		 * @return mixed.
		 */
		public function display_uploader_widget( $gal_id, $strDetailsPage = false, $blnShowAltText = true, $echo = true ) {
			$output = '';

			// Check if we have any error messages.
			if ( count( $this->arrErrorMsg_widg ) > 0 ) {
				$output .= '<div class="upload_error">';
				foreach ( $this->arrErrorMsg_widg as $msg )  {
					$output .= $msg;
				}
				$output .= '</div>';
			}
			// Check if we have any image messages.
			if ( count( $this->arrImageMsg_widg ) > 0 ) {
				$output .= '<div class="upload_error">';
				foreach ( $this->arrImageMsg_widg as $msg ) {
					$output .= $msg;
				}
				$output .= '</div>';
			}

			if ( !is_user_logged_in() && get_option( 'npu_user_role_select' ) != 99 ) {
				$output .= '<div class="need_login">';
				if( get_option( 'npu_notlogged' ) ) {
					$output .= get_option( 'npu_notlogged' );
				} else {
					$output .= esc_html__( 'You must be registered and logged in to upload images.', 'nextgen-uploader' );
				}
				$output .= '</div>';
			} else {
				$npu_selected_user_role = get_option( 'npu_user_role_select' );

				if ( current_user_can( 'level_'. $npu_selected_user_role ) || get_option( 'npu_user_role_select' ) == 99 ) {

					$output .= apply_filters( 'npu_gallery_upload_display_uploader_before_form', '', $this, 'widget' );

					$output .= '<div id="uploadimage">';
					$output .= '<form name="uploadimage" id="uploadimage_form_widget" method="POST" enctype="multipart/form-data" accept-charset="utf-8">';

					$output .= '<p>' . $this->display_image_upload_input( $gal_id, 'widget' ) . '</p>';

					if ( ! $strDetailsPage ) {
						$output .= '<div class="image_details_textfield">';
						if ( $blnShowAltText ) {}
						$output .= '</div>';
					}

					if ( 'Enabled' == get_option( 'npu_image_description_select' ) ) {
						$output .= '<label for="imagedescription">';

						$output .= ( get_option( 'npu_description_text' ) ) ? get_option( 'npu_description_text' ) : __( 'Description:', 'nextgen-uploader' );

						$output .= '</label>';
						$output .= '<input type="text" name="imagedescription" id="imagedescription"/>';
					}

					$output .= apply_filters( 'npu_gallery_upload_display_uploader_before_submit', '', $this, 'widget' );

					// Set up our submit value text.
					$submit = ( get_option( 'npu_upload_button' ) ) ? get_option( 'npu_upload_button' ) : __( 'Upload', 'nextgen-uploader' );

					$output .= '<div class="submit"><input class="button-primary" type="submit" name="uploadimage" id="uploadimage_btn" value="' . $submit . '" /></div></form></div>';
				}
			}

			$output = apply_filters( 'npu_gallery_upload_display_uploader', $output, $gal_id, $strDetailsPage, $blnShowAltText, $echo, 'widget', $this );

			if ( $echo ) {
	   			echo $output;
				return '';
			}

			return $output;
		}
	}
}
$npuUpload = new npuGalleryUpload();

/**
 * Register our widget.
 *
 * @since unkown
 */
function ngg_public_uploader() {
	register_widget( "NextGenPublicUploader" );
}
add_action( 'widgets_init', 'ngg_public_uploader' );

/**
 * Class NextGenPublicUploader
 */
class NextGenPublicUploader extends WP_Widget {

	/**
	 * NextGenPublicUploader constructor.
	 */
	function __construct() {
		$widget_ops = array(
            'description'   => esc_html__( 'Upload images to a NextGEN Gallery', 'nextgen-uploader' ),
            'classname'     => 'npu_gallery_upload',
		);
		parent::__construct( 'next-gen-public-uploader-widget', esc_html__( 'NextGEN Uploader', 'nextgen-uploader' ), $widget_ops );
	}

	/**
	 * Widget display method.
	 *
	 * @since unknown
	 *
	 * @param array $args     Widghet args.
	 * @param array $instance Wodget instnace.
	 */
	function widget( $args, $instance ) {
		$npu_uploader = new npuGalleryUpload();

		$gal_id = esc_attr( $instance['gal_id'] );

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
		}
		$npu_uploader->handleUpload();

		$npu_uploader->display_uploader_widget( $gal_id, false ); // Leave as method in separate class for now.

		echo $args['after_widget'];
	}

	/**
	 * Widget form method.
	 *
	 * @since unknown
	 *
	 * @param array $instance Current instance.
	 * @return mixed
	 */
	function form( $instance ) {

		// Set Defaults.
		$instance = wp_parse_args( (array) $instance, array( 'gal_id' => '0' ) );

		$mapper      = C_Gallery_Mapper::get_instance();
		$gallerylist = $mapper->find_all();
		?>

		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'nextgen-uploader' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" /></p>

		<p>
		<label for="<?php echo $this->get_field_id( 'gal_id' ); ?>"><?php esc_html_e( 'Upload to :', 'nextgen-uploader' ); ?></label>
		<select id="<?php echo $this->get_field_id( 'gal_id' ) ?>" name="<?php echo $this->get_field_name( 'gal_id' ); ?>">
			<option value="0"><?php esc_html_e( 'Choose gallery', 'nextgen-uploader' ); ?></option>
			<?php
			foreach( $gallerylist as $gallery ) {
				$name = ( empty( $gallery->title ) ) ? $gallery->name : $gallery->title;
				echo '<option ' . selected( $instance['gal_id'], $gallery->gid, false ) . ' value="' . $gallery->gid . '">ID: ' . $gallery->gid . ' &ndash; ' . $name . '</option>';
			}
			?>
		</select>
		</p>
	<?php
	}

	/**
	 * Update method for widget.
	 *
	 * @since unknown
	 *
	 * @param array $new_instance New widget instnace.
	 * @param array $old_instance Old widget instance.
	 * @return mixed
	 */
	function update( $new_instance, $old_instance ) {

		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['gal_id'] = absint( $new_instance['gal_id'] );

		return $instance;
	}
}
