<?php
/**
 * Plugin Name: MU Training Schedule
 * Plugin URI: https://www.marshall.edu
 * Description: Training Calendar Listing from Marshall Site
 * Author: John Cummings, Christopher McComas
 * Version: 2.0
 *
 * Notes: This plugin was initially developed by John Cummings in 2012 and has been minimally upgraded since.
 *
 * @package mutrain
 */

$date = strtotime( 'now' );

/**
 * MU Training Shortcode
 *
 * @param array $atts The array of attributes accepted with the shortcode.
 */
function mtrain( $atts ) {
	$data = shortcode_atts(
		array(
			'cname' => 'foo',
		),
		$atts
	);

	$cname = rawurlencode( $data['cname'] );

	echo plugin_dir_path( __FILE__ ) . 'config.php';
	$config          = include plugin_dir_path( __FILE__ ) . 'config.php';
	$server_name     = $config['server'];
	$connection_info = array(
		'Database' => $config['database'],
		'UID'      => $config['user'],
		'PWD'      => $config['password'],
	);

	$conn = sqlsrv_connect( $server_name, $connection_info );

	if ( 'REQUIRED COVID-19 Saliva Testing for Staff and Faculty' === $data['cname'] ) {
		$sql = "SELECT * FROM Courses WHERE Available = 1 AND Date >= CAST(GETDATE() AS date) AND CourseName = '" . $data['cname'] . "' ORDER BY Date ASC, StartTime ASC";
	} else {
		$sql = "SELECT * FROM Courses WHERE Available = 1 AND Date >= CAST(GETDATE() AS date) AND CourseName = '" . $data['cname'] . "' ORDER BY Date ASC";
	}

	$stmt = sqlsrv_query( $conn, $sql, array(), array( 'Scrollable' => 'static' ) );

	if ( ! $stmt ) {
		die( esc_attr( print_r( sqlsrv_errors(), true ) ) ); // phpcs:ignore
	}

	$row_count = sqlsrv_num_rows( $stmt );

	if ( ! sqlsrv_num_rows( $stmt ) ) {
		if ( 'Banner HR Introduction' === $data['cname'] ) {
			echo '<p>For Banner HR training information, please contact Mary Chapman at <a href="mailto:chapmanm@marshall.edu">chapmanm@marshall.edu</a></p>';
		} elseif ( 'Banner Basic Purchasing' === $data['cname'] ) {
			echo '<p>For Banner Basic Purchasing training information, please contact Tracey Brown-Dolinski at <a href="mailto:browndolinsk@marshall.edu">browndolinsk@marshall.edu</a></p>';
		} elseif ( 'Banner Schedule Entry Training' === $data['cname'] ) {
			echo '<p>Banner Basic Navigation training is required to be completed prior to Training. Please email at <a href="mailto:registrar@marshall.edu"> registrar@marshall.edu</a></p>';
		} elseif ( 'Banner Registration Training' === $data['cname'] ) {
			echo '<p>Restricted to Dean’s office staff. Banner Basic Navigation training is required to be completed prior to Training. Please email at <a href="mailto:registrar@marshall.edu"> registrar@marshall.edu</a></p>';
		} elseif ( 'Banner Changing or Adding Student Majors/Minors' === $data['cname'] ) {
			echo '<p>Restricted to Dean’s office staff. Banner Basic Navigation training is required to be completed prior to Training. Please email at <a href="mailto:registrar@marshall.edu"> registrar@marshall.edu</a></p>';
		} elseif ( 'Banner Student Registration Training' === $data['cname'] ) {
			echo '<p>Banner Basic Navigation training is required to be completed prior to Training Please email at <a href="mailto:registrar@marshall.edu"> registrar@marshall.edu</a></p>';
		} else {
			echo '<p>No ' . esc_attr( $data['cname'] ) . ' training sessions are currently scheduled. Please check back.</p>';
		}
	} else {
		echo '<h3>Upcoming Sessions</h3>';
		echo '<div class="flex flex-col border-gray-100 border border-t border-b rounded my-6">';

		while ( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC ) ) {

			$reg_sql = 'SELECT * FROM Registrations WHERE CourseNo = ' . $row['CourseNo'];

			$reg = sqlsrv_query( $conn, $reg_sql, array(), array( 'Scrollable' => 'static' ) );

			$seats_left = $row['Seats'] - sqlsrv_num_rows( $reg );

			if ( 'REQUIRED COVID-19 Testing for Staff and Faculty' === $data['cname'] || 'REQUIRED COVID-19 Saliva Testing for Staff and Faculty' === $data['cname'] ) {
				$location = '<span class="inline bg-red-500 text-white px-2 py-1 rounded font-semibold uppercase text-xs mr-2">Note Location</span>';
			} else {
				$location = '';
			}

			if ( $seats_left < 0 ) {
				$seats_left = 0;
			}

			echo '<div class="border-b border-gray-100 flex flex-row items-start py-4 px-4 lg:px-6">';
			echo '<div class="flex-col flex w-12 lg:w-16 mx-auto">';
			echo '<div class="bg-green text-white text-xs lg:text-sm font-semibold uppercase py-1 rounded-t text-center">' . esc_attr( date_format( $row['Date'], 'M' ) ) . '</div>';
			echo '<div class="bg-gray-100 text-sm lg:text-base font-bold uppercase py-1 rounded-b text-center">' . esc_attr( date_format( $row['Date'], 'd' ) ) . '</div>';
			echo '</div>';
			echo '<div class="ml-4 lg:ml-6 flex-1">';
			echo '<div class="">';

			if ( $seats_left > 0 ) {
				echo '<a href="/human-resources/training/course-registration/?cnumber=' . esc_attr( $row['CourseNo'] ) . '" class="font-semibold">' . esc_attr( $row['CourseName'] ) . '</a>';
			} else {
				echo '<span class="font-semibold">' . esc_attr( $row['CourseName'] ) . '</span>';
			}

			echo '<div class="text-sm">' . wp_kses_post( $location ) . '<span class="font-semibold">Location:</span> ' . esc_attr( $row['Location'] ) . '</div>';
			echo '<div class="text-sm">' . esc_attr( date_format( $row['Date'], 'F j' ) ) . ', ' . esc_attr( date_format( $row['StartTime'], 'g:ia' ) ) . ' - ' . esc_attr( date_format( $row['EndTime'], 'g:ia' ) ) . ' &middot; <span class="font-semibold">' . esc_attr( $seats_left ) . '</span> spots remaining</div> <span class="hidden">Seats taken: ' . esc_attr( sqlsrv_num_rows( $reg ) ) . '</span>';
			echo '<div class="text-sm"><span class="font-semibold">Instructor:</span> ' . esc_attr( $row['Instructor'] ) . ' (<a href="https://netapps.marshall.edu/hr-training/td/Secure/Training_Registrants_Page.asp?CourseNo=' . esc_attr( $row['CourseNo'] ) . '">Instructor Access</a>)</div>';

			if ( ! empty( $row['SF5'] ) ) {
				echo '<div class="text-sm font-semibold text-red-500"><a href="' . esc_url( $row['SF5'] ) . '" class="text-red-500">Download Required Documents</a></div>';
			}

			if ( ! empty( $row['CourseDescrip'] ) ) {
				$desc = str_replace( '(SECOND DOSE ONLY)', '<span class="text-red-500 font-bold">(SECOND DOSE ONLY)</span>', $row['CourseDescrip'] );
				echo '<div class="my-4">' . wp_kses_post( $desc ) . '</div>';
			}
			echo '</div>';

			if ( $seats_left > 0 ) {
				echo '<div class="mt-6"><a href="/human-resources/training/course-registration/?cnumber=' . esc_attr( $row['CourseNo'] ) . '" class="btn btn-green">Register</a></div>';
			} else {
				echo '<div class="mt-6 btn bg-gray-300 text-gray-500 cursor-not-allowed">Course Full</div>';
			}

			echo '</div>';
			echo '</div>';
		}

		echo '</div>';
	}

	sqlsrv_free_stmt( $stmt );
}

add_shortcode( 'training', 'mtrain' );
add_shortcode( 'mu_training', 'mtrain' );
