<?php
/**
 * Class NpsSkipAction
 *
 * @package WPDesk\FS\Nps
 */

namespace WPDesk\FS\Nps;

use FSVendor\WPDesk\Nps\UserMeta;
use FSVendor\WPDesk\PluginBuilder\Plugin\Hookable;

/**
 * Can skip answer.
 */
class NpsSkipAction implements Hookable {
	const FEEDBACK_SKIP_OPTION_ID = 'to_soon_to_say';
	const POST_OPTION_ID_NAME     = 'checked_option_id';
	const NEXT_DATE_IN_SECONDS    = 3 * DAY_IN_SECONDS;
	const DISPLAY_AGAIN_DATE      = 'display_again_date';

	/**
	 * Hooks.
	 */
	public function hooks() {
		add_action( 'nps/sent', [ $this, 'handle_action' ], 10, 2 );
	}

	/**
	 * @param UserMeta $user_meta   .
	 * @param array    $posted_data .
	 *
	 * @return void
	 */
	public function handle_action( UserMeta $user_meta, array $posted_data ) {
		$checked_option_id = $posted_data[ self::POST_OPTION_ID_NAME ] ?? null;

		$user_meta->delete_meta( self::DISPLAY_AGAIN_DATE );

		if ( $checked_option_id === self::FEEDBACK_SKIP_OPTION_ID ) {
			$user_meta->update_meta( self::DISPLAY_AGAIN_DATE, current_time( 'timestamp' ) + self::NEXT_DATE_IN_SECONDS );
		}

		$user_meta->save();
	}
}
