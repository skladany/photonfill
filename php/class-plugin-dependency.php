<?php
if ( ! class_exists( "Plugin_Dependency" ) ) {

	class Plugin_Dependency {

		/** @type string Basename for the plugin with the dependency  **/
		private $plugin_basename;

		/** @type string Name for the plugin we are checking for a dependency  **/
		private $dependency_name;

		/** @type string Install uri for the plugin. Can be a complete url if external to WordPress.org  **/
		private $dependency_uri;

		/** @type array Holds data for all installed plugins at the time of initialization  **/
		public $installed_plugins;

		/** @type array Holds the message generated by the last verify command  **/
		private $verify_message;

		/**
		 * Constructor
		 *
		 * @params string $name
		 * @params url $name optional
		 * @return void
		 */
		public function __construct( $plugin_name, $dependency_name, $dependency_uri="" ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			// Set the plugin defaults
			$this->plugin_name = $plugin_name;
			$this->dependency_name = $dependency_name;
			$this->dependency_uri = $dependency_uri;

			// Get the currently installed plugins
			$this->installed_plugins = get_plugins();
		}

		/**
		 * Determine the plugin status and display the appropriate message if necessary
		 * The return value is a boolean that can be used in the plugin activation hook to stop activation if the plugin dependency is not active
		 *
		 * @params string $name
		 * @params url $name optional
		 * @return void
		 */
		public function verify() {
			$plugin_uri = $this->info();
			if( $plugin_uri === false ) {
				// The plugin is not installed. Display the appropriate message and return false.
				$this->verify_message = $this->install_message();
				return false;
			} else {
				// Determine if the plugin is active
				if( !is_plugin_active( $plugin_uri ) ) {
					// The plugin is not active. Display the appropriate message and return false.
					$this->verify_message = $this->activate_message();
					return false;
				} else {
					// The plugin is installed and active.
					$this->verify_message = "";
					return true;
				}
			}
		}

		/**
		 * Display the message generated by the last verify call, if any
		 *
		 * @return void
		 */
		public function message() {
			return $this->verify_message;
		}

		/**
		 * Display a message that the plugin is installed but not activated with the activation link.
		 *
		 * @return void
		 */
		private function activate_message() {
			$plugin_file = $this->info();
			if ( $plugin_file !== false ) {
				return sprintf(
					__( '<p style="font-family: sans-serif; font-size: 12px">%s<br>Please <a href="%s" target="_top">activate %s</a> and try again.</p>' ),
					esc_html( $this->dependency_message() ),
					wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin='.$plugin_file ), 'activate-plugin_'.$plugin_file ),
					esc_html( $this->dependency_name )
				);
			}
		}

		/**
		 * Display a message that the plugin is not installed with a link to download or install.
		 *
		 * @return void
		 */
		private function install_message() {
			// Necessary for use of plugins_api
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			// See if the plugin is available on WordPress.org
			$info = plugins_api( 'plugin_information', array('slug' => $this->dependency_uri ) );

			$install_instructions = "";
			if ( is_wp_error( $info ) && filter_var($this->dependency_uri, FILTER_VALIDATE_URL) ) {
				// The plugin is not available from WordPress.org
				$install_instructions = sprintf(
					__( '<br>Please <a href="%s" target="_blank">download and install %s</a> and try again.' ),
					esc_url( $this->dependency_uri ),
					esc_html( $this->dependency_name )
				);
			} else if ( !is_wp_error( $info ) ) {
				// The plugin is available from WordPress.org
				$install_instructions = sprintf(
					__( '<br>Please <a href="%s" target="_top">install %s</a> and try again.' ),
					wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $this->slug ), 'install-plugin_' . $this->slug ),
					esc_html( $this->dependency_name )
				);
			}

			return sprintf(
				__( '<p style="font-family: sans-serif; font-size: 12px">%s%s</p>' ),
				esc_html( $this->dependency_message() ),
				$install_instructions
			); // $install instructions escaped above.
		}

		/**
		 * Display a general message about the dependency.
		 *
		 * @return void
		 */
		private function dependency_message() {
			return sprintf(
				__( '%s requires that %s is installed and active.' ),
				esc_html( $this->plugin_name ),
				esc_html( $this->dependency_name )
			);
		}

		/**
		 * Return the array key of the installed plugin, if it exists. Otherwise, this will return false.
		 *
		 * @return mixed
		 */
		private function info() {
			foreach( $this->installed_plugins as $plugin_url => $plugin_data ) {
				if( $this->dependency_name == $plugin_data['Name'] ) {
					return $plugin_url;
				}
			}
			return false;
		}
	}
}