<?php
/**
 * @var WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Library\Marketing\Boxes\MarketingBoxes $boxes
 * @var WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Tracker\OptInPage $tracking_nag
 */

use WPDesk\FlexibleSubscriptions\Vendor\WPDesk\Tracker\OptInPage;

defined( 'ABSPATH' ) || exit;
?>
<style>
.wrap {
	display: flex
}

#marketing-page-wrapper {
	max-width: 1100px;
}
/* Connect */
#wpdesk_tracker_connect {
	margin-bottom: auto;
}
#wpdesk_tracker_connect span.wpdesk-logo {
	background-repeat: no-repeat;
	background-size: 215px 62px;
	display: block;
	height: 62px;
	margin: 0 auto;
	width: 215px;
}

@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
	#wpdesk_tracker_connect span.wpdesk-logo {
		background-size: 215px 62px;
	}
}

#wpdesk_tracker_connect img.logo {
	width: 215px;
}

#wpdesk_tracker_connect .message p {
	font-size: 14px;
}

#wpdesk_tracker_connect .plugin-card-bottom {
	border-bottom: 1px solid #ddd;
	margin-bottom: 10px;
}

#wpdesk_tracker_connect.plugin-card {
	width: auto
}

#wpdesk_tracker_connect .button-allow {
	float: right;
}

#wpdesk_tracker_connect .permissions .trigger {
	display: block;
	font-size: .9em;
	text-align: center;
	margin-bottom: 10px;
	text-decoration: none;
}

#wpdesk_tracker_connect .permissions .permissions-details {
	height: 0;
	overflow: hidden;
	margin: 0;
}

#wpdesk_tracker_connect .permissions.open .permissions-details {
	height: auto;
	padding: 10px 20px;
}

#wpdesk_tracker_connect .permissions ul {
	margin: 0;
}

#wpdesk_tracker_connect .permissions ul li {
	margin-bottom: 12px;
}

#wpdesk_tracker_connect .permissions ul li i.dashicons {
	float: left;
	font-size: 40px;
	width: 40px;
	height: 40px;
}

#wpdesk_tracker_connect .permissions ul li div {
	margin-left: 55px;
}

#wpdesk_tracker_connect .permissions ul li div span {
	font-weight: bold;
}

#wpdesk_tracker_connect .permissions ul li div p {
	margin: 2px 0 0 0;
}

#wpdesk_tracker_connect .terms {
	font-size: .9em;
	padding: 5px;
}

#wpdesk_tracker_connect .terms a {
	color: #999;
}
</style>
<div class="wrap">
	<div id="marketing-page-wrapper">
		<?php echo wp_kses_post( $boxes->get_boxes()->get_all() ); ?>
	</div>
<?php if ( $tracking_nag instanceof OptInPage ) { ?>
	<?php $tracking_nag->output(); ?>
<?php } ?>
</div>
