<?php
    /*  Copyright 2010  Gary-adam Shannon (gary@garyadamshannon.com)

        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License, version 2, as 
        published by the Free Software Foundation.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    */
?>
<?php
      /* 
      Plugin Name: WordPress Guestbook
      Plugin URI: http://wordpress.org/extend/plugins/wordpress-guestbook/
      Description: Enables a Guestbook on your website or blog
      Version: 1.0
      Author: Gary-adam Shannon
      Author URI: http://www.garyadamshannon.com
      */

      require_once('class.HookdResource.php');
      class wpGuestbook extends HookdResource {
          
          function __construct($arg) {
              register_activation_hook(__FILE__, array(&$this, '_hookd_activate'));
              register_deactivation_hook(__FILE__, array(&$this, '_hookd_deactivate'));
              parent::__construct($arg);
          }
          
          function wpGuestbook() {
              $args = func_get_args();
              call_user_func_array(array(&$this, '__construct'), $args);
          }
         
          function perform_install() {
              if (get_option('wpgb_installed')) {
                  // already installed dont try and set up
              } else {
                  // try and setup, but lets make sure no page already exists first
                  query_posts('pagename=Guestbook');
                  if (have_posts()) {
                      // already installed and possibly setup ok!
                  } else {
                      $post = array(
                        'comment_status' => 'open',
                        'post_author' => 1,
                        'post_content' => 'Welcome to the ' . get_option('home') . ' guestbook. Please leave your feedback!',
                        'post_date' => date("Y-m-d H:i:s"),
                        'post_date_gmt' => date("Y-m-d H:i:s"),
                        'post_name' => 'Guestbook',
                        'post_status' => 'publish',
                        'post_title' => 'Guestbook',
                        'post_type' => 'page'
                      );
                      wp_insert_post($post);
                      update_option('wpgb_installed', 1);
                  }
                  wp_reset_query();
              }
          }          
      }
      
      $guestBook = new wpGuestbook('650d23e0a20b504207a1');
      $guestBook->perform_install();
?>