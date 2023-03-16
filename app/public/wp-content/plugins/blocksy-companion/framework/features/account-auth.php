<?php

namespace Blocksy;

class AccountAuth {
	public function __construct() {
		add_action(
			'wp_ajax_blc_implement_user_registration',
			[$this, 'blc_implement_user_registration']
		);

		add_action(
			'wp_ajax_nopriv_blc_implement_user_registration',
			[$this, 'blc_implement_user_registration']
		);

		add_action(
			'wp_ajax_blc_implement_user_login',
			[$this, 'blc_implement_user_login']
		);

		add_action(
			'wp_ajax_nopriv_blc_implement_user_login',
			[$this, 'blc_implement_user_login']
		);

		add_action(
			'wp_ajax_blc_implement_user_lostpassword',
			[$this, 'blc_implement_user_lostpassword']
		);

		add_action(
			'wp_ajax_nopriv_blc_implement_user_lostpassword',
			[$this, 'blc_implement_user_lostpassword']
		);

		add_filter('bm_rgn_is_modal', function ($value) {
			$render = new \Blocksy_Header_Builder_Render();

			if (
				$render->contains_item('account')
				||
				is_customize_preview()
			) {
				return true;
			}

			return $value;
		});
	}

	public function blc_implement_user_lostpassword() {
		ob_start();
		require_once ABSPATH . 'wp-login.php';
		$res = ob_get_clean();

		$errors = [];
		$success = false;

		$nonce_value = '';

		if (
			isset($_POST['blocksy-lostpassword-nonce'])
			&&
			is_string($_POST['blocksy-lostpassword-nonce'])
		) {
			$nonce_value = wp_unslash($_POST['blocksy-lostpassword-nonce']);
		}

		if (!wp_verify_nonce($nonce_value, 'blocksy-lostpassword')) {
			wp_send_json_error([]);
			exit;
		}

		if (class_exists('WC_Shortcode_My_Account')) {
			$success = \WC_Shortcode_My_Account::retrieve_password();

			if (!$success) {
				$errors = new \WP_Error();
				$notices = wc_get_notices();

				if (isset($notices['error'])) {
					foreach ($notices['error'] as $notice) {
						$errors->add(
							'invalidcombo',
							sprintf(
								__('<strong>Error</strong>: %s'),
								$notice['notice']
							)
						);
					}
				}
			}
		} else {
			$errors = retrieve_password();
			$success = !is_wp_error($errors);
		}

		if ($success) {
			$errors = new \WP_Error();

			$errors->add(
				'confirm',
				sprintf(
					/* translators: %s: Link to the login page. */
					__(
						'Check your email for the confirmation link, then visit the <a href="%s" data-login="yes">login page</a>.',
						'blocksy-companion'
					),
					wp_login_url()
				),
				'message'
			);

			$redirect_to = admin_url();
			$errors = apply_filters('wp_login_errors', $errors, $redirect_to);

			login_header(__('Check your email', 'blocksy-companion'), '', $errors);

			wp_die();
		}

		/**
		 * Fires before the lost password form.
		 *
		 * @since 1.5.1
		 * @since 5.1.0 Added the `$errors` parameter.
		 *
		 * @param WP_Error $errors A `WP_Error` object containing any errors generated by using invalid
		 *                         credentials. Note that the error object may not contain any errors.
		 */
		do_action('lost_password', $errors);

		login_header(
			__('Lost Password'),
			'<p class="message">' . __('Please enter your username or email address. You will receive an email message with instructions on how to reset your password.') . '</p>',
			$errors
		);
	}

	public function blc_implement_user_registration() {
		ob_start();
		require_once ABSPATH . 'wp-login.php';
		$res = ob_get_clean();

		$_POST['woocommerce-register-nonce'] = '~';
		add_filter('dokan_register_nonce_check', '__return_false');

		$users_can_register = get_option('users_can_register');

		if (get_option('woocommerce_enable_myaccount_registration') === 'yes') {
			$users_can_register = true;
		}

		if (!$users_can_register) {
			exit;
		}

		$user_login = '';
		$user_email = '';
		$user_pass = '';
		$nonce_value = '';

		if (isset($_POST['user_login']) && is_string($_POST['user_login'])) {
			$user_login = wp_unslash($_POST['user_login']);
		}

		if (isset($_POST['user_email']) && is_string($_POST['user_email'])) {
			$user_email = wp_unslash($_POST['user_email']);
		}

		if (isset($_POST['user_pass']) && is_string($_POST['user_pass'])) {
			$user_pass = wp_unslash($_POST['user_pass']);
		}

		if (
			isset($_POST['blocksy-register-nonce'])
			&&
			is_string($_POST['blocksy-register-nonce'])
		) {
			$nonce_value = wp_unslash($_POST['blocksy-register-nonce']);
		}

		if (!wp_verify_nonce($nonce_value, 'blocksy-register')) {
			wp_send_json_error([]);
			exit;
		}

		if ($this->has_woo_register_flow()) {
			$validation_error = new \WP_Error();
			$validation_error = apply_filters(
				'woocommerce_process_registration_errors',
				$validation_error,
				$user_login,
				$user_pass,
				$user_email
			);

			$errors = wc_create_new_customer(
				sanitize_email($user_email),
				wc_clean($user_login),
				$user_pass
			);

			if (
				! is_wp_error($errors)
				&&
				apply_filters(
					'woocommerce_registration_auth_new_customer',
					true,
					$errors
				)
				&&
				isset($_POST['role'])
				&&
				$_POST['role'] === 'seller'
			) {
				ob_start();
				wc_set_customer_auth_cookie($errors);
				ob_clean();
			}
		} else {
			$errors = register_new_user($user_login, $user_email);
		}

		if (! is_wp_error($errors)) {
			$errors = new \WP_Error();

			if ($this->has_woo_register_flow()) {
				$error_message = sprintf(
					__(
						'Your account was created successfully. Your login details have been sent to your email address. Please visit the <a href="%s" data-login="yes">login page</a>.',
						'blocksy-companion'
					),
					wp_login_url()
				);

				if ('yes' === get_option('woocommerce_registration_generate_password')) {
					$error_message = sprintf(
						__(
							'Your account was created successfully and a password has been sent to your email address. Please visit the <a href="%s" data-login="yes">login page</a>.',
							'blocksy-companion'
						),
						wp_login_url()
					);
				}

				$errors->add('registered', $error_message, 'message');
			} else {
				$errors->add(
					'registered',
					sprintf(
						/* translators: %s: Link to the login page. */
						__('Registration complete. Please check your email, then visit the <a href="%s" data-login="yes">login page</a>.'),
						wp_login_url()
					),
					'message'
				);
			}

			$redirect_to = admin_url();
			$errors = apply_filters('wp_login_errors', $errors, $redirect_to);

			login_header(__('Check your email', 'blocksy-companion'), '', $errors);

			wp_die();
		}

		login_header(
			__('Registration Form', 'blocksy-companion'),
			'<p class="message register">' . __('Register For This Site', 'blocksy-companion') . '</p>',
			$errors
		);

		wp_die();
	}

	public function blc_implement_user_login() {
		require_once ABSPATH . 'wp-login.php';
		wp_die();
	}

	public function has_woo_register_flow() {
		return apply_filters(
			'blocksy:account:register:has_woo_register',
			function_exists('wc_create_new_customer')
		);
	}
}
