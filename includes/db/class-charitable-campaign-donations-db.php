<?php
/**
 * Charitable Campaign Donations DB class.
 *
 * @package     Charitable/Classes/Charitable_Campaign_Donations_DB
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2015, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'Charitable_Campaign_Donations_DB' ) ) :

	/**
	 * Charitable_Campaign_Donations_DB
	 *
	 * @since       1.0.0
	 */
	class Charitable_Campaign_Donations_DB extends Charitable_DB {

		/**
		 * The version of our database table
		 *
		 * @access  public
		 * @since   1.0.0
		 */
		public $version = '1.0.0';

		/**
		 * The name of the primary column
		 *
		 * @access  public
		 * @since   1.0.0
		 */
		public $primary_key = 'campaign_donation_id';

		/**
		 * Stores whether the site is using commas for decimals in amounts.
		 *
		 * @var     boolean
		 * @access  private
		 * @since   1.3.0
		 */
		private $comma_decimal;

		/**
		 * Set up the database table name.
		 *
		 * @access  public
		 * @since   1.0.0
		 */
		public function __construct() {
			global $wpdb;

			$this->table_name = $wpdb->prefix . 'charitable_campaign_donations';
		}

		/**
		 * Create the table.
		 *
		 * @global  $wpdb
		 * @access  public
		 * @since   1.0.0
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
                    campaign_donation_id bigint(20) NOT NULL AUTO_INCREMENT,
                    donation_id bigint(20) NOT NULL,
                    donor_id bigint(20) NOT NULL,
                    campaign_id bigint(20) NOT NULL,
                    campaign_name text NOT NULL,
                    amount decimal(13, 4) NOT NULL,
                    PRIMARY KEY  (campaign_donation_id),
                    KEY donation (donation_id),
                    KEY campaign (campaign_id)
                    ) $charset_collate;";

			$this->_create_table( $sql );
		}

		/**
		 * Whitelist of columns.
		 *
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_columns() {
			return array(
				'campaign_donation_id' => '%d',
				'donation_id'          => '%d',
				'donor_id'             => '%d',
				'campaign_id'          => '%d',
				'campaign_name'        => '%s',
				'amount'               => '%f',
			);
		}

		/**
		 * Default column values.
		 *
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_column_defaults() {
			return array(
				'campaign_donation_id' => '',
				'donation_id'          => '',
				'donor_id'             => '',
				'campaign_id'          => '',
				'campaign_name'        => '',
				'amount'               => '',
			);
		}

		/**
		 * Add a new campaign donation.
		 *
		 * @param   array       $data
		 * @return  int         The ID of the inserted campaign donation
		 * @access  public
		 * @since   1.0.0
		 */
		public function insert( $data, $type = 'campaign_donation' ) {
			if ( ! isset( $data['campaign_name'] ) ) {
				$data['campaign_name'] = get_the_title( $data['campaign_id'] );
			}

			Charitable_Campaign::flush_donations_cache( $data['campaign_id'] );

			return parent::insert( $data, $type );
		}

		/**
		 * Update campaign donation record.
		 *
		 * @param   int         $row_id
		 * @param   array       $data
		 * @param   string      $where          Column used in where argument.
		 * @return  boolean
		 * @access  public
		 * @since   1.0.0
		 */
		public function update( $row_id, $data = array(), $where = '' ) {
			if ( empty( $where ) ) {
				Charitable_Campaign::flush_donations_cache( $row_id );
			}

			return parent::update( $row_id, $data, $where );
		}

		/**
		 * Delete a row identified by the primary key.
		 *
		 * @param   int $row_id
		 * @access  public
		 * @since   1.0.0
		 * @return  bool
		 */
		public function delete( $row_id = 0 ) {
			Charitable_Campaign::flush_donations_cache( $row_id );

			return parent::delete( $row_id );
		}

		/**
		 * Delete all campaign donation records for a given donation.
		 *
		 * @param   int $donation_id
		 * @access  public
		 * @static
		 * @since   1.2.0
		 * @return  bool
		 */
		public static function delete_donation_records( $donation_id ) {
			$table = charitable_get_table( 'campaign_donations' );

			foreach ( $table->get_campaigns_for_donation( $donation_id ) as $campaign_id ) {
				Charitable_Campaign::flush_donations_cache( $campaign_id );
			}

			return $table->delete_by( 'donation_id', $donation_id );
		}

		/**
		 * Get the total amount donated, ever.
		 *
		 * @global  $wpdb
		 * @param   string[] $statuses
		 * @return  float
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_total( $statuses = array() ) {
			global $wpdb;

			if ( empty( $statuses ) ) {
				$statuses = charitable_get_approval_statuses();
			}

			list( $status_clause, $parameters ) = $this->get_donation_status_clause( $statuses );

			$sql = "SELECT SUM(cd.amount) 
                    FROM $this->table_name cd
                    INNER JOIN $wpdb->posts p
                    ON p.ID = cd.donation_id
                    WHERE 1 = 1
                    $status_clause";

			$total = $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) );

			if ( $this->is_comma_decimal() ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Return an object containing all campaign donations associated with a particular
		 * campaign ID or a particular donation ID.
		 *
		 * @global 	WPDB      $wpdb
		 * @param 	string    $field The field we are retrieving donations by. Either 'campaign' or 'donation'.
		 * @param 	int|int[] $donation_id A single donation ID or an array of IDs.
		 * @return  Object
		 * @access  public
		 * @since   1.4.0
		 */
		public function get_campaign_donations_by( $field, $donation_id ) {
			global $wpdb;

			$column = $this->get_sanitized_column( $field );

			list( $in, $parameters ) = $this->get_in_clause_params( $donation_id );

			$sql = "SELECT * 
                    FROM $this->table_name 
                    WHERE $field IN ( $in );";

			$records = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ), OBJECT_K );

			if ( $this->is_comma_decimal() ) {
				$records = array_map( array( $this, 'sanitize_amounts' ), $records );
			}

			return $records;
		}

		/**
		 * Return a list of all distinct IDs based on a particular field.
		 *
		 * @global 	WPDB      $wpdb
		 * @param 	string    $field The distinct field we are retrieving.
		 * @param 	int|int[] $donation_id A single donation ID or an array of IDs.
		 * @return  Object
		 * @access  public
		 * @since   1.4.0
		 */
		public function get_distinct_ids( $field, $id, $where_field = 'campaign_id' ) {
			global $wpdb;

			$select_column = $this->get_sanitized_column( $field );
			$where_column  = $this->get_sanitized_column( $where_field );

			list( $in, $parameters ) = $this->get_in_clause_params( $id );

			$sql = "SELECT DISTINCT $select_column 
                    FROM $this->table_name 
                    WHERE $where_column IN ( $in );";

			return $wpdb->get_col( $wpdb->prepare( $sql, $parameters ) );
		}

		/**
		 * Get an object of all campaign donations associated with one or more donations.
		 *
		 * @uses 	Charitable_Campaign_Donations_DB::get_campaign_donations_by()
		 * @param 	int|int[] $donation_id A single donation ID or an array of IDs.
		 * @return  Object
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_donation_records( $donation_id ) {
			return $this->get_campaign_donations_by( 'donation_id', $donation_id );
		}

		/**
		 * Get the amount donated in a single donation.
		 *
		 * @global  $wpdb
		 * @param 	int|int[] $donation_id A single donation ID or an array of IDs.
		 * @param   int|int[] $campaign Optional. If set, this will only return the total donated to that campaign, or array of campaigns.
		 * @return  decimal
		 * @access  public
		 * @since   1.3.0
		 */
		public function get_donation_amount( $donation_id, $campaign = '' ) {
			global $wpdb;

			list( $in, $parameters ) = $this->get_in_clause_params( $donation_id );

			$where_clause = "donation_id IN ( $in )";

			if ( ! empty( $campaign ) ) {

				list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaign );

				$where_clause .= " AND campaign_id IN ( $campaigns_in )";
				$parameters    = array_merge( $parameters, $campaigns_parameters );

			}

			$sql = "SELECT SUM(amount) 
                    FROM $this->table_name 
                    WHERE $where_clause;";

			$total = $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) );

			if ( $this->is_comma_decimal() ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Get the total amount donated in a single donation.
		 *
		 * @param   int $donation_id
		 * @return  decimal
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_donation_total_amount( $donation_id ) {
			return $this->get_donation_amount( $donation_id );
		}

		/**
		 * Return an array of campaigns donated to in a single donation.
		 *
		 * @global  WPDB $wpdb
		 * @return  object
		 * @access  public
		 * @since   1.2.0
		 */
		public function get_campaigns_for_donation( $donation_id ) {
			global $wpdb;

			$sql = "SELECT DISTINCT campaign_id 
                    FROM $this->table_name 
                    WHERE donation_id = %d;";

			return $wpdb->get_col( $wpdb->prepare( $sql, intval( $donation_id ) ) );
		}

		/**
		 * Get an object of all donations on a campaign.
		 *
		 * @uses 	Charitable_Campaign_Donations_DB::get_campaign_donations_by()
		 * @param   int|int[] $campaign_id
		 * @return  object
		 * @since   1.0.0
		 */
		public function get_donations_on_campaign( $campaign_id ) {
			return $this->get_campaign_donations_by( 'campaign_id', $campaign_id );
		}

		/**
		 * Get total amount donated to a campaign.
		 *
		 * @global  wpdb    $wpdb
		 * @param   int|int[] $campaigns A campaign ID. Optionally, you can pass an array of campaign IDs to get the total of all put together.
		 * @param   boolean $include_all
		 * @return  int
		 * @since   1.0.0
		 */
		public function get_campaign_donated_amount( $campaigns, $include_all = false ) {
			global $wpdb;

			$statuses = $include_all ? array() : charitable_get_approval_statuses();

			list( $status_clause, $status_parameters ) = $this->get_donation_status_clause( $statuses );

			list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaigns );

			$parameters = array_merge( $campaigns_parameters, $status_parameters );

			$sql = "SELECT SUM(amount) cd
                    FROM $this->table_name cd
                    INNER JOIN $wpdb->posts p
                    ON p.ID = cd.donation_id
                    WHERE cd.campaign_id IN ( $campaigns_in )
                    $status_clause;";

			$total = $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) );

			if ( $this->is_comma_decimal() ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Get an array of all donation ids for a campaign.
		 *
		 * @uses 	Charitable_Campaign_Donations_DB::get_distinct_ids()
		 * @param   int $campaign_id
		 * @return  object
		 * @since   1.0.0
		 */
		public function get_donation_ids_for_campaign( $campaign_id ) {
			return $this->get_distinct_ids( 'donation_id', $campaign_id, 'campaign_id' );
		}

		/**
		 * The donor IDs of all who have donated to the given campaign.
		 *
		 * @uses 	Charitable_Campaign_Donations_DB::get_distinct_ids()
		 * @param   int     $campaign_id
		 * @return  object
		 * @since   1.0.0
		 */
		public function get_campaign_donors( $campaign_id ) {
			return $this->get_distinct_ids( 'donor_id', $campaign_id, 'campaign_id' );
		}

		 /**
		  * Return the number of users who have donated to the given campaign.
		  *
		  * @global wpdb      $wpdb
		  * @param  int|int[] $campaign
		  * @param  boolean   $include_all If false, only include approved donations.
		  * @return int
		  * @since  1.0.0
		  */
		public function count_campaign_donors( $campaign, $include_all = false ) {
			global $wpdb;

			$statuses = $include_all ? array() : charitable_get_approval_statuses();

			list( $status_clause, $status_parameters )   = $this->get_donation_status_clause( $statuses );
			list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $campaign );

			$parameters = array_merge( $campaigns_parameters, $status_parameters );

			$sql = "SELECT COUNT( DISTINCT cd.donor_id ) 
                    FROM $this->table_name cd
                    INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
                    WHERE cd.campaign_id IN ( $campaigns_in )
                    $status_clause;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) );
		}

		/**
		 * Return all donations made by a donor.
		 *
		 * @global  wpdb $wpdb
		 * @param   int $donor_id
		 * @param   boolean $distinct_donations
		 * @return  object[]
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_donations_by_donor( $donor_id, $distinct_donations = false ) {
			global $wpdb;

			if ( $distinct_donations ) {
				$select_fields = 'DISTINCT( cd.donation_id ), cd.campaign_id, cd.campaign_name, cd.amount';
			} else {
				$select_fields = 'cd.campaign_donation_id, cd.donation_id, cd.campaign_id, cd.campaign_name, cd.amount';
			}

			$sql = "SELECT $select_fields
                    FROM $this->table_name cd
                    WHERE cd.donor_id = %d;";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $donor_id ), OBJECT_K );

			if ( $this->is_comma_decimal() ) {
				$results = array_map( array( $this, 'sanitize_amounts' ), $results );
			}

			return $results;
		}

		/**
		 * Return total amount donated by a donor.
		 *
		 * @global  wpdb    $wpdb
		 * @param   int     $donor_id
		 * @return  int
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_total_donated_by_donor( $donor_id ) {
			global $wpdb;

			$sql = "SELECT SUM(cd.amount)
                    FROM $this->table_name cd
                    WHERE cd.donor_id = %d;";

			$total = $wpdb->get_var( $wpdb->prepare( $sql, $donor_id ) );

			if ( $this->is_comma_decimal() ) {
				$total = Charitable_Currency::get_instance()->sanitize_database_amount( $total );
			}

			return $total;
		}

		/**
		 * Count the number of donations made by the donor.
		 *
		 * @global  wpdb    $wpdb
		 * @param   int     $donor_id
		 * @param   boolean $distinct_donations     If true, will only count unique donations.
		 * @return  int
		 * @access  public
		 * @since   1.0.0
		 */
		public function count_donations_by_donor( $donor_id, $distinct_donations = false ) {
			global $wpdb;

			$count = $distinct_donations ? 'DISTINCT donation_id' : 'donation_id';

			$sql = "SELECT COUNT( $count )
                    FROM $this->table_name
                    WHERE donor_id = %d;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $donor_id ) );
		}

		/**
		 * Count the number of campaigns that the donor has supported.
		 *
		 * @global  wpdb    $wpdb
		 * @return  int
		 * @access  public
		 * @since   1.0.0
		 */
		public function count_campaigns_supported_by_donor( $donor_id ) {
			global $wpdb;

			$sql = "SELECT COUNT( DISTINCT campaign_id )
                    FROM $this->table_name
                    WHERE donor_id = %d;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $donor_id ) );
		}

		/**
		 * Return a set of donations, filtered by the provided arguments.
		 *
		 * @param   array $args
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_donations_report( $args ) {
			global $wpdb;

			$parameters        = array();
			$sql_order         = $this->get_orderby_clause( $args, 'ORDER BY p.post_date ASC' );
			$sql_where         = '';
			$sql_where_clauses = array();

			if ( isset( $args['campaign_id'] ) ) {

				list( $campaigns_in, $campaigns_parameters ) = $this->get_in_clause_params( $args['campaign_id'] );

				$sql_where_clauses[] = "cd.campaign_id IN ( $campaigns_in )";
				$parameters          = array_merge( $parameters, $campaigns_parameters );

			}

			if ( isset( $args['status'] ) ) {

				$sql_where_clauses[] = 'p.post_status = %s';
				$parameters[]        = $args['status'];

			} else {
				// if ALL: select all valid statuses
				$statuses            = array_keys( charitable_get_valid_donation_statuses() );
				$in                  = $this->get_in_clause( $statuses, '%s' );
				$sql_where_clauses[] = "p.post_status IN ( $in )";
				$parameters          = array_merge( $parameters, $statuses );
			}

			if ( isset( $args['start_date'] ) ) {

				$sql_where_clauses[] = 'p.post_date >= %s';
				$parameters[]        = $args['start_date'];

			}

			if ( isset( $args['end_date'] ) ) {
				$sql_where_clauses[] = 'p.post_date <= %s';
				$parameters[]        = $args['end_date'];
			}

			if ( ! empty( $sql_where_clauses ) ) {
				$sql_where = 'WHERE ' . implode( ' AND ', $sql_where_clauses );
			}

			/* This is our base SQL query */
			$sql = "SELECT cd.donation_id, cd.campaign_id, cd.campaign_name, cd.amount, d.email, d.first_name, d.last_name, p.post_date, p.post_content, p.post_status
                    FROM $this->table_name cd
                    INNER JOIN {$wpdb->prefix}charitable_donors d
                    ON d.donor_id = cd.donor_id
                    INNER JOIN $wpdb->posts p
                    ON p.ID = cd.donation_id
                    $sql_where
                    $sql_order";

			if ( ! empty( $parameters ) ) {
				$sql = $wpdb->prepare( $sql, $parameters );
			}

			$results = $wpdb->get_results( $sql );

			if ( $this->is_comma_decimal() ) {
				$results = array_map( array( $this, 'sanitize_amounts' ), $results );
			}

			return $results;
		}

		/**
		 * Return a count and sum of donations for a given period.
		 *
		 * @global  WPDB $wpdb
		 * @param   string $period
		 * @param   string[] $statuses
		 * @return  array
		 * @access  public
		 * @since   1.2.0
		 */
		public function get_donations_summary_by_period( $period = '', $statuses = array() ) {
			global $wpdb;

			if ( empty( $statuses ) ) {
				$statuses = charitable_get_approval_statuses();
			}

			list( $status_clause, $parameters ) = $this->get_donation_status_clause( $statuses );

			array_unshift( $parameters, $period );

			$sql = "SELECT COALESCE( SUM( cd.amount ), 0 ) as amount, COUNT( cd.donation_id ) as count
                    FROM {$wpdb->prefix}charitable_campaign_donations cd
                    INNER JOIN $wpdb->posts p ON p.ID = cd.donation_id
                    WHERE p.post_date LIKE %s
                    $status_clause;";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $parameters ) );

			$result = $results[0];

			if ( $this->is_comma_decimal() ) {
				$result = $this->sanitize_amounts( $result );
			}

			return $result;
		}

		/**
		 * Returns the orderby clause.
		 *
		 * @param   array $args
		 * @param   string $default
		 * @return  string
		 * @access  public
		 * @since   1.3.4
		 */
		public function get_orderby_clause( $args, $default = '' ) {
			if ( ! isset( $args['orderby'] ) && ! isset( $args['order'] ) ) {
				return $default;
			}

			$orderby = isset( $args['orderby'] ) ? $args['orderby'] : 'ID';
			$order   = isset( $args['order'] ) && in_array( $args['order'], array( 'ASC', 'DESC' ) ) ? $args['order'] : 'ASC';

			switch ( $orderby ) {
				case 'date' :
					$ret = 'ORDER BY p.post_date ';
					break;

				default :
					$ret = 'ORDER BY cd.campaign_donation_id ';
			}

			$ret .= $order;

			return $ret;
		}

		/**
		 * Count donations by status.
		 *
		 * @param   string|string[] $statuses
		 * @return  int
		 * @access  public
		 * @since   1.0.0
		 */
		public function count_donations_by_status( $statuses ) {
			global $wpdb;

			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			list( $status_clause, $parameters ) = $this->get_donation_status_clause( $statuses );

			$sql = "SELECT COUNT( * )
                    FROM {$wpdb->prefix}charitable_campaign_donations cd
                    INNER JOIN $wpdb->posts po ON po.ID = cd.donation_id
                    WHERE 1 = 1
                    $status_clause;";

			return $wpdb->get_var( $wpdb->prepare( $sql, $parameters ) );
		}

		/**
		 * Returns the donation status clause.
		 *
		 * @param   string[] $statuses
		 * @return  string
		 * @access  private
		 * @since   1.0.0
		 */
		private function get_donation_status_clause( $statuses = array() ) {
			if ( empty( $statuses ) ) {
				return array( '', array() );
			}

			$statuses = array_filter( $statuses, 'charitable_is_valid_donation_status' );

			$in = $this->get_in_clause( $statuses, '%s' );

			$sql = "AND p.post_status IN ( $in )";

			return array( $sql, $statuses );
		}

		/**
		 * Returns an array containing the placeholders and sanitized parameters for an IN clause.
		 *
		 * @param   int|int[] $list
		 * @return  array
		 * @access  private
		 * @since   1.4.0
		 */
		private function get_in_clause_params( $list ) {
			if ( ! is_array( $list ) ) {
				$list = array( $list );
			}

			/* Filter out any non-numeric campaign IDs, then convert to int */
			$list = array_filter( $list, 'is_numeric' );
			$list = array_map( 'intval', $list );

			$in = $this->get_in_clause( $list, '%d' );

			return array( $in, $list );
		}

		/**
		 * Given an array and a placeholder, this returns a string with the correct number of placeholders.
		 *
		 * @param   array $list
		 * @param   string $placeholder
		 * @return  string
		 * @access  private
		 * @since   1.3.4
		 */
		private function get_in_clause( $list, $placeholder = '%s' ) {
			$placeholders = array_fill( 0, count( $list ), $placeholder );

			return implode( ', ', $placeholders );
		}

		/**
		 * Checks whether we are using commas for decimals.
		 *
		 * @return  boolean
		 * @access  private
		 * @since   1.3.0
		 */
		private function is_comma_decimal() {
			if ( ! isset( $this->comma_decimal ) ) {
				$this->comma_decimal = Charitable_Currency::get_instance()->is_comma_decimal();
			}

			return $this->comma_decimal;
		}

		/**
		 * Sanitize amounts retrieved from the database.
		 *
		 * @param   Object $campaign_donation
		 * @return  Object
		 * @access  private
		 * @since   1.3.0
		 */
		private function sanitize_amounts( $campaign_donation ) {
			$campaign_donation->amount = Charitable_Currency::get_instance()->sanitize_database_amount( $campaign_donation->amount );
			return $campaign_donation;
		}

		/**
		 * Return a sanitized column name.
		 *
		 * @param 	string $field
		 * @return  string
		 * @access  private
		 * @since   1.4.0
		 */
		private function get_sanitized_column( $field ) {

			switch ( $field ) {

				case 'campaign' :
				case 'campaign_id' : 
					$column = 'campaign_id';
					break;

				case 'donation' : 
				case 'donation_id' : 
					$column = 'donation_id';
					break;

				case 'donor' : 
				case 'donor_id' : 
					$column = 'donor_id';
					break;

				default : 
					charitable_get_deprecated()->doing_it_wrong(
						__METHOD__,
						__( 'Field expected to be `campaign`, `campaign_id`, `donation`, `donation_id`, `donor` or `donor_id`.', 'charitable' ),
						'1.4.0'
					);

					$column = false;
			}

			return $column;
		}

		/**
		 * Returns the campaign ID clause.
		 *
		 * @param   int|int[] $campaigns
		 * @return  array
		 * @access  private
		 * @since   1.0.0
		 *
		 * @deprecated 1.4.0
		 */
		private function get_campaigns_clause( $campaigns ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'Charitable_Campaign_Donations_DB::get_in_clause_params()'
			);

			return $this->get_in_clause_params( $campaigns );		
		}		
	}

endif;
