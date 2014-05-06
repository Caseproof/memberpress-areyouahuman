<?php
/*
Plugin Name: MemberPress Are You A Human
Plugin URI: http://memberpress.com
Description: Puts an "Are You A Human" game on each registration page to prevent spam signups
Version: 1.0.4
Author: Caseproof, LLC
Author URI: http://caseproof.com
Text Domain: memberpress
Copyright: 2004-2014, Caseproof, LLC
*/

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if( is_plugin_active('memberpress/memberpress.php') ) {

  /**
   * Returns current plugin version.
   *
   * @return string Plugin version
   */
  function mpayah_plugin_info($field) {
    static $plugin_folder, $plugin_file;

    if( !isset($plugin_folder) or !isset($plugin_file) ) {
      if( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

      $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
      $plugin_file = basename( ( __FILE__ ) );
    }

    if(isset($plugin_folder[$plugin_file][$field]))
      return $plugin_folder[$plugin_file][$field];

    return '';
  }

  // Plugin Information from the plugin header declaration
  define('MPAYAH_VERSION', mpayah_plugin_info('Version'));
  define('MPAYAH_PLUGIN_SLUG', plugin_basename(__FILE__));
  define('MPAYAH_PLUGIN_NAME', dirname(MPAYAH_PLUGIN_SLUG));
  define('MPAYAH_PATH', WP_PLUGIN_DIR.'/'.MPAYAH_PLUGIN_NAME);
  define('MPAYAH_URL', plugins_url('/'.MPAYAH_PLUGIN_NAME));
  define('MPAYAH_EDITION', 'memberpress-areyouahuman');

  class MeprAreYouAHuman {
    public $ayah;
    public $key_strs;
    public $keys;

    public function __construct() {
      $this->key_strs = (object)array('publisher'=>'mepr-ayah-publisher-key',
                                      'scoring'=>'mepr-ayah-scoring-key');
      $this->keys = (object)array('publisher'=>get_option($this->key_strs->publisher),
                                  'scoring'=>get_option($this->key_strs->scoring));

      foreach( (array)$this->key_strs as $key => $str ) {
        $this->keys->{$key} = ( empty($this->keys->{$key}) ? '' : $this->keys->{$key} );
        $this->keys->{$key} = ( isset($_POST[$str]) ? $_POST[$str] : $this->keys->{$key} );
      }

      add_action('mepr-process-options', array($this,'store_options'));
      add_action('mepr_display_general_options', array($this,'display_options'));

      if( !empty($this->keys->publisher) and !empty($this->keys->scoring) ) {
        define( 'AYAH_PUBLISHER_KEY', $this->keys->publisher );
        define( 'AYAH_SCORING_KEY', $this->keys->scoring );

        require_once( "ayah/ayah.php" );
        $this->ayah = new AYAH();

        add_action('mepr-user-signup-fields', array($this,'display'), 100); // ensure it shows last
        add_filter('mepr-validate-signup', array($this,'validate'));
      }
    }

    public function display_options() {
      ?>
      <h3><?php _e('Are You A Human (Captcha Alternative)', 'memberpress-areyouahuman'); ?>
          <?php MeprAppHelper::info_tooltip( 'mepr-areyouahuman',
                                             __('Are You A Human', 'memberpress-areyouahuman'),
                                             sprintf(__('Go to %1$sAreYouAHuman.com%2$s and register for a free account to get your publisher and scoring keys', 'memberpress-areyouahuman'), '<a href="http://areyouahuman.com">', '</a>') ); ?>
      </h3>
      <div class="mepr-options-pane">
        <table>
          <tr>
            <td>
              <label for="<?php echo $this->key_strs->publisher; ?>"><?php _e('Publisher Key:', 'memberpress-areyouahuman'); ?></label>
            </td>
            <td>
              <input type="text" id="<?php echo $this->key_strs->publisher; ?>" name="<?php echo $this->key_strs->publisher; ?>" class="regular-text" value="<?php echo stripslashes($this->keys->publisher); ?>" />
            </td>
          </tr>
          <tr>
            <td>
              <label for="<?php echo $this->key_strs->scoring; ?>"><?php _e('Scoring Key:', 'memberpress-areyouahuman'); ?></label>
            </td>
            <td>
              <input type="text" id="<?php echo $this->key_strs->scoring; ?>" name="<?php echo $this->key_strs->scoring; ?>" class="regular-text" value="<?php echo stripslashes($this->keys->scoring); ?>" />
            </td>
          </tr>
        </table>
      </div>
      <?php
    }

    public function store_options() {
      foreach( (array)$this->key_strs as $key ) {
        if(isset($_POST[$key])) { update_option($key, $_POST[$key]); }
      }
    }

    public function display() {
      echo $this->ayah->getPublisherHTML();
    }

    public function validate($errors=array()) {
      if(array_key_exists('wp-submit', $_POST))
      {
        // Use the AYAH object to see if the user passed or failed the game.
        $score = $this->ayah->scoreResult();

        if(!$score)
          $errors[] = __("Sorry, but we were not able to verify you as human. Please try again.", 'memberpress-areyouahuman');
      }

      return $errors;
    }
  }

  new MeprAreYouAHuman();

  require_once(MPAYAH_PATH . '/MpayahUpdateController.php');
  MpayahUpdateController::load_hooks(); 
}

