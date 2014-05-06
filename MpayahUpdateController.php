<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MpayahUpdateController {
  public static function load_hooks() {
    add_filter('pre_set_site_transient_update_plugins', 'MpayahUpdateController::queue_update');
    add_filter('plugins_api', 'MpayahUpdateController::plugin_info', 937801, 3);
  }

  public static function queue_update($transient, $force=false) {
    $mepr_options = MeprOptions::fetch();

    if( $force or ( false === ( $update_info = get_site_transient('mpayah_update_info') ) ) )
    {
      if(empty($mepr_options->mothership_license))
      {
        // Just here to query for the current version
        $args = array();
        if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
          $args['edge'] = 'true';

        $version_info = self::send_mothership_request( "/versions/latest/".MPAYAH_EDITION, $args );
        $curr_version = $version_info['version'];
        $download_url = '';
      }
      else
      {
        try {
          $domain = urlencode(MeprUtils::site_domain());
          $args = compact('domain');

          if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
            $args['edge'] = 'true';

          $license_info = self::send_mothership_request("/versions/info/".MPAYAH_EDITION."/{$mepr_options->mothership_license}", $args);
          $curr_version = $license_info['version'];
          $download_url = $license_info['url'];
          set_site_transient( 'mpayah_license_info',
                              $license_info,
                              MeprUtils::hours(12) );
        }
        catch(Exception $e)
        {
          try
          {
            // Just here to query for the current version
            $args = array();
            if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
              $args['edge'] = 'true';

            $version_info = self::send_mothership_request("/versions/latest/".MPAYAH_EDITION, $args);
            $curr_version = $version_info['version'];
            $download_url = '';
          }
          catch(Exception $e)
          {
            if(isset($transient->response[MPAYAH_PLUGIN_SLUG]))
              unset($transient->response[MPAYAH_PLUGIN_SLUG]);

            return $transient;
          }
        }
      }

      set_site_transient( 'mpayah_update_info',
                          compact( 'curr_version', 'download_url' ),
                          MeprUtils::hours(12) );
    }
    else
      extract( $update_info );

    if(isset($curr_version) and version_compare($curr_version, MPAYAH_VERSION, '>'))
    {
      $transient->response[MPAYAH_PLUGIN_SLUG] = (object)array(
        'id'          => $curr_version,
        'slug'        => MPAYAH_PLUGIN_SLUG,
        'new_version' => $curr_version,
        'url'         => 'http://memberpress.com',
        'package'     => $download_url
      );
    }
    else
      unset( $transient->response[MPAYAH_PLUGIN_SLUG] );
    
    return $transient;
  }

  public static function plugin_info($false, $action, $args) {
    global $wp_version;
    
    if(!isset($action) or $action != 'plugin_information')
      return false;

    if(isset( $args->slug) and !preg_match("#.*".$args->slug.".*#", MPAYAH_PLUGIN_SLUG))
      return false;

    $mepr_options = MeprOptions::fetch();

    if(empty($mepr_options->mothership_license))
    {
      // Just here to query for the current version
      $args = array();
      if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
        $args['edge'] = 'true';

      $version_info = self::send_mothership_request("/versions/latest/".MPAYAH_EDITION, $args);
      $curr_version = $version_info['version'];
      $version_date = $version_info['version_date'];
      $download_url = '';
    }
    else
    {
      try
      {
        $domain = urlencode(MeprUtils::site_domain());
        $args = compact('domain');

        if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
          $args['edge'] = 'true';

        $license_info = self::send_mothership_request("/versions/info/{$mepr_options->mothership_license}", $args);
        $curr_version = $license_info['version'];
        $version_date = $license_info['version_date'];
        $download_url = $license_info['url'];
      }
      catch(Exception $e)
      {
        try
        {
          $args = array();
          if( $mepr_options->edge_updates or ( defined( "MEMBERPRESS_EDGE" ) and MEMBERPRESS_EDGE ) )
            $args['edge'] = 'true';

          // Just here to query for the current version
          $version_info = self::send_mothership_request("/versions/latest/".MPAYAH_EDITION, $args);
          $curr_version = $version_info['version'];
          $version_date = $version_info['version_date'];
          $download_url = '';
        }
        catch(Exception $e)
        {
          if(isset($transient->response[MEPR_PLUGIN_SLUG]))
            unset($transient->response[MEPR_PLUGIN_SLUG]);
          
          return $transient;
        }
      }
    }

    $pinfo = (object)array( "slug" => MPAYAH_PLUGIN_SLUG,
                            "name" => "MemberPress Are You A Human",
                            "author" => '<a href="http://blairwilliams.com">Caseproof, LLC</a>',
                            "author_profile" => "http://blairwilliams.com",
                            "contributors" => array("Caseproof" => "http://caseproof.com"),
                            "homepage" => "http://memberpress.com",
                            "version" => $curr_version,
                            "new_version" => $curr_version,
                            "requires" => $wp_version,
                            "tested" => $wp_version,
                            "compatibility" => array($wp_version => array($curr_version => array( 100, 0, 0))),
                            "rating" => "100.00",
                            "num_ratings" => "1",
                            "downloaded" => "1000",
                            "added" => "2014-03-15",
                            "last_updated" => $version_date,
                            "tags" => array("membership" => __("Membership", 'memberpress-areyouahuman'),
                                            "membership software" => __("Membership Software", 'memberpress-areyouahuman'),
                                            "members" => __("Members", 'memberpress-areyouahuman'),
                                            "payment" => __("Payment", 'memberpress-areyouahuman'),
                                            "protection" => __("Protection", 'memberpress-areyouahuman'),
                                            "rule" => __("Rule", 'memberpress-areyouahuman'),
                                            "lock" => __("Lock", 'memberpress-areyouahuman'),
                                            "access" => __("Access", 'memberpress-areyouahuman'),
                                            "community" => __("Community", 'memberpress-areyouahuman'),
                                            "admin" => __("Admin", 'memberpress-areyouahuman'),
                                            "pages" => __("Pages", 'memberpress-areyouahuman'),
                                            "posts" => __("Posts", 'memberpress-areyouahuman'),
                                            "plugin" => __("Plugin", 'memberpress-areyouahuman')),
                            "sections" => array("description" => "<p>" . __('Helps you place an "Are You a Human" game (captcha alternative) on your MemberPress registration forms.', 'memberpress-areyouahuman') . "</p>",
                                                "faq" => "<p>" . sprintf(__('You can access in-depth information about MemberPress at %1$sthe MemberPress User Manual%2$s.', 'memberpress-areyouahuman'), "<a href=\"http://memberpress.com/user-manual\">", "</a>") . "</p>", "changelog" => "<p>".__('No Additional information right now', 'memberpress-areyouahuman')."</p>"),
                            "download_link" => $download_url );

    return $pinfo;
  }

  public static function send_mothership_request( $endpoint,
                                                  $args=array(),
                                                  $method='get',
                                                  $domain='http://mothership.caseproof.com',
                                                  $blocking=true )
  {
    $uri = "{$domain}{$endpoint}";

    $arg_array = array( 'method'    => strtoupper($method),
                        'body'      => $args,
                        'timeout'   => 15,
                        'blocking'  => $blocking,
                        'sslverify' => false
                      );

    $resp = wp_remote_request($uri, $arg_array);

    // If we're not blocking then the response is irrelevant
    // So we'll just return true.
    if($blocking == false)
      return true;

    if(is_wp_error($resp))
      throw new Exception(__('You had an HTTP error connecting to Caseproof\'s Mothership API', 'memberpress-areyouahuman'));
    else
    {
      if(null !== ($json_res = json_decode($resp['body'], true)))
      {
        if(isset($json_res['error']))
          throw new Exception($json_res['error']);
        else
          return $json_res;
      }
      else
        throw new Exception(__( 'Your License Key was invalid', 'memberpress-areyouahuman'));
    }

    return false;
  }

  public static function manually_queue_update()
  {
    $transient = get_site_transient("update_plugins");
    set_site_transient("update_plugins", self::queue_update($transient, true));
  }
} //End class

