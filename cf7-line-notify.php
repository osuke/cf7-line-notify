<?php
/**
 * Plugin Name: LINE Notify for Contact Form 7
 * Version: 0.1
 * Description: This plugin send a message to LINE via LINE Notify when Contact Form 7 plugin send a mail.
 * Author: Osuke Uesugi
 * Author URI: https://github.com/OsukeUesugi
 * Plugin URI: https://github.com/OsukeUesugi/cf7-line-notify
 * License: MIT
 */
class Cf7_Line_Notify
{
  function __construct() {
    register_activation_hook( __FILE__, array( $this, 'check_dependency') );
    add_action( 'admin_menu', array( $this, 'add_plugin_menu' ) );
    add_action( 'wpcf7_mail_sent', array( $this, 'line_notify' ) );
    add_action( 'admin_init', array( $this, 'plugin_menu_init' ) );
  }

  public function check_dependency() {
    $active_plugins = get_option( 'active_plugins' );
    if ( !array_search( 'contact-form-7/wp-contact-form-7.php', $active_plugins ) ) {
      echo 'Please activate Contact form 7 plugin before use this plugin.';
      exit();
    }
  }

  public function plugin_menu_init() {
    if( isset( $_POST['cf7-line-notify'] ) && $_POST['cf7-line-notify'] ) {
      if( check_admin_referer( 'cf7-line-notify-nonce-key', 'cf7-line-notify' ) ) {

        if( isset( $_POST['cf7-line-notify-token'] ) && $_POST['cf7-line-notify-token'] ) {
          update_option( 'cf7-line-notify-token', $_POST['cf7-line-notify-token'] );
        } else {
          update_option( 'cf7-line-notify-token', '' );
        }
      }
    }
  }

  public function add_plugin_menu() {
    add_options_page(
      'CF7 LINE Notify',
      'LINE Notify for Contact Form 7 ',
      'administrator',
      'cf7-line-notify',
      array($this, 'display_plugin_admin_page')
    );
  }

  function display_plugin_admin_page() {
    $access_token = stripslashes( get_option( 'cf7-line-notify-token' ) );
?>
      <div class="wrap">
        <h2>Contact form 7 LINE Notify</h2>
        <form action="" method="post">
        <table class="form-table">
        <tr>
        <th scope="row">ACCESS TOKEN</th>
        <td>
        <input id="" class="" name="cf7-line-notify-token" value="<?php echo $access_token; ?>">
        </td>
        </tr>
        </table>
        <p><input type="submit" value="SAVE" class="button button-primary button-large" /></p>
<?php
  wp_nonce_field( 'cf7-line-notify-nonce-key', 'cf7-line-notify' );
?>
        </form>
      </div>
<?php
  }

  public function line_notify( $contact_form ) {
    $access_token = stripslashes( get_option( 'cf7-line-notify-token' ) );

    if ( empty( $access_token ) ) return;

    $submission = WPCF7_Submission::get_instance();
    $headers = 'Authorization: Bearer ' . $access_token;

    if ( $submission ) {
        $posted_data = $submission->get_posted_data();
        $mail_properties = $contact_form->get_properties();
        $mail_body = wpcf7_mail_replace_tags($mail_properties['mail']['body']);
        $data = array(
          'headers' => $headers,
          'body' => array( 'message' => $mail_body )
        );
        wp_remote_post('https://notify-api.line.me/api/notify', $data);
    }
  }
}

new Cf7_Line_Notify();
