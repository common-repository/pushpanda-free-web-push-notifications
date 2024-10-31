<?php

defined( 'ABSPATH' ) or die('Thanks for visting PushPanda.io wordpress plugin.');

/**
 * Plugin Name: PushPanda.io - Free Web Push Notifications
 * Plugin URI: https://www.pushpanda.io/
 * Description: Free web push notifications for destop and mobile browsers. Simply enable the plugin and start sending push messages to your subscribers. See more details on <a href="https://www.pushpanda.io/">PushPanda.io</a>
 * Version: 1.1.0
 * Author: PushPanda.io
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: pushpanda
 */

define( 'PUSHPANDA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PUSHPANDA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PUSHPANDA_PLUGIN_WORKER', "PushPandaWorker.js" );

/* SETTINGS */
$pushpanda_embed_code = '
    <script type="text/javascript" data-cfasync="false">
        var _pushpanda = _pushpanda || [];
        _pushpanda.push([\'_project\', \'PUSPANDAPROJECTID\']);
        _pushpanda.push([\'_path\', \'PUSHPANDAPATH\']);
        _pushpanda.push([\'_worker\', "PUSHPANDAWORKER"]);

        (function (p,u,s,h,p,a,n,d,a) {
            var p = document.createElement(\'script\');
            p.src = \'//cdn.pushpanda.io/sdk/sdk.js\';
            p.type = \'text/javascript\';
            p.async = \'true\';
            u = document.getElementsByTagName(\'script\')[0];
            u.parentNode.insertBefore(p, u);
        })();
    </script>
';

$pushpanda_sw_found = false;

/* ACTIONS */
add_action ( 'wp_head', 'pushpanda_embed' );
add_action( 'admin_menu', 'pushpanda_admin_menu' );
add_action( 'admin_init', 'pushpanda_admin_init' );
add_action( 'admin_notices','pushpanda_nosettings');
add_action( 'admin_enqueue_scripts', 'pushpanda_admin_styles');

/* METHODS */
// set css styles
function pushpanda_admin_styles() {
    wp_enqueue_style( 'pushpanda-admin-icons', plugin_dir_url( __FILE__ ) . 'assets/css/pushpanda.css', false, "3");
}


// add settings link to admin menu
function pushpanda_admin_menu() {
    add_options_page('PushPanda.io', 'PushPanda.io', 'create_users', 'pushpanda_settings', 'pushpanda_plugin_settings');
}

// whitelist settings
function pushpanda_admin_init(){
    register_setting('pushpanda_settings','pushpanda_project_id');
}

/* FRONTEND OUTPUT */
function pushpanda_embed(){
    global $pushpanda_embed_code;
    $project_id = get_option('pushpanda_project_id');

    if(strlen($project_id)==36){
        $pushpanda_embed_code = str_replace('PUSPANDAPROJECTID', $project_id, $pushpanda_embed_code);
        $pushpanda_embed_code = str_replace('PUSHPANDAWORKER', PUSHPANDA_PLUGIN_WORKER, $pushpanda_embed_code);
        echo str_replace('PUSHPANDAPATH', PUSHPANDA_PLUGIN_URL."sdk/", $pushpanda_embed_code);
    }
}

function turn_off_reject_unsafe_urls($args) {
    $args['reject_unsafe_urls'] = false;
    return $args;
}

function check_sw_response() {
	$url = PUSHPANDA_PLUGIN_URL . "sdk/" . PUSHPANDA_PLUGIN_WORKER;
	
	$args = array(
		'limit_response_size' => 150 * KB_IN_BYTES,
		'sslverify'			  => false,
	);
	
	$response = wp_safe_remote_get( $url, $args );
	
	if ( WP_Http::OK !== wp_remote_retrieve_response_code( $response ) ) {
		return false;
	} else {
		return true;
	}
}


/* BACKEND OUTPUT */
function pushpanda_plugin_settings() {
    echo '<div class="pp-wrapper">';?>
    <div class="pp-header">
        <div class="pp-container">
            <div class="pp-logo"><img src="<?php echo PUSHPANDA_PLUGIN_URL."assets/pushpanda_logo_dark.svg" ?>" alt="PushPanda.io Logo"></div>
            <div class="pp-nav"><a href="mailto:support@pushpanda.io?subject=Wordpress%20Plugin">Contact Support</a></div>
        </div>
    </div>
    <div class="clear"></div>
    <div class="pp-container pp-mt-15 pp-mb">
        <div class="pp-grid postbox">
            <div class="pp-box">
                <div class="pp-inner">
                    <h3>Already have an Account?</h3>
                    <hr>
                    <p><?php echo sprintf(__('To enable PushPanda Push Notifications on your Wordpress site, you will need to copy your <strong>PROJECT ID</strong> from the <a href="https://app.pushpanda.io/websites" target="_blank">Projects Page</a> in your PushPanda Account.','pushpanda')) ?></p>
                    <p><a href="https://app.pushpanda.io/websites" target="_blank" class="button button-secondary">Go to your <span class="pushpanda-icon"></span> Account</a></p>
                    <form method="post" action="options.php">
                        <?php settings_fields( 'pushpanda_settings' ); ?>
                        <?php do_settings_sections( 'pushpanda_options' ); ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php echo sprintf(__('PushPanda.io Project ID','pushpanda')) ?></th>
                                <td><input type="text" name="pushpanda_project_id" value="<?php echo esc_attr( get_option('pushpanda_project_id') ); ?>" /></td>
                            </tr>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
            <div class="pp-box">
                <div class="pp-inner">
                    <h3>Create FREE Account</h3>
                    <hr>
                    <p><?php echo sprintf(__('Get your free Account and experience the power of browser push notifications. Register <a href="https://app.pushpanda.io/register" target="_blank">HERE</a> and start sending web push notifications to your subscribers in a few minutes!','pushpanda')) ?><br>&nbsp;</p>
                    <p align="center"><a href="https://app.pushpanda.io/register" target="_blank"><img src="<?php echo PUSHPANDA_PLUGIN_URL."assets/pushpanda_web_push_free.png" ?>" width="auto" height="200"></a></p>
                </div>
            </div>
        </div>
    </div>
    <?php
    echo '</div>';
}

function pushpanda_nosettings(){
    if (!is_admin())
        return;
	
	global $pushpanda_sw_found;
    $project_id = get_option("pushpanda_project_id");
	
    if (!$project_id){
        echo "<div class='notice notice-warning is-dismissible'><p><strong>PushPanda.io is almost ready.</strong> You must <a target=\"_blank\" href=\"https://app.pushpanda.io/websites\">enter your Project ID</a> to work.</p></div>";
    } else if (strlen($project_id) != 36) {
		echo "<div class='notice notice-error is-dismissible'><p><strong>PushPanda.io Project ID not valid.</strong> You must <a target=\"_blank\" href=\"https://app.pushpanda.io/websites\">enter a valid Project ID</a> to work.</p></div>";
	} else {
		$pushpanda_sw_found = check_sw_response();
		if (!$pushpanda_sw_found) {
			echo "<div class='notice notice-error is-dismissible'><p><strong>PushPanda.io ServiceWorker not accessible.</strong> Please ensure that the installed PushPanda ServiceWorker Script (<a href=\"" . PUSHPANDA_PLUGIN_URL . "sdk/" . PUSHPANDA_PLUGIN_WORKER . "\" target=\"_blank\">TEST HERE</a>) is publicly accessible.</p></div>";
		}
	}
}


?>
