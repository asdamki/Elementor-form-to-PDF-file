<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class EmailAsPDF extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	public function get_name() {
		return 'emailaspdf';
	}

	public function get_label() {
		return __( 'Email As PDF', 'elementor-pro' );
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			$this->get_control_id( 'section_emailaspdf' ),
			[
				'label' => $this->get_label(),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_to' ),
			[
				'label' => __( 'To', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => get_option( 'admin_email' ),
				'placeholder' => get_option( 'admin_email' ),
				'label_block' => true,
				'title' => __( 'Separate emails with commas', 'elementor-pro' ),
				'render_type' => 'none',
			]
		);

		/* translators: %s: Site title. */
		$default_message = sprintf( __( 'New message from "%s"', 'elementor-pro' ), get_option( 'blogname' ) );

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_subject' ),
			[
				'label' => __( 'Subject', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => $default_message,
				'placeholder' => $default_message,
				'label_block' => true,
				'render_type' => 'none',
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_content' ),
			[
				'label' => __( 'Message', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '[all-fields]',
				'placeholder' => '[all-fields]',
				'description' => __( 'By default, all form fields are sent via shortcode: <code>[all-fields]</code>. Want to customize sent fields? Copy the shortcode that appears inside the field and paste it above.', 'elementor-pro' ),
				'label_block' => true,
				'render_type' => 'none',
			]
		);

		$site_domain = \ElementorPro\Classes\Utils::get_site_domain();

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_from' ),
			[
				'label' => __( 'From Email', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'email@' . $site_domain,
				'render_type' => 'none',
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_from_name' ),
			[
				'label' => __( 'From Name', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => get_bloginfo( 'name' ),
				'render_type' => 'none',
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_reply_to' ),
			[
				'label' => __( 'Reply-To', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'' => '',
				],
				'render_type' => 'none',
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_to_cc' ),
			[
				'label' => __( 'Cc', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'title' => __( 'Separate emails with commas', 'elementor-pro' ),
				'render_type' => 'none',
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_to_bcc' ),
			[
				'label' => __( 'Bcc', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'title' => __( 'Separate emails with commas', 'elementor-pro' ),
				'render_type' => 'none',
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_form_metadata' ),
			[
				'label' => __( 'Meta Data', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'label_block' => true,
				'separator' => 'before',
				'default' => [
					'date',
					'time',
					'page_url',
					'user_agent',
					'remote_ip',
					'credit',
				],
				'options' => [
					'date' => __( 'Date', 'elementor-pro' ),
					'time' => __( 'Time', 'elementor-pro' ),
					'page_url' => __( 'Page URL', 'elementor-pro' ),
					'user_agent' => __( 'User Agent', 'elementor-pro' ),
					'remote_ip' => __( 'Remote IP', 'elementor-pro' ),
					'credit' => __( 'Credit', 'elementor-pro' ),
				],
				'render_type' => 'none',
			]
		);

		$widget->add_control(
			$this->get_control_id( 'emailaspdf_content_type' ),
			[
				'label' => __( 'Send As', 'elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'html',
				'render_type' => 'none',
				'options' => [
					'html' => __( 'HTML', 'elementor-pro' ),
					'plain' => __( 'Plain', 'elementor-pro' ),
					'pdf' => __( 'PDF', 'elementor-pro' ),
				],
			]
		);

		$widget->end_controls_section();
	}

	public function on_export( $element ) {
		$controls_to_unset = [
			'emailaspdf_to',
			'emailaspdf_from',
			'emailaspdf_from_name',
			'emailaspdf_subject',
			'emailaspdf_reply_to',
			'emailaspdf_to_cc',
			'emailaspdf_to_bcc',
		];

		foreach ( $controls_to_unset as $base_id ) {
			$control_id = $this->get_control_id( $base_id );
			unset( $element['settings'][ $control_id ] );
		}

		return $element;
	}

	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );
		$sendpdf = false;
		$send_html = false;
		$attachments = array();
		$content_type_email = $settings[ $this->get_control_id( 'emailaspdf_content_type' ) ];
		
		if($content_type_email == 'pdf'){
			
			$sendpdf = true;
			$send_html = false;
			
		}elseif($content_type_email == 'html'){
			
			$sendpdf = false;
			$send_html = true;
			
		}
		
		$line_break = ( $send_html || $sendpdf ) ? '<br>' : "\n";

		$fields = [
			'emailaspdf_to' => get_option( 'admin_email' ),
			/* translators: %s: Site title. */
			'emailaspdf_subject' => sprintf( __( 'New message from "%s"', 'elementor-pro' ), get_bloginfo( 'name' ) ),
			'emailaspdf_content' => '[all-fields]',
			'emailaspdf_from_name' => get_bloginfo( 'name' ),
			'emailaspdf_from' => get_bloginfo( 'admin_email' ),
			'emailaspdf_reply_to' => 'noreplay@' . \ElementorPro\Classes\Utils::get_site_domain(),
			'emailaspdf_to_cc' => '',
			'emailaspdf_to_bcc' => '',
		];

		foreach ( $fields as $key => $default ) {
			$setting = trim( $settings[ $this->get_control_id( $key ) ] );
			$setting = $record->replace_setting_shortcodes( $setting );
			if ( ! empty( $setting ) ) {
				$fields[ $key ] = $setting;
			}
		}

		$email_reply_to = '';

		if ( ! empty( $fields['emailaspdf_reply_to'] ) ) {
			$sent_data = $record->get( 'sent_data' );
			foreach ( $record->get( 'fields' ) as $field_index => $field ) {
				if ( $field_index === $fields['emailaspdf_reply_to'] && ! empty( $sent_data[ $field_index ] ) && is_email( $sent_data[ $field_index ] ) ) {
					$email_reply_to = $sent_data[ $field_index ];
					break;
				}
			}
		}

		$fields['emailaspdf_content'] = $this->replace_content_shortcodes( $fields['emailaspdf_content'], $record, $line_break );

		$email_meta = '';

		$form_metadata_settings = $settings[ $this->get_control_id( 'emailaspdf_form_metadata' ) ];

		foreach ( $record->get( 'meta' ) as $id => $field ) {
			if ( in_array( $id, $form_metadata_settings ) ) {
				$email_meta .= $this->field_formatted( $field ) . $line_break;
			}
		}

		if ( ! empty( $email_meta ) ) {
			$fields['emailaspdf_content'] .= $line_break . '---' . $line_break . $line_break . $email_meta;
		}

		$headers = sprintf( 'From: %s <%s>' . "\r\n", $fields['emailaspdf_from_name'], $fields['emailaspdf_from'] );
		$headers .= sprintf( 'Reply-To: %s' . "\r\n", $email_reply_to );

		if ( $send_html ) {
			$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
		}
		
		if($sendpdf){
			
			//require_once(ELEMENTOR_FORM_PDF_PATH.'lib/fpdf/html2pdf.php');
			require_once(ELEMENTOR_FORM_PDF_PATH.'lib/mpdf/vendor/autoload.php');
			
			$separator = md5(time());
			$eol = PHP_EOL;
			$filename = 'form_submitted_'.$separator.'.pdf';
			$file_path =  ELEMENTOR_FORM_PDF_UPLOAD_PATH .'/'. $filename;
			
			//$pdf=new PDF_HTML();
			//$mpdf = new mPDF('c');
			$mpdf = new mPDF('','A4','','dejavusans',32,25,27,25,16,13);

			$mpdf->SetDirectionality('rtl');
			$mpdf->mirrorMargins = true;
			$mpdf->SetDisplayMode('fullpage','two');

			$mpdf->autoLangToFont = true;

			$mpdf->defaultPageNumStyle = 'arabic-indic';
			
			//$mpdf->SetFont('dejavusans','',14);
			
			$mpdf->WriteHTML($fields['emailaspdf_content']);
			$mpdf->Output($file_path,'F');
			//$pdf->AddFont('DejaVuSans','','DejaVuSans.php');
			//$pdf->AddPage();
			//$pdf->SetFont('DejaVuSans','',14);
			//$pdf->SetFont('Arial','',12);
			//$pdf->AddPage();
			//$pdf->WriteHTML($fields['emailaspdf_content']);
			//$pdf->Output('F',$file_path,true);
			$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
			$attachments = array($file_path);
		}

		$cc_header = '';
		if ( ! empty( $fields['emailaspdf_to_cc'] ) ) {
			$cc_header = 'Cc: ' . $fields['emailaspdf_to_cc'] . "\r\n";
		}

		/**
		 * Email headers.
		 *
		 * Filters the additional headers sent when the form send an email.
		 *
		 * @since 1.0.0
		 *
		 * @param string|array $headers Additional headers.
		 */
		$headers = apply_filters( 'elementor_pro/forms/wp_mail_headers', $headers );

		/**
		 * Email content.
		 *
		 * Filters the content of the email sent by the form.
		 *
		 * @since 1.0.0
		 *
		 * @param string $email_content Email content.
		 */
		$fields['emailaspdf_content'] = apply_filters( 'elementor_pro/forms/wp_mail_message', $fields['emailaspdf_content'] );
		
		if($sendpdf && count($attachments) > 0){
			
			$email_sent = wp_mail( $fields['emailaspdf_to'], $fields['emailaspdf_subject'], $fields['emailaspdf_content'], $headers . $cc_header , $attachments);
			
		}else{
			
			$email_sent = wp_mail( $fields['emailaspdf_to'], $fields['emailaspdf_subject'], $fields['emailaspdf_content'], $headers . $cc_header );
		}

		if ( ! empty( $fields['emailaspdf_to_bcc'] ) ) {
			$bcc_emails = explode( ',', $fields['emailaspdf_to_bcc'] );
			foreach ( $bcc_emails as $bcc_email ) {
				
				if($sendpdf && count($attachments) > 0){
			
					wp_mail( trim( $bcc_email ), $fields['emailaspdf_subject'], $fields['emailaspdf_content'], $headers ,$attachments);
					
				}else{
					wp_mail( trim( $bcc_email ), $fields['emailaspdf_subject'], $fields['emailaspdf_content'], $headers );
				}
			}
		}

		/**
		 * Elementor form mail sent.
		 *
		 * Fires when an email was sent successfully.
		 *
		 * @since 1.0.0
		 *
		 * @param array       $settings Form settings.
		 * @param Form_Record $record   An instance of the form record.
		 */
		do_action( 'elementor_pro/forms/mail_sent', $settings, $record );

		if ( ! $email_sent ) {
			$ajax_handler->add_error_message( \ElementorPro\Modules\Forms\Classes\Ajax_Handler::get_default_message( \ElementorPro\Modules\Forms\Classes\Ajax_Handler::SERVER_ERROR, $settings ) );
		}
	}

	private function field_formatted( $field ) {
		$formatted = '';
		if ( ! empty( $field['title'] ) ) {
			$formatted = sprintf( '%s: %s', $field['title'], $field['value'] );
		} elseif ( ! empty( $field['value'] ) ) {
			$formatted = sprintf( '%s', $field['value'] );
		}

		return $formatted;
	}

	// Allow overwrite the control_id with a prefix, @see Email2
	protected function get_control_id( $control_id ) {
		return $control_id;
	}

	/**
	 * @param string      $email_content
	 * @param Form_Record $record
	 *
	 * @return string
	 */
	private function replace_content_shortcodes( $email_content, $record, $line_break ) {
		$email_content = do_shortcode( $email_content );
		$all_fields_shortcode = '[all-fields]';

		if ( false !== strpos( $email_content, $all_fields_shortcode ) ) {
			$text = '';
			foreach ( $record->get( 'fields' ) as $field ) {
				$formatted = $this->field_formatted( $field );
				if ( ( 'textarea' === $field['type'] ) && ( '<br>' === $line_break ) ) {
					$formatted = str_replace( [ "\r\n", "\n", "\r" ], '<br />', $formatted );
				}
				$text .= $formatted . $line_break;
			}

			$email_content = str_replace( $all_fields_shortcode, $text, $email_content );

		}

		return $email_content;
	}
}
