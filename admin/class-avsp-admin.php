<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/Anny0007/AV-summarize-posts
 * @since      1.0.0
 *
 * @package    Avsp
 * @subpackage Avsp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Avsp
 * @subpackage Avsp/admin
 * @author     Ankit Vishwakarma <ank.vish007@gmail.com>
 */
class Avsp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	private $option_name      = 'avsp_settings';
	private $summary_meta_key = '_avsp_summary';
	private $audio_meta_key   = '_avsp_audio_url';

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Avsp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Avsp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/avsp-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Avsp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Avsp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/avsp-admin.js', array( 'jquery' ), $this->version, false );

	}
	public function add_settings_page() {
        add_options_page(
            __( 'AI Voice Summary Plugin', 'avsp' ),
            __( 'AI Voice Summary', 'avsp' ),
            'manage_options',
            'avsp',
            array( $this, 'settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'avsp', $this->option_name, array( $this, 'avsp_sanitize_options' )  );
        add_settings_section(
            'avsp_main_section',
            __( 'OpenAI Configuration for summarization', 'avsp' ),
            null,
            'avsp'
        );
        add_settings_field(
            'openai_api_key',
            __( 'OpenAI API Key', 'avsp' ),
            array( $this, 'api_key_field' ),
            'avsp',
            'avsp_main_section'
        );
        add_settings_field(
            'summary_length',
            __( 'Summary Length (words)', 'avsp' ),
            array( $this, 'summary_length_field' ),
            'avsp',
            'avsp_main_section'
        );
    }
	public function avsp_sanitize_options( $options ) {
		$sanitized = array();
	
		// Sanitize API Key: strip tags and spaces
		if ( isset( $options['openai_api_key'] ) ) {
			$sanitized['openai_api_key'] = sanitize_text_field( trim( $options['openai_api_key'] ) );
		}
	
		// Sanitize Summary Length: only allow positive integers, fallback to default (e.g., 100)
		if ( isset( $options['summary_length'] ) ) {
			$sanitized['summary_length'] = absint( $options['summary_length'] );
			if ( $sanitized['summary_length'] <= 0 ) {
				$sanitized['summary_length'] = 100; // default value
			}
		}
	
		return $sanitized;
	}
	public function api_key_field() {
        $opts = get_option( $this->option_name );
        ?>
        <input
            type="text"
            style='width:400px'
            name="<?php echo esc_attr($this->option_name); ?>[openai_api_key]"
            value="<?php echo isset($opts['openai_api_key']) ? esc_attr($opts['openai_api_key']) : ''; ?>">
        <?php
    }

    public function summary_length_field() {
        $opts = get_option( $this->option_name );
        ?>
        <input
            type="number"
            name="<?php echo esc_attr($this->option_name); ?>[summary_length]"
            value="<?php echo isset($opts['summary_length']) ? intval($opts['summary_length']) : 60; ?>"
            min="10"
            max="500">
        <?php
    }

    /**
     * Render settings page.
     */
    public function settings_page() {
        ?>
     <div class="wrap">
            <!-- <h1><?php esc_html_e('AI Post Summary Audio Settings', 'avsp'); ?></h1> -->
            <form action='options.php' method='post'>
                <?php
                settings_fields('avsp');
                do_settings_sections('avsp');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * On save_post, create summary and audio if not present.
     */
    public function maybe_generate_summary_audio( $post_id, $post, $update ) {
        // Only summary posts, no revisions/autosaves
        if ( wp_is_post_revision( $post_id ) || defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }

        // Get settings
        $opts = get_option( $this->option_name );
        if( empty($opts['openai_api_key']) ) return;

        $api_key = sanitize_text_field( $opts['openai_api_key'] );
        $length = isset($opts['summary_length']) ? intval($opts['summary_length']) : 60;
        // Avoid regenerating if present (except for manual triggers, for demo purposes always regenerate)
        $content = wp_strip_all_tags($post->post_content);
        if ( empty($content) ) return;

        // Generate summary
        $summary_text = $this->generate_summary( $content, $api_key, $length );
        if ( ! $summary_text ) return;

        update_post_meta( $post_id, $this->summary_meta_key, $summary_text );

        // Generate audio
        $audio_url = $this->generate_audio( $summary_text, $api_key, $post_id );

        if ( $audio_url ) {
            update_post_meta( $post_id, $this->audio_meta_key, esc_url_raw($audio_url) );
        }
    }

    /**
     * Generate post summary using OpenAI GPT.
     */
    private function generate_summary( $text, $api_key, $summary_length ) {
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $prompt = "Summarize this blog post (about {$summary_length} words):\n\n$text";
        $body = json_encode( array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array( array( 'role' => 'user', 'content' => $prompt ) ),
            'max_tokens' => 200,
            'temperature' => 0.6,
        ) );

        $response = wp_remote_post( $endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => $body,
            'timeout' => 40,
        ));

        if ( is_wp_error($response) ) return false;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset($data['choices'][0]['message']['content'])
            ? sanitize_text_field(trim($data['choices'][0]['message']['content']))
            : false;
    }

    /**
     * Generate audio for summary using OpenAI TTS. Saves file to uploads and returns URL.
     */
    private function generate_audio( $summary_text, $api_key, $post_id ) {
        $tts_endpoint = 'https://api.openai.com/v1/audio/speech';
        $body = json_encode( array(
            'model' => 'tts-1', // For better: offer a voice selection in admin!
            'input' => $summary_text,
            'voice' => 'nova',
            'response_format' => 'mp3'
        ));

        $response = wp_remote_post( $tts_endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => $body,
            'timeout' => 60
        ));

        if ( is_wp_error($response) ) return false;
        $audio_data = wp_remote_retrieve_body( $response );
        if ( empty($audio_data) ) return false;

        // Save MP3 to the uploads directory
        $upload_dir = wp_upload_dir();
        $filename = "ai-post-summary-{$post_id}_" . wp_generate_password(4, false) . ".mp3";
        $path = trailingslashit($upload_dir['path']) . $filename;

        // Write file
        file_put_contents( $path, $audio_data );
        $url = trailingslashit($upload_dir['url']) . $filename;

        return $url;
    }

    /**
     * Inject audio summary & summary text above post content.
     */
	public function inject_audio_summary( $content ) {
		if ( is_singular( 'post' ) && in_the_loop() && is_main_query() ) {
			$pid       = get_the_ID();
			$audio_url = get_post_meta( $pid, $this->audio_meta_key, true );
			$summary   = get_post_meta( $pid, $this->summary_meta_key, true );
	
			if ( $audio_url && $summary ) {
				$summary_html  = '<div class="avsp-summary-audio-box" style="margin:1em 0;padding:1em;border:1px solid #eee;background:#fafbfc;">';
				$summary_html .= '<strong>' . esc_html__( 'Listen to the post summary:', 'avsp' ) . '</strong><br />';
				$summary_html .= '<audio controls style="width:100%;max-width:400px;margin-top:0.5em;"><source src="' . esc_url( $audio_url ) . '" type="audio/mpeg">' . esc_html__( 'Your browser does not support the audio element.', 'avsp' ) . '</audio>';
				$summary_html .= '<p style="margin-top:1em;"><em>' . esc_html( $summary ) . '</em></p>';
				$summary_html .= '</div>';
				$summary_html .= '<hr />';
				// Inject above the content
				return $summary_html . $content;
			}
		}
		return $content;
	}
}
