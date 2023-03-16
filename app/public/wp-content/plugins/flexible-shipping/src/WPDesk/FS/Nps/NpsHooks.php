<?php
/**
 * Class NpsHooks
 *
 * @package WPDesk\FS\Nps
 */

namespace WPDesk\FS\Nps;

use FSVendor\WPDesk\Nps\DisplayDecisions\AdminPageDisplayNpsDecision;
use FSVendor\WPDesk\Nps\DisplayDecisions\DisplayNpsLocationsAndUserDecisions;
use FSVendor\WPDesk\Nps\DisplayDecisions\ShippingMethodDisplayNpsDecision;
use FSVendor\WPDesk\Nps\DisplayDecisions\UserDisplayNpsDecision;
use FSVendor\WPDesk\Nps\FeedbackOption;
use FSVendor\WPDesk\Nps\Nps;
use FSVendor\WPDesk\Nps\UserMeta;
use FSVendor\WPDesk\Nps\UuidUserId;
use FSVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use FSVendor\WPDesk\PluginBuilder\Plugin\HookableParent;

/**
 * Can decide if display NPS.
 */
class NpsHooks implements Hookable {
	use HookableParent;

	/**
	 * @var string
	 */
	private $scripts_version;

	/**
	 * @var string
	 */
	private $plugin_assets_url;

	/**
	 * @param string $plugin_assets_url .
	 * @param string $scripts_version   .
	 */
	public function __construct( string $plugin_assets_url, string $scripts_version ) {
		$this->scripts_version   = $scripts_version;
		$this->plugin_assets_url = $plugin_assets_url;
	}

	/**
	 * Init hooks (actions and filters).
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_init', [ $this, 'init_nps' ] );
	}

	public function init_nps() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// User Meta.
		$user_meta_name = 'fs_nps';
		$user_meta      = new UserMeta( wp_get_current_user(), $user_meta_name );

		// Again Nps.
		$is_again_nps = $this->is_again_nps( $user_meta );

		if ( $is_again_nps ) {
			$user_suffix = uniqid();
		}

		$nps = new Nps(
			'fs_nps',
			'vi75TA6E40L0BlKQ',
			'60b0d308a43c20215b585189',
			'5116ff93-1e16-411a-8c88-32ad2256df28',
			new UuidUserId( 'wpdesk_id', $user_suffix ?? '' ),
			$this->plugin_assets_url . 'vendor_prefixed/wpdesk/wp-nps/assets/',
			$this->scripts_version,
			new DisplayNpsLocationsAndUserDecisions(
				[
					new ShippingMethodDisplayNpsDecision( 'flexible_shipping_single' ),
					new AdminPageDisplayNpsDecision(
						[
							'page'    => 'wc-settings',
							'tab'     => 'shipping',
							'section' => 'flexible_shipping_info',
						]
					),
				],
				[
					new NpsDisplayDecision( new UserDisplayNpsDecision( $user_meta_name ), $user_meta ),
				]
			),
			$user_meta_name,
			admin_url( 'admin-ajax.php' )
		);
		$nps->set_question( __( 'Hey, we are curious how would you grade your first impression on Flexible Shipping so far?', 'flexible-shipping' ) );
		$nps->set_best_label( __( 'Wow, it\'s awesome!', 'flexible-shipping' ) );
		$nps->set_worst_label( __( 'Really poor', 'flexible-shipping' ) );
		$nps->set_feedback_question( __( 'Have you encountered any difficulties?', 'flexible-shipping' ) );

		$privacy_policy_url = get_user_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/polityka-prywatnosci/' : 'https://octolize.com/terms-of-service/privacy-policy/';

		$nps->set_disclaimer(
			sprintf(
			// translators: open tag, close tag.
				__( 'By using the \'Send feedback\' button I hereby agree and consent to the terms of %1$sPrivacy Policy%2$s.', 'flexible-shipping' ),
				'<a target="_blank" href="' . esc_url( $privacy_policy_url ) . '">',
				'</a>'
			)
		);

		$nps->add_feedback_option( new FeedbackOption( __( 'Everything is fine so far', 'flexible-shipping' ) ) );

		$feedback_option = new FeedbackOption(
			__( 'I had some configuration problems with...', 'flexible-shipping' ),
			__( 'What was difficult to set up?', 'flexible-shipping' )
		);
		$feedback_option->set_required_additional_question();
		$nps->add_feedback_option( $feedback_option );

		$feedback_option = new FeedbackOption(
			__( 'I need more shipping cost calculation conditions based on...', 'flexible-shipping' ),
			__( 'Please list the calculation conditions you need:', 'flexible-shipping' )
		);
		$feedback_option->set_required_additional_question();
		$nps->add_feedback_option( $feedback_option );

		$feedback_option = new FeedbackOption(
			__( 'There is no plugin translation to...', 'flexible-shipping' ),
			__( 'What translation was missing?', 'flexible-shipping' )
		);
		$feedback_option->set_required_additional_question();
		$nps->add_feedback_option( $feedback_option );

		if ( ! $is_again_nps ) {
			$feedback_option = new FeedbackOption( __( 'Too soon to say', 'flexible-shipping' ) );
			$feedback_option->set_id( NpsSkipAction::FEEDBACK_SKIP_OPTION_ID );
			$nps->add_feedback_option( $feedback_option );
		}

		$this->add_hookable( $nps );

		$this->add_hookable( new ShippingMethodFirstSettingsTime() );
		$this->add_hookable( new NpsSkipAction() );

		$this->hooks_on_hookable_objects();
	}

	/**
	 * @param UserMeta $user_meta
	 *
	 * @return bool
	 */
	private function is_again_nps( UserMeta $user_meta ): bool {
		return (bool) $user_meta->get_from_meta_value( NpsSkipAction::DISPLAY_AGAIN_DATE, false );
	}
}
