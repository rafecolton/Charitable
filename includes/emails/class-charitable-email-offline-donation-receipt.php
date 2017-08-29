<?php
/**
 * Class that models the offline donation receipt email.
 *
 * @version     1.5.0
 * @package     Charitable/Classes/Charitable_Email_Offline_Donation_Receipt
 * @author      Eric Daams
 * @copyright   Copyright (c) 2017, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Charitable_Email_Offline_Donation_Receipt' ) && class_exists( 'Charitable_Email_Donation_Receipt' ) ) {

	/**
	 * Offline Donation Receipt
	 *
	 * @since 1.5.0
	 */
	class Charitable_Email_Offline_Donation_Receipt extends Charitable_Email_Donation_Receipt {

		/* @var string */
		CONST ID = 'offline_donation_receipt';

		/**
		 * Object types that are used in this email.
		 *
		 * @var string[]
		 */
		protected $object_types = array( 'donation' );

		/**
		 * Instantiate the email class, defining its key values.
		 *
		 * @param mixed[] $objects Array containing a Charitable_Donation object.
		 */
		public function __construct( $objects = array() ) {
			parent::__construct( $objects );

			/**
			 * Customize the name of the offline donation notification.
			 *
			 * @since 1.5.0
			 *
			 * @param string $name
			 */
			$this->name = apply_filters( 'charitable_email_offline_donation_receipt_name', __( 'Offline Donation Receipt', 'charitable' ) );
		}

		/**
		 * Returns the current email's ID.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public static function get_email_id() {
			return self::ID;
		}

		/**
		 * Static method that is fired right after a donation is completed, sending the donation receipt.
		 *	 
		 * @since  1.5.0
		 *
		 * @param  int $donation_id The donation ID we're sending an email about.
		 * @return boolean
		 */
		public static function send_with_donation_id( $donation_id ) {
			if ( ! charitable_get_helper( 'emails' )->is_enabled_email( self::get_email_id() ) ) {
				return false;
			}

			/* If the donation is not pending, stop here. */
			if ( 'charitable-pending' != get_post_status( $donation_id ) ) {
				return false;
			}

			/* If the donation was not made with the offline payment option, stop here. */
			if ( 'offline' != get_post_meta( $donation_id, 'donation_gateway', true ) ) {
				return false;
			}

			if ( ! apply_filters( 'charitable_send_' . self::get_email_id(), true, $donation ) ) {
				return false;
			}

			/* All three of those checks passed, so proceed with sending the email. */
			$email = new Charitable_Email_Offline_Donation_Receipt( array(
				'donation' => new Charitable_Donation( $donation_id )
			) );

			/**
			 * Don't resend the email.
			 */
			if ( $email->is_sent_already( $donation_id ) ) {
				return false;
			}

			$sent = $email->send();

			/**
			 * Log that the email was sent.
			 */
			if ( apply_filters( 'charitable_log_email_send', true, self::get_email_id(), $email ) ) {
				$email->log( $donation_id, $sent );
			}

			return true;
		}

		/**
		 * Return the default subject line for the email.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		protected function get_default_subject() {
			/**
			 * Filter the default subject line.
			 *
			 * @since 1.5.0
			 *
			 * @param string           $subject The default subject line.
			 * @param Charitable_Email $email   The Charitable_Email object.
			 */
			return apply_filters( 'charitable_email_offline_donation_receipt_default_subject', __( 'Thank you for your offline donation', 'charitable' ), $this );
		}

		/**
		 * Return the default headline for the email.
		 *
		 * @return  string
		 * @since   1.5.0
		 */
		protected function get_default_headline() {
			/**
			 * Filter the default headline.
			 *
			 * @since 1.5.0
			 *
			 * @param string           $headline The default headline.
			 * @param Charitable_Email $email    The Charitable_Email object.
			 */
			return apply_filters( 'charitable_email_offline_donation_receipt_default_headline', __( 'Your Offline Donation Receipt', 'charitable' ), $this );
		}

		/**
		 * Return the default body for the email.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		protected function get_default_body() {
			ob_start();
?>
Dear [charitable_email show=donor_first_name],

Thank you so much for your generous offline donation. Somebody from our fundraising team will contact you for your payment.

<strong>Your Offline Donation</strong>
[charitable_email show=donation_summary]

With thanks, [charitable_email show=site_name]
<?php
			/**
			 * Filter the default body content.
			 *
			 * @since 1.5.0
			 *
			 * @param string           $body  The body content.
			 * @param Charitable_Email $email The Charitable_Email object.
			 */
			return apply_filters( 'charitable_email_offline_donation_receipt_default_body', ob_get_clean(), $this );
		}
  	}
}
