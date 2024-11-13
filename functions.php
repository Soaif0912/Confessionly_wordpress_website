<?php
  // Start session
  if (!session_id()) {
    session_start();
  }
  
  if (!isset($_SESSION['unique_session_id'])) {
    $_SESSION['unique_session_id'] = session_id();
  }

// this function use to show the manu option in admin panal
register_nav_menus(
  array('primary-manu' => 'Top Menu', 'secondary-menu' => 'Footer Menu')
);

add_theme_support('post-thumbnails');  // for show the features image option in admin dashboard
add_theme_support('custom-header');
add_theme_support('title-tag');


// for the social icon side bar
register_sidebar(
  array(
    'name' => 'Social Icon',
    'id' => 'social-icon'
  )
);

// for the footer side bar1
register_sidebar(
  array(
    'name' => 'Footer sidebar 1',
    'id' => 'footer-sidebar1'
  )
);

// for the style sheet of the page 
function weavers_register_stylesheet()
{
  $theme_version = wp_get_theme()->get('Version');
  // add default theme style.css
  wp_enqueue_style('weavers-style', get_stylesheet_uri(), array(), $theme_version);
  // add others library styles
  wp_enqueue_style('fonts', get_template_directory_uri() . '/assets/css/fonts.css', null, $theme_version);

  wp_enqueue_style('font-awesome-all.min', get_template_directory_uri() . '/assets/css/font-awesome-all.min.css', null, $theme_version);
  wp_enqueue_style('bootstrap.min', get_template_directory_uri() . '/assets/css/bootstrap.min.css', null, $theme_version);
  wp_enqueue_style('HoldOn.min', get_template_directory_uri() . '/assets/css/HoldOn.min.css', null, $theme_version);
  // for custom style sheet
  wp_enqueue_style('custom', get_template_directory_uri() . '/assets/css/custom.css?version=' . time(), null, time());
}
add_action('wp_enqueue_scripts', 'weavers_register_stylesheet');

// for the scripting files add
function wp_register_scripts()
{
  $theme_version = wp_get_theme()->get('Version');
  // add scripting librarys
  // wp_enqueue_script('weavers-proper-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js', array(), $theme_version, false);
  wp_enqueue_script('bootstrap.bundle.min', get_template_directory_uri() . '/assets/js/bootstrap.bundle.min.js', array('jquery'), $theme_version, false);
  wp_enqueue_script('bootstrap.bundle.min.js', get_template_directory_uri() . '/assets/js/bootstrap.bundle.min.js.map', array('jquery'), $theme_version, false);
  wp_enqueue_script('font-awesome-all.min', get_template_directory_uri() . '/assets/js/font-awesome-all.min.js', array('jquery'), $theme_version, false);
  wp_enqueue_script('holdon', get_template_directory_uri() . '/assets/js/HoldOn.min.js', array('jquery'), '1.0', true);
  // for the cutom scripts
  wp_enqueue_script('custom.js', get_template_directory_uri() . '/assets/js/custom.js', array('jquery'), $theme_version, false);
}
add_action('wp_enqueue_scripts', 'wp_register_scripts');

if (function_exists('acf_add_options_page')) :

  acf_add_options_page(
    array(
      'page_title' => 'Theme General Settings',
      'menu_title' => 'Theme Settings',
      'menu_slug' => 'theme-general-settings',
      'capability' => 'edit_posts',
      'redirect' => false
    )
  );

  acf_add_options_sub_page(
    array(
      'page_title' => 'Theme Header Settings',
      'menu_title' => 'Header',
      'parent_slug' => 'theme-general-settings',
    )
  );

  acf_add_options_sub_page(
    array(
      'page_title' => 'Theme Footer Settings',
      'menu_title' => 'Footer',
      'parent_slug' => 'theme-general-settings',
    )
  );
endif;


// handles the AJAX request
function enqueue_custom_scripts()
{
  // Enqueue jQuery if not already loaded
  wp_enqueue_script('jquery');

  // Enqueue custom script
  wp_enqueue_script('custom-ajax-script', get_template_directory_uri() . '/custom-ajax-script.js', array('jquery'), '1.0', true);

  // Localize script with nonce and URL for AJAX request
  wp_localize_script(
    'custom-ajax-script',
    'custom_ajax_object',
    array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('custom-ajax-nonce')
    )
  );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');




// ----------For submit post----------->
add_action('wp_ajax_create_post', 'create_post_ajax_handler');
add_action('wp_ajax_nopriv_create_post', 'create_post_ajax_handler');
function create_post_ajax_handler()
{
  check_ajax_referer('custom-ajax-nonce', '_wpnonce');
  // Sanitize and process form data
  $post_author = $_POST['post_author'];
  $post_content = wp_kses_post($_POST['my_editor_id']);

  if ($post_content == '') {
    $post_content = wp_kses_post($_POST['my_editor_id_2']);
  }
  $max_word_count = 1300;
  $trimmed_content = wp_trim_words($post_content, $max_word_count, '');

  if (empty($post_author)) {
    $post_author = get_field('comment_author_name','option');
  }

  function get_last_post_id()
  {
    $args = array(
      'post_type'      => 'any',
      'post_status'    => array('publish', 'draft'),
      'posts_per_page' => 1,
      'orderby'        => 'post_date',
      'order'          => 'DESC',
    );

    $recent_posts = get_posts($args);
    if (!empty($recent_posts)) {
      return $recent_posts[0]->ID;
    } else {
      return 0; // No posts found
    }
  }

  $ran_num = rand(101, 999);
  $last_post_id ='#'.(get_last_post_id() + 1).$ran_num;
  $post_title = 'confession ' . $last_post_id;

  // validation logic 
  if (empty($post_content)) {
    wp_send_json_error(array('message' => 'Invalid post data'));
  }

  // Create a new post
  $new_post = array(
    'post_author' => $post_author,
    'post_title' => $post_title,
    'post_content' => $trimmed_content,
    'post_status' => 'draft',
    'post_type' => 'post',
  );

  $post_id = wp_insert_post($new_post);

  // Setting author to meta field
  if (!is_wp_error($post_id)) {
    $custom_field_value = sanitize_text_field($_POST['post_author']);
    if(empty($custom_field_value)){ $custom_field_value = $post_author; }
    update_post_meta($post_id, 'past_author_name', $custom_field_value);
    update_post_meta($post_id, 'post_id',$last_post_id);
  } else {
    $error_message = $post_id->get_error_message();
  }

  if ($post_id) {
    echo $post_id;
  } else {
    echo 'Error creating post. Please try again.';
  }
  wp_die();
}

// //------------Unique_id_for_post-------------->
// function generate_and_save_unique_id($post_id)
// {
//   if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
//     return;
//   }
//   if ('post' !== get_post_type($post_id)) {
//     return;
//   }
//   $existing_unique_id = get_post_meta($post_id, 'custom_unique_id', true);
//   if (!$existing_unique_id) {

//     $unique_id = '#' . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
//     update_post_meta($post_id, 'custom_unique_id', $unique_id);
//   }
// }
// add_action('save_post', 'generate_and_save_unique_id');



//------- Load Post content---------///
function discussion_comment($current_post_id, $IP_Address, $comment_id_array_forCommentBox, $comment_id_array_forComment, $parent_id = 0, $comment_class = 'commentlist',$comment_level_limit=0)
{
  $comment_number = 0;
  global $wpdb;
  $userIP = $IP_Address;


  $comments = get_comments(array('post_id' => $current_post_id, 'parent' => $parent_id, 'orderby' => 'comment_ID', 'order' => 'DESC', 'number' => $comment_number));
  if (!empty($comments)) {
    foreach ($comments as $comment) {

      if (in_array($parent_id, $comment_id_array_forComment)) {

        if($parent_id !=0){
        $comment_level_limit++;
        }

        // comment count
        $table_name_comment = $wpdb->prefix . 'comments';
        $query_comment_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name_comment WHERE comment_parent = %s",
          $comment->comment_ID
        );
        $comment_count = $wpdb->get_var($query_comment_count);
        // Like present check 
        $table_name = $wpdb->prefix . 'comment_like';
        $query_like_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
          $comment->comment_ID,
          $userIP,
          'Like'
        );
        $like_present = $wpdb->get_var($query_like_present);
        // like count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_type = %s",
          $comment->comment_ID,
          'Like'
        );
        $like_count = $wpdb->get_var($query_like_count);
        $extra_like_count = get_comment_meta($comment->comment_ID, 'extra_comment_like_count', true);
        if(!empty($extra_like_count)){
        $like_count += $extra_like_count;
        }

        // dislike present
        $query_dislike_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
          $comment->comment_ID,
          $userIP,
          'DisLike'
        );
        $dislike_present = $wpdb->get_var($query_dislike_present);
        // dislike count
        $query_dislike_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_type = %s",
          $comment->comment_ID,
          'DisLike'
        );
        $dislike_like_count = $wpdb->get_var($query_dislike_count);
        $extra_dislike_count = get_comment_meta($comment->comment_ID, 'extra_comment_dislike_count', true);
        if(!empty($extra_dislike_count)){
        $dislike_like_count += $extra_dislike_count;
        }


        // report present 
        $table_name_report = $wpdb->prefix . 'comment_report';
        $query_report_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name_report WHERE report_comment_ID = %s AND report_author_IP = %s AND report_type = %s",
          $comment->comment_ID,
          $userIP,
          'report'
        );
        $comment_report_present = $wpdb->get_var($query_report_present);
        // report count
        $table_name_report = $wpdb->prefix . 'comment_report';
        $query_report_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name_report WHERE report_comment_ID = %s AND report_type = %s",
          $comment->comment_ID,
          'report'
        );
        $report_count = $wpdb->get_var($query_report_count);
?>

        <div class="sub-comment <?php echo $comment_class; ?> <?php if ($comment_count > 0) {
                                  echo 'has-comment';
                                } else {
                                  echo 'no-comment';
                                } ?>" id="comment-<?= $comment->comment_ID ?>">
          <?php if ($comment_count > 0) { ?> <div class="comment-border"></div> <?php } ?>

          <div class="top-comment-each  ">
            <ul class="user-info d-flex justify-content-between">
              <li>
                <?php echo $comment->comment_author; ?>
              </li>
              <li> <?php echo date('d/m/Y', strtotime($comment->comment_date)); ?>
                <?php //echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' ';
                //echo get_field('post_&_comment_how_many_days_old_text', 'option'); 
                ?>
              </li>
            </ul>
            <div class="post-top-text hidden-comment" <?php if ($report_count >= 2) { ?>style="display: none;" <?php } ?>>
              <?php echo $comment->comment_content; ?>
            </div>
            <?php if ($report_count >= 2) { ?>
              <span class="read-hidden-read" style="display: flex;">
                <p><?php echo get_field('hidden_comment_text_', 'option') ?></p>
                <a href="javascript:void(0)" class="read-hidden-comment">
                  <?php echo get_field('hidden_comment_display_button_text', 'option'); ?>
                </a>
              </span>
            <?php } ?>

            <div class="reply-block">
              <div class="reply-bottom-info">
                <ul class="icon-group d-flex align-items-end list-unstyled">

                  <?php if ($comment_count > 0) { ?>
                  <li class="show-comment"><a href="javascript:void(0)" class="reply_comment_disply <?php if (in_array($comment->comment_ID, $comment_id_array_forComment)) {  echo "open";  } else {  echo "close";
                    } ?>" data-comment-id="<?php echo $comment->comment_ID; ?>"></a>
                  </li>
                  <?php } ?>
                  <input type="hidden" id="comment_checking" name="custId" value="<?php echo $comment_count; ?>">

                  <li class="like <?php if ($like_present == 1) { echo 'active';  }?>" id="comment-like" data-id="<?php echo $comment->comment_ID; ?>"><a class="icon-like-new" href="javascript:void(0)"></a>
                    <?php  ?><span class="like-count <?php if($like_count <1 ){ echo 'd-none'; }?> "><?php if($like_count>0){  echo $like_count; } ?></span><?php ?>
                  </li>

                  <input type="hidden" id="like" name="custId" value="<?php echo $like_present; ?>">

                  <li class="dislike <?php if ($dislike_present == 1) { echo 'active';  }?>" id="comment-dislike" data-id="<?php echo $comment->comment_ID; ?>"><a class="icon-dislike-new" href="javascript:void(0)"></a>
                    <?php  ?>
                    <span class="dislike_count <?php if($dislike_like_count <1 ){ echo 'd-none'; }?> "> <?php if($dislike_like_count>0){ echo $dislike_like_count; } ?> </span><?php  ?>
                  </li>

                  <input type="hidden" id="dislike" name="custId" value="<?php echo $dislike_present; ?>">

                  <?php if(!($comment_level_limit >= 3)){?>
                  <li class="comment_btn" data-comment-id="<?php echo $comment->comment_ID; ?>"><a class="icon-comment" href="javascript:void(0)"></a> 
                  <?php if($comment_count>0){ ?> <span><?php  echo $comment_count; ?></span> <?php }?>
                  </li>
                    <?php } ?>

                  <li><a class="icon-report" id="comment-report" data-id="<?php echo $comment->comment_ID; ?>" data-post-data-id="<?php echo $comment_report_present; ?>" <?php if ($comment_report_present == 1) { ?> style="color:#AB4D50; <?php } ?> href=" javascript:void(0)"></a>
                    <input type="hidden" id="report" name="custId" value="<?php echo $comment_report_present; ?>">
                  </li>
                </ul>

                <?php if(!($comment_level_limit >= 3)){?>
                <div id="comment_form-<?php echo $comment->comment_ID; ?>" style="display:none !important">
                  <div class="comment-block d-flex align-items-center">
                    <form method="post" class="comment-form">
                      <input type="hidden" name="comment_type" value="commentreply" />
                      <input type="hidden" name="comment_parent_comment" value="<?php echo $parent_id; ?>" />
                      <input type="hidden" name="comment_parent" value="<?php echo $comment->comment_ID; ?>" />
                      <input type="hidden" name="comment_post_ID" value="<?php echo $current_post_id; echo 'Hello '.$comment_level_limit; ?>" />
                      <input type="hidden" name="author" value="2" />

                      <input type="text" placeholder="<?php echo get_field('comment_author_input_box_placeholder', 'option') ?>" class="form-control" name="comment_author" id="comment-author">
                      <div class="send-comment">
                        <textarea class="form-control comment_box" placeholder="<?php echo get_field('comment_input_box_placeholder', 'option') ?>"  name="comment"></textarea>
                        <div class="send-icon submit_comment_btn" data-id="<?php echo $comment->comment_ID; ?>">
                          <a href="javascript:void(0);"><span class="icon-send"></span></a>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
                <?php } ?>

              </div>
            </div>
          </div>

          <?php
          discussion_comment($current_post_id, $IP_Address, $comment_id_array_forCommentBox, $comment_id_array_forComment, $comment->comment_ID, 'sub-sub-comment',$comment_level_limit);
          ?>

        </div>

      <?php
      }
    }
  }
}


function discussion_top5_comment($current_post_id, $IP_Address, $comment_id_array_forCommentBox, $comment_id_array_forComment, $parent_id = 0, $comment_class = 'commentlist',$comment_level_limit=0)
{
  $comment_number = 5;
  global $wpdb;
  $userIP = $IP_Address;


  $comments = get_comments(array('post_id' => $current_post_id, 'parent' => $parent_id, 'orderby' => 'comment_ID', 'order' => 'DESC', 'number' => $comment_number));
  if (!empty($comments)) {
    foreach ($comments as $comment) {

      if (in_array($parent_id, $comment_id_array_forComment)) {

        if($parent_id !=0){
        $comment_level_limit++;
        }

        // comment count
        $table_name_comment = $wpdb->prefix . 'comments';
        $query_comment_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name_comment WHERE comment_parent = %s",
          $comment->comment_ID
        );
        $comment_count = $wpdb->get_var($query_comment_count);
        // Like present check 
        $table_name = $wpdb->prefix . 'comment_like';
        $query_like_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
          $comment->comment_ID,
          $userIP,
          'Like'
        );
        $like_present = $wpdb->get_var($query_like_present);
        // like count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_type = %s",
          $comment->comment_ID,
          'Like'
        );
        $like_count = $wpdb->get_var($query_like_count);
        $extra_like_count = get_comment_meta($comment->comment_ID, 'extra_comment_like_count', true);
        if(!empty($extra_like_count)){
        $like_count += $extra_like_count;
        }

        // dislike present
        $query_dislike_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
          $comment->comment_ID,
          $userIP,
          'DisLike'
        );
        $dislike_present = $wpdb->get_var($query_dislike_present);
        // dislike count
        $query_dislike_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_comment_ID = %s AND like_type = %s",
          $comment->comment_ID,
          'DisLike'
        );
        $dislike_like_count = $wpdb->get_var($query_dislike_count);
        $extra_dislike_count = get_comment_meta($comment->comment_ID, 'extra_comment_dislike_count', true);
        if(!empty($extra_dislike_count)){
        $dislike_like_count += $extra_dislike_count;
        }

        
        // report present 
        $table_name_report = $wpdb->prefix . 'comment_report';
        $query_report_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name_report WHERE report_comment_ID = %s AND report_author_IP = %s AND report_type = %s",
          $comment->comment_ID,
          $userIP,
          'report'
        );
        $comment_report_present = $wpdb->get_var($query_report_present);
        // report count
        $table_name_report = $wpdb->prefix . 'comment_report';
        $query_report_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name_report WHERE report_comment_ID = %s AND report_type = %s",
          $comment->comment_ID,
          'report'
        );
        $report_count = $wpdb->get_var($query_report_count);
?>

        <div class="sub-comment <?php echo $comment_class; ?> <?php if ($comment_count > 0) {
                                  echo 'has-comment';
                                } else {
                                  echo 'no-comment';
                                } ?>" id="comment-<?= $comment->comment_ID ?>">
          <?php if ($comment_count > 0) { ?> <div class="comment-border"></div> <?php } ?>

          <div class="top-comment-each  ">
            <ul class="user-info d-flex justify-content-between">
              <li>
                <?php echo $comment->comment_author; ?>
              </li>
              <li> <?php echo date('d/m/Y', strtotime($comment->comment_date)); ?>
                <?php //echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' ';
                //echo get_field('post_&_comment_how_many_days_old_text', 'option'); 
                ?>
              </li>
            </ul>
            <div class="post-top-text hidden-comment" <?php if ($report_count >= 2) { ?>style="display: none;" <?php } ?>>
              <?php echo $comment->comment_content; ?>
            </div>
            <?php if ($report_count >= 2) { ?>
              <span class="read-hidden-read" style="display: flex;">
                <p><?php echo get_field('hidden_comment_text_', 'option') ?></p>
                <a href="javascript:void(0)" class="read-hidden-comment">
                  <?php echo get_field('hidden_comment_display_button_text', 'option'); ?>
                </a>
              </span>
            <?php } ?>

            <div class="reply-block">
              <div class="reply-bottom-info">
                <ul class="icon-group d-flex align-items-end list-unstyled">

                  <?php if ($comment_count >0) { ?>
                  <li class="show-comment" style="display:none;"><a href="javascript:void(0)" class="reply_comment_disply <?php if (in_array($comment->comment_ID, $comment_id_array_forComment)) {  echo "open";  } else {  echo "close";
                    } ?>" data-comment-id="<?php echo $comment->comment_ID; ?>"></a>
                  </li>
                  <?php } ?>

                  <input type="hidden" id="comment_checking" name="custId" value="<?php echo $comment_count; ?>">

                  <li class="like <?php if ($like_present == 1) { echo 'active';  }?>" id="comment-like" data-id="<?php echo $comment->comment_ID; ?>"><a class="icon-like-new" href="javascript:void(0)"></a>
                    <?php  ?><span class="like-count <?php if($like_count <1 ){ echo 'd-none'; }?> "><?php if($like_count>0){  echo $like_count; } ?></span><?php ?>
                  </li>

                  <input type="hidden" id="like" name="custId" value="<?php echo $like_present; ?>">

                  <li class="dislike <?php if ($dislike_present == 1) { echo 'active';  }?>" id="comment-dislike" data-id="<?php echo $comment->comment_ID; ?>"><a class="icon-dislike-new" href="javascript:void(0)"></a>
                    <?php  ?>
                    <span class="dislike_count <?php if($dislike_like_count <1 ){ echo 'd-none'; }?> "> <?php if($dislike_like_count>0){ echo $dislike_like_count; } ?> </span><?php  ?>
                  </li>

                  <input type="hidden" id="dislike" name="custId" value="<?php echo $dislike_present; ?>">

                  <?php if(!($comment_level_limit >= 3)){?>
                  <li class="comment_btn" ><a  href="<?php the_permalink(); ?>" class="icon-comment" href="javascript:void(0)"></a> 
                  <?php if($comment_count>0){ ?> <span><?php  echo $comment_count; ?></span> <?php }?>
                  </li>
                    <?php } ?>

                  <li><a class="icon-report" id="comment-report" data-id="<?php echo $comment->comment_ID; ?>" data-post-data-id="<?php echo $comment_report_present; ?>" <?php if ($comment_report_present == 1) { ?> style="color:#AB4D50; <?php } ?> href=" javascript:void(0)"></a>
                    <input type="hidden" id="report" name="custId" value="<?php echo $comment_report_present; ?>">
                  </li>
                </ul>

                <!-- <?php if(!($comment_level_limit >= 3)){?>
                <div id="comment_form-<?php echo $comment->comment_ID; ?>" style="display:none !important">
                  <div class="comment-block d-flex align-items-center">
                    <form method="post" class="comment-form">
                      <input type="hidden" name="comment_type" value="commentreply" />
                      <input type="hidden" name="comment_parent_comment" value="<?php echo $parent_id; ?>" />
                      <input type="hidden" name="comment_parent" value="<?php echo $comment->comment_ID; ?>" />
                      <input type="hidden" name="comment_post_ID" value="<?php echo $current_post_id; echo 'Hello '.$comment_level_limit; ?>" />
                      <input type="hidden" name="author" value="2" />

                      <input type="text" placeholder="<?php echo get_field('comment_author_input_box_placeholder', 'option') ?>" class="form-control" name="comment_author" id="comment-author">
                      <div class="send-comment">
                        <textarea class="form-control" placeholder="<?php echo get_field('comment_input_box_placeholder', 'option') ?>" name="comment"></textarea>
                        <div class="send-icon submit_comment_btn" data-id="<?php echo $comment->comment_ID; ?>">
                          <a href="javascript:void(0);"><span class="icon-send"></span></a>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
                <?php } ?> -->

              </div>
            </div>
          </div>

          <?php
          discussion_top5_comment($current_post_id, $IP_Address, $comment_id_array_forCommentBox, $comment_id_array_forComment, $comment->comment_ID, 'sub-sub-comment',$comment_level_limit);
          ?>

        </div>

      <?php
      }
    }
  }
}


function LoadPost(){

  global $wpdb;

  $single_page_post_id = $_POST['single_page_post_id'];

  $limit_per_page = get_field('post_limit_per_page', 'option');
  if ($limit_per_page == '' || $limit_per_page == 0) {
    $limit_per_page = 20;
  }

  $windowWidth = $_POST['windowWidth'];
  if($windowWidth<991){
    $limit_per_page=-1;
  }


  if ($single_page_post_id != '') {
    $limit_per_page = -1;
  }

  // $total_post = $_POST['total_post'];

  $post_name = $_POST['SearchValue'];
  $meta_key = sanitize_text_field($_POST['filter']);
  // if ($meta_key == "") {
  //   $meta_key = 'sticky';
  // }

  $page_no = "";

  if (isset($_POST["page_no"])) {
    $page_no = $_POST["page_no"];
  } else {
    $page_no = 1;
  }

  $offset = ($page_no - 1) * $limit_per_page;

  $post_id_array_forPostCommentBox = $_POST["post_id_array_forPostCommentBox"];
  $post_id_array_forPostCommentShow = $_POST["post_id_array_forPostCommentShow"];

  $comment_id_array_forCommentBox = $_POST["comment_id_array_forCommentBox"];
  $comment_id_array_forComment = $_POST["comment_id_array_forComment"];

  $args = array(
    'post_type' => 'post', // Change this if you are using a custom post type
    'post_status' => 'publish',
    'posts_per_page' => $limit_per_page,
    'offset' => $offset,
    'meta_key'       => $meta_key, // Replace 'your_meta_field' with the actual meta key you want to use
    'orderby'        => array(
        'meta_value_num' => 'DESC', // Order by the meta field in descending order
        'post_date'      => 'DESC', // Order by post date in descending order
    ),
    // 'meta_query'     => array(
    //     array(
    //         'key' => $meta_key, // Replace 'your_meta_field' with the actual meta key you want to use
    //         // You can add more conditions if needed
    //     ),
    // ),
    's' => esc_attr($post_name),
  );

  $custom_query = new WP_Query($args);
  $total_post = $custom_query->found_posts;

  /*if($meta_key){    
    $args['meta_query'] = array(
      array(
        'key' => $meta_key,  
        'value' => array(0,1),
        'compare' => 'BETWEEN'      
      ),
    );
  }*/

  $query = new WP_Query($args); //print_r($query);

  if ($query->have_posts()) :
    while ($query->have_posts()) :
      $query->the_post();

      $post_id = get_the_ID();

      if ($single_page_post_id == '') {

        $unique_id = get_post_meta(get_the_ID(), 'post_id', true);
        // Get the content
        $content = get_the_content();
        $content = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $content);

        $excerpt = wp_trim_words($content, 100); // Display first100 words as excerpt
        $excerpt = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $excerpt);

        // report count
        $table_name2 = $wpdb->prefix . 'report';
        $query_report_count1 = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_type = %s",
          $post_id,
          'report'
        );
        $report_count1 = $wpdb->get_var($query_report_count1);
        // report present
        $query_report_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_author_IP = %s AND report_type = %s",
          $post_id,
          $_POST['author_ip'],
          'report'
        );
        $report_present = $wpdb->get_var($query_report_present);
        // Like present check 
        $table_name = $wpdb->prefix . 'like';
        $query_like_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'Like'
        );
        $like_present = $wpdb->get_var($query_like_present);
        // like count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'Like'
        );
        $like_count = $wpdb->get_var($query_like_count);
        $extra_like_count = get_post_meta(get_the_ID(), 'add_extra_like_count', true);
        if(!empty($extra_like_count)){
        $like_count += $extra_like_count;
        }

        // dislike present
        $query_dislike_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'DisLike'
        );
        $dislike_present = $wpdb->get_var($query_dislike_present);
        // dislike count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'DisLike'
        );
        $dislike_like_count = $wpdb->get_var($query_like_count);
        $extra_Dislike_count = get_post_meta(get_the_ID(), 'add_extra_dislike_count', true);
        if(!empty($extra_Dislike_count)){
        $dislike_like_count += $extra_Dislike_count;
        }
        

        // get total comment
        $args = array(
          'post_id' => $post_id,
          'count' => true
        );
        $comments_count = get_comments($args);
      ?>

        <div class="card-wrap post-each light-gray-bg parent-comment-<?php echo $post_id; ?> <?php if ($comments_count > 0) {
          echo 'has-comment';} else {echo 'no-comment';}echo ' ', $post_id; ?>">

          <?php if ($comments_count > 0) { ?> <div class="comment-border"></div> <?php } ?>
          <div class="main-post-block <?php echo ($comments_count > 0) ? 'hascomment' : 'nocomment'; ?>">
          <div class="top-comment-each">
            <ul class="user-info d-flex justify-content-between">
              <li>
                <?php echo $unique_id; ?>
                <?php $meta_value = get_post_meta($post_id, 'sticky', true);
                if ($meta_value == 1) { ?>  <?php } ?>
                <!-- <span class="icon-pin"></span> -->
              </li>
              <li>
                <?php echo date('d/m/Y', get_the_time('U')); ?>
                <?php //echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ';
                //echo get_field('post_&_comment_how_many_days_old_text', 'option'); 
                ?>
              </li>
            </ul>
            <div class="author-name" ><h4><?php $custom_field_value = get_post_meta(get_the_ID(), 'past_author_name', true);
              if (!empty($custom_field_value)) { echo $custom_field_value;}else{ echo "Anonymous"; } ?></h4>
            </div>
            <div class="post-top-text" <?php if ($report_count1 >= 15) { ?>style="display: none;" <?php } ?>>

              <div class="post-content <?php if (!in_array($post_id, $post_id_array_forPostCommentShow)) { ?> post-top-comments<?php }?>"
              data-id="<?php echo $post_id; ?>">

                <div class="excerpt">
                  <p>
                    <?php echo $excerpt;
                    $wordCount = str_word_count($excerpt, 0, '0123456789');
                    if ($wordCount > 100) { ?> <a href="javascript:void(0)" class="read-more"><?php echo get_field('read_more_button_text', 'option'); ?></a>
                    <?php } ?>
                  </p>
                </div>

                <div class="full-content" style="display: none;">
                  <p>
                    <?php echo $content; ?><a href="javascript:void(0)" class="read-less"><?php echo get_field('read_less_button_text', 'option'); ?></a>
                  </p>
                </div>

              </div>

            </div>
            
            <?php if ($report_count1 >= 15) { ?>
              <span class="read-hidden-read" style="display: flex;">
                <p><?php echo get_field('hidden_post_text_', 'option'); ?> </p>
                <a href="javascript:void(0)" class="read-hidden">
                  <?php echo get_field('hidden_post_display_button_text', 'option'); ?>
                </a>
              </span>
            <?php } ?>

            <div class="reply-block">
              <ul class="post-view d-flex justify-content-end">
                <li>
                  <?php
                  // $totalReaction = $like_count + $dislike_like_count;
                  // if ($totalReaction > 0 && $totalReaction < 2) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_less_than_2_reaction_text', 'option');
                  // } else if ($totalReaction > 1) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_greater_than_two_reaction_text', 'option');
                  // }
                  ?>
                </li>
              </ul>
              <div class="reply-bottom-info">
                <ul class="icon-group d-flex list-unstyled">
                  <?php if ($comments_count > 0) { ?>
                    <!-- <li class="show-comment"><a href="javascript:void(0)" class="post_comment_display <?php if (in_array($post_id, $post_id_array_forPostCommentShow)) {
                    echo "open"; } else { echo "close"; } ?>" data-id="<?php echo $post_id; ?>"></a>
                    </li>  -->
                  <?php } ?>

                  <li class="like  <?php if ($like_present == 1) { echo 'active'; } ?>" id="post-like" data-id="<?php echo $post_id; ?>">
                    <a class="icon-like-new"  href="javascript:void(0)"></a>
                    <?php  ?><span class="like-count <?php if($like_count<1){ echo 'd-none';} ?>"><?php if($like_count>0){  echo $like_count; } ?></span><?php  ?>
                  </li>

                  <input type="hidden" id="like" name="custId" value="<?php echo $like_present; ?>">

                  <li class="dislike <?php if ($dislike_present == 1) {  echo 'active';  } ?>" id="post-dislike" data-id="<?php echo $post_id; ?>">
                  <a class="icon-dislike-new" href="javascript:void(0)"></a>
                  <?php  ?><span class="dislike_count <?php if($dislike_like_count<1){ echo 'd-none';} ?>"><?php if($dislike_like_count>0){ echo $dislike_like_count;} ?></span><?php ?>
                  </li>

                  <input type="hidden" id="dislike" name="custId" value="<?php echo $dislike_present; ?>">

                  <li class="comment comment_display  <?php if (in_array($post_id, $post_id_array_forPostCommentShow)) { echo 'active';} ?>" data-id="<?php echo $post_id; ?>">
                     <a href=""  target="_blank" class="icon-comment" href="javascript:void(0)"></a>
                     <?php if($comments_count>0){ ?> <span><?php echo $comments_count; ?> </span> <?php }?>
                  </li>

                  <li class="share a2a_counter share-<?php echo $post_id; ?>" data-id="<?php echo $post_id; ?>" data-toggle="modal" data-target="#exampleModal<?php echo $post_id; ?>">
                    <div class="icon-share">
                  </li>

                  <!-- Modal -->
                  <div class="modal fade share-modal-wrap" id="exampleModal<?php echo $post_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog share-modal modal-dialog-centered" role="document">
                      <div class="modal-content">

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>

                        <div class="modal-body">

                          <div class="a2a_kit a2a_kit_size_32 a2a_default_style share-option" data-a2a-url="<?php echo get_permalink(); ?>" data-a2a-title="<?php the_title(); ?>">
                            <a class="a2a_button_facebook"></a>
                            <a class="a2a_button_facebook_messenger"></a>
                            <a class="a2a_button_whatsapp"></a>
                            <a class="a2a_button_email"></a>
                            <a class="a2a_button_reddit"></a>
                            <a class="a2a_button_tumblr"></a>
                            <a class="a2a_button_linkedin"></a>
                            <a class="a2a_button_x"></a>
                            <a class="a2a_button_viber"></a>
                            <a class="a2a_button_threads"></a>
                            <a class="a2a_button_telegram"></a>
                            <a class="a2a_button_snapchat"></a>
                            <a class="a2a_button_sms"></a>
                            <a class="a2a_button_copy_link"></a>
                            <a class="a2a_dd a2a_counter" href="https://www.addtoany.com/share"></a>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                  <li>
                    <a class="icon-report" id="post-report" <?php if ($report_present == 1) { ?> style="color:#AB4D50;" <?php } ?> data-id="<?php echo $post_id; ?>" data-post-data-id="<?php echo $report_present; ?>" href="javascript:void(0)"></a>
                    <input type="hidden" id="report" name="custId" value="<?php echo $report_present; ?>">
                  </li>

                </ul>

                <!-- Top 5 comments display -->
                <?php if($comments_count>0){?>
                <div class="top-comment-wrap top5-comment-<?php echo $post_id; ?>" style="display:none;" >

                  <div class="top-comments" ><h3><?php the_field('text_for_top_comments','option');?></h3></div>
                  <div class="sub-comment-wrap" >
                  <?php discussion_top5_comment(get_the_ID(), $_POST['author_ip'], $comment_id_array_forCommentBox, $comment_id_array_forComment); ?>
                  </div>

                  <button class="comment comment_display  load-more" data-id="<?php echo $post_id; ?>">
                     <a  class="top-comment" href="javascript:void(0)"><?php the_field('text_for_load_more_comments','option');?></a>
                  </button>
                </div>
                <?php } ?>

                <?php if (in_array($post_id, $post_id_array_forPostCommentShow)) { ?>
                <div class="add-cmnt">
                  <a href="#" class="add_cmnt_box" data-id="<?php echo $post_id;?>" > <?php the_field('add_comment_text','option');?></a>
                </div>

                <div class="comment-block align-items-center" id="post_comment_box_<?php echo $post_id; ?>" style="display: none ; ">
                  <form method="post" class="comment-form">
                    <input type="hidden" name="comment_type" value="postcomment" />
                    <input type="hidden" name="comment_parent" value="" />
                    <input type="hidden" name="comment_post_ID" value="<?php echo get_the_ID(); ?>" />
                    <input type="hidden" name="comment_post_ID" value="<?php echo get_the_ID(); ?>" />
                    <input type="hidden" name="author" value="2" />

                    <input type="text" placeholder="<?php echo get_field('comment_author_input_box_placeholder', 'option') ?>" class="form-control" name="comment_author" id="comment-author">

                    <div class="send-comment">
                      <textarea class="form-control comment_box" placeholder="<?php echo get_field('comment_input_box_placeholder', 'option') ?>"  name="comment" required></textarea>
                      <div class="send-icon submit_comment_btn" data-id="<?php echo $post_id; ?>">
                        <a href="javascript:void(0);"><span class="icon-send"></span></a>
                      </div>
                    </div>
                  </form>
                </div>
                <?php  }?>

              </div>
            </div>
          </div>

            <?php
            if (in_array($post_id, $post_id_array_forPostCommentShow)) { 
               if ($comments_count > 0) { ?>
            <div class="sub-comment-wrap">
            <?php  
               
              discussion_comment(get_the_ID(), $_POST['author_ip'], $comment_id_array_forCommentBox, $comment_id_array_forComment);?>
            </div>
            <?php } } ?>

        </div>
        </div>

      <?php
      } else if ($single_page_post_id == $post_id) {


        $unique_id = get_post_meta(get_the_ID(), 'post_id', true);
        // Get the content
        $content = get_the_content();
        $content = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $content);

        $excerpt = wp_trim_words($content, 100); // Display first100 words as excerpt
        $excerpt = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $excerpt);

        // report count
        $table_name2 = $wpdb->prefix . 'report';
        $query_report_count1 = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_type = %s",
          $post_id,
          'report'
        );
        $report_count1 = $wpdb->get_var($query_report_count1);
        // report present
        $query_report_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_author_IP = %s AND report_type = %s",
          $post_id,
          $_POST['author_ip'],
          'report'
        );
        $report_present = $wpdb->get_var($query_report_present);
        // Like present check 
        $table_name = $wpdb->prefix . 'like';
        $query_like_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'Like'
        );
        $like_present = $wpdb->get_var($query_like_present);
        // like count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'Like'
        );
        $like_count = $wpdb->get_var($query_like_count);
        $extra_like_count = get_post_meta(get_the_ID(), 'add_extra_like_count', true);
        if(!empty($extra_like_count)){
        $like_count += $extra_like_count;
        }

        // dislike present
        $query_dislike_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'DisLike'
        );
        $dislike_present = $wpdb->get_var($query_dislike_present);
        // dislike count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'DisLike'
        );
        $dislike_like_count = $wpdb->get_var($query_like_count);
        $extra_Dislike_count = get_post_meta(get_the_ID(), 'add_extra_dislike_count', true);
        if(!empty($extra_Dislike_count)){
        $dislike_like_count += $extra_Dislike_count;
        }


        // get total comment
        $args = array(
          'post_id' => $post_id,
          'count' => true
        );
        $comments_count = get_comments($args);
      ?>

        <div class="card-wrap post-each light-gray-bg parent-comment-<?php echo $post_id; ?> <?php if ($comments_count > 0) {
          echo 'has-comment';} else {echo 'no-comment';}echo ' ', $post_id; ?>">

          <?php if ($comments_count > 0) { ?> <div class="comment-border"></div> <?php } ?>
          <div class="main-post-block <?php echo ($comments_count > 0) ? 'hascomment' : 'nocomment'; ?>">
          <div class="top-comment-each">
            <ul class="user-info d-flex justify-content-between">
              <li>
                <?php echo $unique_id;
                $meta_value = get_post_meta($post_id, 'sticky', true);
                if ($meta_value == 1) { ?>  <?php } ?>
                <!-- <span class="icon-pin"></span> -->
              </li>
              <li>
                <?php echo date('d/m/Y', get_the_time('U')); ?>
                <?php //echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ';
                //echo get_field('post_&_comment_how_many_days_old_text', 'option'); 
                ?>
              </li>
            </ul>

            <div class="author-name" ><h4><?php $custom_field_value = get_post_meta(get_the_ID(), 'past_author_name', true);
              if (!empty($custom_field_value)) { echo $custom_field_value;}else{ echo "Anonymous"; } ?></h4>
            </div>

            <div class="post-top-text" <?php if ($report_count1 >= 15) { ?>style="display: none;" <?php } ?>>

              <div class="post-content ">
                <div class="excerpt">
                  <p>
                    <?php echo $excerpt;
                    $wordCount = str_word_count($excerpt);
                    if ($wordCount > 100) { ?> <a href="javascript:void(0)" class="read-more"><?php echo get_field('read_more_button_text', 'option'); ?></a>
                    <?php } ?>
                  </p>
                </div>

                <div class="full-content" style="display: none;">
                  <p>
                    <?php echo $content; ?><a href="javascript:void(0)" class="read-less"><?php echo get_field('read_less_button_text', 'option'); ?></a>
                  </p>
                </div>

              </div>
            </div>

            <?php if ($report_count1 >= 15) { ?>
              <span class="read-hidden-read" style="display: flex;">
                <p><?php echo get_field('hidden_post_text_', 'option'); ?> </p>
                <a href="javascript:void(0)" class="read-hidden">
                  <?php echo get_field('hidden_post_display_button_text', 'option'); ?>
                </a>
              </span>
            <?php } ?>
            <div class="reply-block">
              <ul class="post-view d-flex justify-content-end">
                <li>
                  <?php
                  // $totalReaction = $like_count + $dislike_like_count;
                  // if ($totalReaction > 0 && $totalReaction < 2) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_less_than_2_reaction_text', 'option');
                  // } else if ($totalReaction > 1) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_greater_than_two_reaction_text', 'option');
                  // }
                  ?>
                </li>
              </ul>
              <div class="reply-bottom-info">
                <ul class="icon-group d-flex list-unstyled">
                  <?php if ($comments_count > 0) { ?>
                    <!-- <li class="show-comment"><a href="javascript:void(0)" class="post_comment_display <?php if (in_array($post_id, $post_id_array_forPostCommentShow)) {
                      echo "open"; } else { echo "close"; } ?>" data-id="<?php echo $post_id; ?>"></a>
                    </li>  -->
                  <?php } ?>

                  <li class="like <?php if ($like_present == 1) { echo 'active'; } ?> " id="post-like"  data-id="<?php echo $post_id; ?>" ><a class="icon-like-new" href="javascript:void(0)"></a>
                    <?php ?><span class="like-count <?php if($like_count <1 ){  echo 'd-none'; } ?> "><?php if($like_count>0){  echo $like_count; } ?></span><?php  ?>
                  </li>
                  <input type="hidden" id="like" name="custId" value="<?php echo $like_present; ?>">

                  <li class="dislike <?php if ($dislike_present == 1) { echo "active"; } ?>" id="post-dislike" data-id="<?php echo $post_id; ?>"><a class="icon-dislike-new" href="javascript:void(0)"></a>
                  <?php  ?><span class="dislike_count <?php if($dislike_like_count <1 ){  echo 'd-none'; } ?> "><?php  if($dislike_like_count>0){ echo $dislike_like_count; } ?></span> <?php  ?>
                  </li>
                  <input type="hidden" id="dislike" name="custId" value="<?php echo $dislike_present; ?>">

                  <li class="comment"><a class="icon-comment" data-id="<?php echo $post_id; ?>" href="javascript:void(0)"></a>
                     <?php  if($comments_count>0){ ?> <span><?php  echo $comments_count; ?></span><?php }?>
                  </li>

                  <li class="share a2a_counter share-<?php echo $post_id; ?>" data-id="<?php echo $post_id; ?>" data-toggle="modal" data-target="#exampleModal<?php echo $post_id; ?>">
                    <div class="icon-share">
                  </li>

                  <!-- Modal -->
                  <div class="modal fade share-modal-wrap" id="exampleModal<?php echo $post_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog share-modal modal-dialog-centered" role="document">
                      <div class="modal-content">

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>

                        <div class="modal-body">

                          <div class="a2a_kit a2a_kit_size_32 a2a_default_style share-option" data-a2a-url="<?php echo get_permalink(); ?>" data-a2a-title="<?php the_title(); ?>">
                            <a class="a2a_button_facebook"></a>
                            <a class="a2a_button_facebook_messenger"></a>
                            <a class="a2a_button_whatsapp"></a>
                            <a class="a2a_button_email"></a>
                            <a class="a2a_button_reddit"></a>
                            <a class="a2a_button_tumblr"></a>
                            <a class="a2a_button_linkedin"></a>
                            <a class="a2a_button_x"></a>
                            <a class="a2a_button_viber"></a>
                            <a class="a2a_button_threads"></a>
                            <a class="a2a_button_telegram"></a>
                            <a class="a2a_button_snapchat"></a>
                            <a class="a2a_button_sms"></a>
                            <a class="a2a_button_copy_link"></a>
                            <a class="a2a_dd a2a_counter" href="https://www.addtoany.com/share"></a>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                  <li>
                    <a class="icon-report" id="post-report" <?php if ($report_present == 1) { ?> style="color:#AB4D50;" <?php } ?> data-id="<?php echo $post_id; ?>" data-post-data-id="<?php echo $report_present; ?>" href="javascript:void(0)"></a>
                    <input type="hidden" id="report" name="custId" value="<?php echo $report_present; ?>">
                  </li>

                </ul>
                

                <div class="add-cmnt">
                  <a href="#" class="main-parent-comment" data-id="<?php echo $post_id; ?>"> <?php the_field('add_comment_text','option');?></a>
                </div>

                <div class="comment-block align-items-center" id="post_comment_box_<?php echo $post_id; ?>" style="display: none ; ">
                  <form method="post" class="comment-form">
                    <input type="hidden" name="comment_type" value="postcomment" />
                    <input type="hidden" name="comment_parent" value="" />
                    <input type="hidden" name="comment_post_ID" value="<?php echo get_the_ID(); ?>" />
                    <input type="hidden" name="comment_post_ID" value="<?php echo get_the_ID(); ?>" />
                    <input type="hidden" name="author" value="2" />

                    <input type="text" placeholder="<?php echo get_field('comment_author_input_box_placeholder', 'option') ?>" class="form-control" name="comment_author" id="comment-author">

                    <div class="send-comment">
                      <textarea class="form-control comment_box" placeholder="<?php echo get_field('comment_input_box_placeholder', 'option') ?>" name="comment" required></textarea>
                      <div class="send-icon submit_comment_btn" data-id="<?php echo $post_id; ?>">
                        <a href="javascript:void(0);"><span class="icon-send"></span></a>
                      </div>
                    </div>

                  </form>
                </div>

              </div>
            </div>
          </div>

          <?php if ($comments_count > 0) { ?>
          <div class="sub-comment-wrap">
            <?php
            // if (in_array($post_id, $post_id_array_forPostCommentShow)) {
              discussion_comment(get_the_ID(), $_POST['author_ip'], $comment_id_array_forCommentBox, $comment_id_array_forComment);
            //} ?>
          </div>
          <?php }?>
        </div>
        </div>


    <?php
      }
    endwhile;
    wp_reset_postdata();
  else : ?>
    <h1 style="text-align:center; color:white;"><?php echo get_field('no_post_found_text', 'option') ?></h1>
<?php endif;


  if ($post_name == "" && $total_post > $limit_per_page && $single_page_post_id == "" && $windowWidth>991) {
    $totalPages = ceil($total_post / $limit_per_page);
    $range = 2; // Number of pages to show before and after the current page
    $ellipsis = '...';
    echo '<ul class="pagination" id="pagination" >';
    // Previous page link
    if ($page_no > 1) {
      echo '<li class="page-item"><a class="prev page-numbers" id="' . ($page_no - 1) . '" href="#"><img src="' . get_template_directory_uri() . '/assets/images/prev.png"></a></li>';
    }

    // if ($totalPages > 3) {
    //   if ($page_no == $totalPages - 2) {
    //     // Pages before current page
    //     for ($i = $page_no - 1; $i < $page_no; $i++) {
    //       echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
    //     }
    //   } else if ($page_no == $totalPages - 1) {
    //     for ($i = $page_no - 2; $i < $page_no; $i++) {

    //       echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
    //     }
    //   } else if ($page_no == $totalPages) {
    //     for ($i = $page_no - 3; $i < $page_no; $i++) {
    //       echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
    //     }
    //   }
    // }

  // Total page lessthan 6 page

  if ( $totalPages <6 ) {
    // // Before current page 
    for ($i = 1 ; $i < $page_no; $i++ ) {
      echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
    }

    // Current page
    echo '<li class="page-item"><a class="page-numbers active" id="' . $page_no . '" href="#">' . $page_no . '</a></li>';

    // After current page 
    for ($i = $page_no+1 ; $i <=$totalPages; $i++) {
      echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
    }
  }

  
  if ( $totalPages >5 ) {

    if($page_no > $totalPages-3){

      echo '<li class="page-item"><a class="page-numbers" id="1" href="#">1</a></li>';

      echo '<li class="page-item"><a class="page-numbers" id="' . $page_no . '">' . $ellipsis . '</a></li>';

      // Before current page
      for ($i = $totalPages-2 ; $i < $page_no; $i++ ) {
        echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
      }
      // Current page
      echo '<li class="page-item"><a class="page-numbers active" id="' . $page_no . '" href="#">' . $page_no . '</a></li>';
      // After current page 
      for ($i = $page_no+1 ; $i <=$totalPages; $i++) {
        echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
      }
    }

    if($page_no <= $totalPages-3){  

      // Pages after current page
      if ($page_no < $totalPages - 1) {
        
          if($page_no >= $totalPages - 4 ){

              for ($i = $totalPages - 4; $i < $page_no; $i++) {
                echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
              }
              
              // Current page
              echo '<li class="page-item"><a class="page-numbers active" id="' . $page_no . '" href="#">' . $page_no . '</a></li>';  

              for ($i = $page_no + 1; $i < $totalPages; $i++) {
                echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
              }

          }else{
              // Current page
              echo '<li class="page-item"><a class="page-numbers active" id="' . $page_no . '" href="#">' . $page_no . '</a></li>';  

              for ($i = $page_no + 1; $i <= $page_no + 2; $i++) {
                echo '<li class="page-item"><a class="page-numbers" id="' . $i . '" href="#">' . $i . '</a></li>';
              }

              echo '<li class="page-item"><a class="page-numbers" id="' . $page_no . '" >' . $ellipsis . '</a></li>';
          }

      }

      // last page
      if ($page_no != $totalPages && $page_no != $totalPages - 2) {
        echo '<li class="page-item"><a class="page-numbers " id="' . $totalPages . '" href="#">' . $totalPages . '</a></li>';
      }

    }

  }

  // Next page link
  if ($page_no < $totalPages) {
    echo '<li class="page-item"><a class="next page-numbers" id="' . ($page_no + 1) . '" href="#"><img src="' . get_template_directory_uri() . '/assets/images/next.png"></a></li>';
  }

  }
  echo '
  <input type="hidden" id="hiddenInput" value=' . ($page_no + 1) . '
  </ul> ';


  die();
}

add_action('wp_ajax_nopriv_LoadPost', 'LoadPost');
add_action('wp_ajax_LoadPost', 'LoadPost');


// --------------------Post Like
function add_post_like_ajax_handler()
{
  global $wpdb;

  $table_name = $wpdb->prefix . 'like';

  $data = array(
    'like_post_ID' => $_POST['like_post_id'],
    'like_author_IP' => $_POST['like_author_ip'],
    'like_type' => $_POST['post_like_type'],
    // Add more columns and values as needed
  );

  // Prepare SQL query
  $query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
    $_POST['like_post_id'],
    $_POST['like_author_ip'],
    $_POST['post_like_type']
  );

  // Execute the query
  $result = $wpdb->get_results($query);

  if ($result == false) {
    $wpdb->insert($table_name, $data);

    // Optionally, you can check if the insertion was successful
    if ($wpdb->insert_id) {
    } else {
    }
    $query_delete_dislike = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_post_id'],
      $_POST['like_author_ip'],
      'DisLike'
    );
    $result_delete_dislike = $wpdb->get_results($query_delete_dislike);
    // echo "row deleted";

    // post meta like store
    $query_post_like = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'Like'
    );
    $post_like_count = $wpdb->get_var($query_post_like);
    update_post_meta($_POST['like_post_id'], '_total_like', $post_like_count);

    // post meta dislike store
    $query_post_dislike = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'DisLike'
    );
    $post_dislike_count = $wpdb->get_var($query_post_dislike);
    update_post_meta($_POST['like_post_id'], '_total_dislike', $post_dislike_count);
  } else {

    $query_delete = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_post_id'],
      $_POST['like_author_ip'],
      $_POST['post_like_type']
    );
    $result_delete = $wpdb->get_results($query_delete);


    // post meta like store
    $query_post_like2 = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'Like'
    );
    $post_like_count2 = $wpdb->get_var($query_post_like2);
    update_post_meta($_POST['like_post_id'], '_total_like', $post_like_count2);

    // post meta dislike store
    $query_post_dislike2 = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'DisLike'
    );
    $post_dislike_count2 = $wpdb->get_var($query_post_dislike2);
    update_post_meta($_POST['like_post_id'], '_total_dislike', $post_dislike_count2);
  }

  wp_die();
}
add_action('wp_ajax_nopriv_add_post_like', 'add_post_like_ajax_handler');
add_action('wp_ajax_add_post_like', 'add_post_like_ajax_handler');



// Post DisLike
function add_post_DisLike_ajax_handler()
{
  global $wpdb;

  $table_name = $wpdb->prefix . 'like';

  $data = array(
    'like_post_ID' => $_POST['like_post_id'],
    'like_author_IP' => $_POST['like_author_ip'],
    'like_type' => $_POST['post_like_type'],

  );

  // Prepare SQL query
  $query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
    $_POST['like_post_id'],
    $_POST['like_author_ip'],
    $_POST['post_like_type']
  );

  // Execute the query
  $result = $wpdb->get_results($query);

  if ($result == false) {
    $wpdb->insert($table_name, $data);

    // Optionally, you can check if the insertion was successful
    if ($wpdb->insert_id) {
      echo "Data inserted successfully!";
    } else {
      echo "Error inserting data!";
    }

    $query_delete_like = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_post_id'],
      $_POST['like_author_ip'],
      'Like'
    );
    $result_delete_like = $wpdb->get_results($query_delete_like);

    // post meta like store
    $query_post_like = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'Like'
    );
    $post_like_count = $wpdb->get_var($query_post_like);
    update_post_meta($_POST['like_post_id'], '_total_like', $post_like_count);

    // post meta dislike store
    $query_post_dislike = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'DisLike'
    );
    $post_dislike_count = $wpdb->get_var($query_post_dislike);
    update_post_meta($_POST['like_post_id'], '_total_dislike', $post_dislike_count);
  } else {

    $query_delete = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_post_id'],
      $_POST['like_author_ip'],
      $_POST['post_like_type']
    );
    $result_delete = $wpdb->get_results($query_delete);
    echo "row deleted";

    // post meta like store
    $query_post_like2 = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'Like'
    );
    $post_like_count2 = $wpdb->get_var($query_post_like2);
    update_post_meta($_POST['like_post_id'], '_total_like', $post_like_count2);

    // post meta dislike store
    $query_post_dislike2 = $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
      $_POST['like_post_id'],
      'DisLike'
    );
    $post_dislike_count2 = $wpdb->get_var($query_post_dislike2);
    update_post_meta($_POST['like_post_id'], '_total_dislike', $post_dislike_count2);
  }
  wp_die();
}
add_action('wp_ajax_nopriv_add_post_DisLike', 'add_post_DisLike_ajax_handler');
add_action('wp_ajax_add_post_DisLike', 'add_post_DisLike_ajax_handler');


// --------------------Post report --------------->
function add_post_report_ajax_handler()
{
  global $wpdb;

  $table_name = $wpdb->prefix . 'report'; // replace 'your_table_name' with the actual table name 

  $data = array(
    'report_post_ID' => $_POST['report_post_id'],
    'report_author_IP' => $_POST['report_author_ip'],
    'report_type' => $_POST['post_report_type'],
    // Add more columns and values as needed
  );

  // Prepare SQL query
  $query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE report_post_ID = %s AND report_author_IP = %s AND report_type = %s",
    $_POST['report_post_id'],
    $_POST['report_author_ip'],
    $_POST['post_report_type']
  );

  // Execute the query
  $result = $wpdb->get_results($query);

  if ($result == false) {
    $wpdb->insert($table_name, $data);

    // Optionally, you can check if the insertion was successful
    if ($wpdb->insert_id) {
      echo "Data inserted successfully!";
    } else {
      echo "Error inserting data!";
    }
  } else {

    $query_delete = $wpdb->prepare(
      "DELETE FROM $table_name WHERE report_post_ID = %s AND report_author_IP = %s AND report_type = %s",
      $_POST['report_post_id'],
      $_POST['report_author_ip'],
      $_POST['post_report_type']
    );
    $result_delete = $wpdb->get_results($query_delete);
    echo "row deleted";
  }
  // post meta report store
  $query_post_report = $wpdb->prepare(
    "SELECT COUNT(*) FROM $table_name WHERE report_post_ID = %s AND report_type = %s",
    $_POST['report_post_id'],
    'report'
  );
  $post_report_count = $wpdb->get_var($query_post_report);
  update_post_meta($_POST['report_post_id'], '_total_report', $post_report_count);
  wp_die(); // this is required to terminate immediately and return a proper response

}
add_action('wp_ajax_nopriv_add_post_report', 'add_post_report_ajax_handler');
add_action('wp_ajax_add_post_report', 'add_post_report_ajax_handler');


// Add comment and sub comment
add_action('wp_ajax_nopriv_addcomment', 'addcomment');
add_action('wp_ajax_addcomment', 'addcomment');

function addcomment()
{
  $comment_author = '';
  if ($_POST['comment_author'] == '') {
    $comment_author = get_field('comment_author_name', 'option');
  } else {
    $comment_author = $_POST['comment_author'];
  }

  $comment_content = $_POST['comment'];
  $max_word_count = 1200;
  $trimmed_content = wp_trim_words($comment_content, $max_word_count, '');

  $commentArray = array(
    'comment_author' => $comment_author,
    'comment_content' => $trimmed_content,
    'comment_parent' => $_POST['comment_parent'],
    'comment_post_ID' => $_POST['comment_post_ID'],
    'user_id' => 2
  );
  if ($_POST['comment'] != "") {
    $commentId = wp_insert_comment($commentArray);
  }

  $last_inserted_comment = get_comment($commentId);
  $parentcmnt = ($last_inserted_comment->comment_parent == 0) ? $commentId : $last_inserted_comment->comment_parent;
  $commenthtml = '<div class="sub-comment" id="comment-' . $parentcmnt . '">
<div class="top-comment-each children">
<ul class="user-info d-flex justify-content-between">
<li>Anonymous</li>
<li>' . get_the_date('d/m/Y') . '</li>
</ul>
<div class="post-top-text">
' . $last_inserted_comment->comment_content . '  
</div>
<div class="reply-block">
<div class="reply-bottom-info">
<ul class="icon-group d-flex align-items-end list-unstyled">
<li><a class="icon-like-new" href="javascript:void(0)"></a><span>0</span></li>
<li><a class="icon-dislike-new" href="javascript:void(0)"></a><span>0</span></li>
<li><a class="icon-comment" href="javascript:void(0)"></a><span>0</span></li>
<li><a class="icon-share" href="javascript:void(0)"></a><span>0</span></li>
<li><a class="icon-report" href="javascript:void(0)"></a><span>0</span></li>
</ul>
<div class="comment-block d-flex align-items-center">
<form method="post" class="comment-form">
<input type="hidden" name="comment_type" value="commentreply" />
<input type="hidden" name="comment_parent" value="' . $commentId . '">
<input type="hidden" name="comment_post_ID" value="' . $last_inserted_comment->comment_post_ID . '">
<input type="hidden" name="author" value="2">
<textarea class="form-control" placeholder="Write your comments" name="comment" required></textarea>
<div class="send-icon submit_comment_btn">
<a href="javascript:void(0);"><span class="icon-send"></span></a>
</div>
</form>
</div>
</div>
</div>              
</div> 
</div>';

echo $commenthtml;

  // if ($_POST['comment'] != '') {
  //   echo 'success';
  // }
  die();
}


// --------------------comment Like
function add_comment_like_ajax_handler()
{
  global $wpdb;

  $table_name = $wpdb->prefix . 'comment_like'; // replace 'your_table_name' with the actual table name 

  $data = array(
    'like_comment_ID' => $_POST['like_comment_id'],
    'like_author_IP' => $_POST['like_author_ip'],
    'like_type' => $_POST['comment_like_type'],
    // Add more columns and values as needed
  );

  // Prepare SQL query
  $query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
    $_POST['like_comment_id'],
    $_POST['like_author_ip'],
    $_POST['comment_like_type']
  );

  // Execute the query
  $result = $wpdb->get_results($query);

  if ($result == false) {
    $wpdb->insert($table_name, $data);

    // Optionally, you can check if the insertion was successful
    if ($wpdb->insert_id) {
      echo "Data inserted successfully!";
    } else {
      echo "Error inserting data!";
    }
    $query_delete_dislike = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_comment_id'],
      $_POST['like_author_ip'],
      'DisLike'
    );
    $result_delete_dislike = $wpdb->get_results($query_delete_dislike);
    echo "row deleted";
  } else {

    $query_delete = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_comment_id'],
      $_POST['like_author_ip'],
      $_POST['comment_like_type']
    );
    $result_delete = $wpdb->get_results($query_delete);
    echo "row deleted";
  }
  wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_nopriv_add_comment_like', 'add_comment_like_ajax_handler');
add_action('wp_ajax_add_comment_like', 'add_comment_like_ajax_handler');

// --------------------comment disLike -------------->
function add_comment_DisLike_ajax_handler()
{
  global $wpdb;

  $table_name = $wpdb->prefix . 'comment_like'; // replace 'your_table_name' with the actual table name 

  $data = array(
    'like_comment_ID' => $_POST['like_comment_id'],
    'like_author_IP' => $_POST['like_author_ip'],
    'like_type' => $_POST['comment_like_type'],
    // Add more columns and values as needed
  );

  // Prepare SQL query
  $query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
    $_POST['like_comment_id'],
    $_POST['like_author_ip'],
    $_POST['comment_like_type']
  );

  // Execute the query
  $result = $wpdb->get_results($query);

  if ($result == false) {
    $wpdb->insert($table_name, $data);

    // Optionally, you can check if the insertion was successful
    if ($wpdb->insert_id) {
      echo "Data inserted successfully!";
    } else {
      echo "Error inserting data!";
    }

    $query_delete_like = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_comment_id'],
      $_POST['like_author_ip'],
      'Like'
    );
    $result_delete_like = $wpdb->get_results($query_delete_like);
  } else {

    $query_delete = $wpdb->prepare(
      "DELETE FROM $table_name WHERE like_comment_ID = %s AND like_author_IP = %s AND like_type = %s",
      $_POST['like_comment_id'],
      $_POST['like_author_ip'],
      $_POST['comment_like_type']
    );
    $result_delete = $wpdb->get_results($query_delete);
    echo "row deleted";
  }
  wp_die();
}
add_action('wp_ajax_nopriv_add_comment_DisLike', 'add_comment_DisLike_ajax_handler');
add_action('wp_ajax_add_comment_DisLike', 'add_comment_DisLike_ajax_handler');


// --------------------comment report --------------->
function add_comment_report_ajax_handler()
{
  global $wpdb;

  $table_name = $wpdb->prefix . 'comment_report'; // replace 'your_table_name' with the actual table name 

  $data = array(
    'report_comment_ID' => $_POST['report_comment_id'],
    'report_author_IP' => $_POST['report_author_ip'],
    'report_type' => $_POST['comment_report_type'],
    // Add more columns and values as needed
  );

  // Prepare SQL query
  $query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE report_comment_ID = %s AND report_author_IP = %s AND report_type = %s",
    $_POST['report_comment_id'],
    $_POST['report_author_ip'],
    $_POST['comment_report_type']
  );

  // Execute the query
  $result = $wpdb->get_results($query);

  if ($result == false) {
    $wpdb->insert($table_name, $data);

    // Optionally, you can check if the insertion was successful
    if ($wpdb->insert_id) {
      echo "Data inserted successfully!";
    } else {
      echo "Error inserting data!";
    }
  } else {

    $query_delete = $wpdb->prepare(
      "DELETE FROM $table_name WHERE report_comment_ID = %s AND report_author_IP = %s AND report_type = %s",
      $_POST['report_comment_id'],
      $_POST['report_author_ip'],
      $_POST['comment_report_type']
    );
    $result_delete = $wpdb->get_results($query_delete);
    echo "row deleted";
  }

  wp_die(); // this is required to terminate immediately and return a proper response

}
add_action('wp_ajax_nopriv_add_comment_report', 'add_comment_report_ajax_handler');
add_action('wp_ajax_add_comment_report', 'add_comment_report_ajax_handler');


// SVG format supporter
add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
  $filetype = wp_check_filetype($filename, $mimes);
  return [
    'ext' => $filetype['ext'],
    'type' => $filetype['type'],
    'proper_filename' => $data['proper_filename']
  ];
}, 10, 4);

function cc_mime_types($mimes)
{
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

function fix_svg()
{
  echo '<style type="text/css">
		  .attachment-266x266, .thumbnail img {
			   width: 100% !important;
			   height: auto !important;
		  }
		  </style>';
}
add_action('admin_head', 'fix_svg');



// function for archive page
function LoadPost_archive_post(){
  
  global $wpdb;


  $post_id_array_forPostCommentBox = $_POST["post_id_array_forPostCommentBox"];
  $post_id_array_forPostCommentShow = $_POST["post_id_array_forPostCommentShow"];

  $comment_id_array_forCommentBox = $_POST["comment_id_array_forCommentBox"];
  $comment_id_array_forComment = $_POST["comment_id_array_forComment"];
  $date = $_POST["archive_date"];

  $args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'date_query' => array(
        array(
            'year'  => date('Y', strtotime($date)),
            'month' => date('n', strtotime($date)),
            'day'   => date('j', strtotime($date)),
        ),
    ),
  );


  $query = new WP_Query($args); 

  if ($query->have_posts()) :
    while ($query->have_posts()) :
      $query->the_post();

      $post_id = get_the_ID();

        $unique_id = get_post_meta(get_the_ID(), 'post_id', true);
        // Get the content
        $content = get_the_content();
        $content = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $content);

        $excerpt = wp_trim_words($content, 100); // Display first100 words as excerpt
        $excerpt = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $excerpt);

        // report count
        $table_name2 = $wpdb->prefix . 'report';
        $query_report_count1 = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_type = %s",
          $post_id,
          'report'
        );
        $report_count1 = $wpdb->get_var($query_report_count1);
        // report present
        $query_report_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_author_IP = %s AND report_type = %s",
          $post_id,
          $_POST['author_ip'],
          'report'
        );
        $report_present = $wpdb->get_var($query_report_present);
        // Like present check 
        $table_name = $wpdb->prefix . 'like';
        $query_like_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'Like'
        );
        $like_present = $wpdb->get_var($query_like_present);
        // like count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'Like'
        );
        $like_count = $wpdb->get_var($query_like_count);
        $extra_like_count = get_post_meta(get_the_ID(), 'add_extra_like_count', true);
        if(!empty($extra_like_count)){
        $like_count += $extra_like_count;
        }

        // dislike present
        $query_dislike_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'DisLike'
        );
        $dislike_present = $wpdb->get_var($query_dislike_present);
        // dislike count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'DisLike'
        );
        $dislike_like_count = $wpdb->get_var($query_like_count);
        $extra_Dislike_count = get_post_meta(get_the_ID(), 'add_extra_dislike_count', true);
        if(!empty($extra_Dislike_count)){
        $dislike_like_count += $extra_Dislike_count;
        }


        // get total comment
        $args = array(
          'post_id' => $post_id,
          'count' => true
        );
        $comments_count = get_comments($args);
      ?>

        <div class="card-wrap post-each light-gray-bg parent-comment-<?php echo $post_id; ?> <?php if ($comments_count > 0) {
          echo 'has-comment';} else {echo 'no-comment';}echo ' ', $post_id; ?>">

          <?php if ($comments_count > 0) { ?> <div class="comment-border"></div> <?php } ?>
          <div class="main-post-block <?php echo ($comments_count > 0) ? 'hascomment' : 'nocomment'; ?>">
          <div class="top-comment-each">
            <ul class="user-info d-flex justify-content-between">
              <li>
                <?php echo $unique_id;
                $meta_value = get_post_meta($post_id, 'sticky', true);
                if ($meta_value == 1) { ?>  <?php } ?>
                <!-- <span class="icon-pin"></span> -->
              </li>
              <li>
                <?php echo date('d/m/Y', get_the_time('U')); ?>
                <?php //echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ';
                //echo get_field('post_&_comment_how_many_days_old_text', 'option'); 
                ?>
              </li>
            </ul>

            <div class="author-name" ><h4><?php $custom_field_value = get_post_meta(get_the_ID(), 'past_author_name', true);
              if (!empty($custom_field_value)) { echo $custom_field_value;}else{ echo "Anonymous"; } ?></h4>
            </div>

            <div class="post-top-text" <?php if ($report_count1 >= 15) { ?>style="display: none;" <?php } ?>>

              <div class="post-content ">
                <div class="excerpt">
                  <p>
                    <?php echo $excerpt;
                    $wordCount = str_word_count($excerpt);
                    if ($wordCount > 100) { ?> <a href="javascript:void(0)" class="read-more"><?php echo get_field('read_more_button_text', 'option'); ?></a>
                    <?php } ?>
                  </p>
                </div>

                <div class="full-content" style="display: none;">
                  <p>
                    <?php echo $content; ?><a href="javascript:void(0)" class="read-less"><?php echo get_field('read_less_button_text', 'option'); ?></a>
                  </p>
                </div>

              </div>
            </div>

            <?php if ($report_count1 >= 15) { ?>
              <span class="read-hidden-read" style="display: flex;">
                <p><?php echo get_field('hidden_post_text_', 'option'); ?> </p>
                <a href="javascript:void(0)" class="read-hidden">
                  <?php echo get_field('hidden_post_display_button_text', 'option'); ?>
                </a>
              </span>
            <?php } ?>
            <div class="reply-block">
              <ul class="post-view d-flex justify-content-end">
                <li>
                  <?php
                  // $totalReaction = $like_count + $dislike_like_count;
                  // if ($totalReaction > 0 && $totalReaction < 2) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_less_than_2_reaction_text', 'option');
                  // } else if ($totalReaction > 1) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_greater_than_two_reaction_text', 'option');
                  // }
                  ?>
                </li>
              </ul>
              <div class="reply-bottom-info">
                <ul class="icon-group d-flex list-unstyled">
                  <?php if ($comments_count > 0) { ?>
                    <!-- <li class="show-comment"><a href="javascript:void(0)" class="post_comment_display <?php if (in_array($post_id, $post_id_array_forPostCommentShow)) {
                    echo "open"; } else { echo "close"; } ?>" data-id="<?php echo $post_id; ?>"></a>
                    </li>  -->
                  <?php } ?>

                  <li class="like  <?php if ($like_present == 1) { echo 'active'; } ?>" id="post-like" data-id="<?php echo $post_id; ?>" >
                    <a class="icon-like-new" href="javascript:void(0)"></a>
                    <?php  ?><span class="like-count"><?php if($like_count>0){ echo $like_count; } ?></span><?php ?>
                  </li>
                  <input type="hidden" id="like" name="custId" value="<?php echo $like_present; ?>">

                  <li class="dislike <?php if ($dislike_present == 1) {  echo 'active';  } ?>" id="post-dislike" data-id="<?php echo $post_id; ?>">
                    <a class="icon-dislike-new" href="javascript:void(0)"></a>
                    <?php  ?><span class="dislike_count"> <?php if($dislike_like_count>0){ echo $dislike_like_count; }?></span><?php ?>
                  </li>
                  <input type="hidden" id="dislike" name="custId" value="<?php echo $dislike_present; ?>">

                  <li class="comment"><a href="<?php the_permalink();?>" class=" comment icon-comment " data-id="<?php echo $post_id; ?>" href="javascript:void(0)"></a>
                    <?php if($comments_count>0){ ?> <span><?php echo $comments_count; ?></span><?php }?>
                  </li>

                  <li class="share a2a_counter share-<?php echo $post_id; ?>" data-id="<?php echo $post_id; ?>" data-toggle="modal" data-target="#exampleModal<?php echo $post_id; ?>">
                    <div class="icon-share">
                  </li>

                  <!-- Modal -->
                  <div class="modal fade share-modal-wrap" id="exampleModal<?php echo $post_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog share-modal modal-dialog-centered" role="document">
                      <div class="modal-content">

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>

                        <div class="modal-body">
                          <div class="a2a_kit a2a_kit_size_32 a2a_default_style share-option" data-a2a-url="<?php echo get_permalink(); ?>" data-a2a-title="<?php the_title(); ?>">
                            <a class="a2a_button_facebook"></a>
                            <a class="a2a_button_facebook_messenger"></a>
                            <a class="a2a_button_whatsapp"></a>
                            <a class="a2a_button_email"></a>
                            <a class="a2a_button_reddit"></a>
                            <a class="a2a_button_tumblr"></a>
                            <a class="a2a_button_linkedin"></a>
                            <a class="a2a_button_x"></a>
                            <a class="a2a_button_viber"></a>
                            <a class="a2a_button_threads"></a>
                            <a class="a2a_button_telegram"></a>
                            <a class="a2a_button_snapchat"></a>
                            <a class="a2a_button_sms"></a>
                            <a class="a2a_button_copy_link"></a>
                            <a class="a2a_dd a2a_counter" href="https://www.addtoany.com/share"></a>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>

                  <li>
                    <a class="icon-report" id="post-report" <?php if ($report_present == 1) { ?> style="color:#AB4D50;" <?php } ?> data-id="<?php echo $post_id; ?>" data-post-data-id="<?php echo $report_present; ?>" href="javascript:void(0)"></a>
                    <input type="hidden" id="report" name="custId" value="<?php echo $report_present; ?>">
                  </li>

                </ul>

              </div>
            </div>
          </div>

            <?php
            if (in_array($post_id, $post_id_array_forPostCommentShow)) {
              discussion_comment(get_the_ID(), $_POST['author_ip'], $comment_id_array_forCommentBox, $comment_id_array_forComment);
            } ?>

        </div>
        </div>


      <?php
      
    endwhile;
    wp_reset_postdata();
  else : ?>
    <h1 style="text-align:center; color:white;"><?php echo get_field('no_post_found_for_selected_date_text', 'option') ?></h1>
<?php endif;


  die();
}

add_action('wp_ajax_nopriv_LoadPost_archive_post', 'LoadPost_archive_post');
add_action('wp_ajax_LoadPost_archive_post', 'LoadPost_archive_post');



// function for tag page
function LoadPost_tag_post(){
  
  global $wpdb;


  $post_id_array_forPostCommentBox = $_POST["post_id_array_forPostCommentBox"];
  $post_id_array_forPostCommentShow = $_POST["post_id_array_forPostCommentShow"];

  $comment_id_array_forCommentBox = $_POST["comment_id_array_forCommentBox"];
  $comment_id_array_forComment = $_POST["comment_id_array_forComment"];
  $tag = $_POST["archive_tag"];

    // Define the tag slug
    $tag_slug = $tag;

    // Get the tag object
    $tag = get_term_by('slug', $tag_slug, 'post_tag');

    // Define the query arguments
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1, // -1 to display all posts
        'tax_query' => array(
            array(
                'taxonomy' => 'post_tag',
                'field' => 'id',
                'terms' => $tag->term_id,
            ),
        ),
    );



  $query = new WP_Query($args); 

  if ($query->have_posts()) :
    while ($query->have_posts()) :
      $query->the_post();

      $post_id = get_the_ID();

        $unique_id = get_post_meta(get_the_ID(), 'post_id', true);
        // Get the content
        $content = get_the_content();
        $content = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $content);

        $excerpt = wp_trim_words($content, 100); // Display first100 words as excerpt
        $excerpt = preg_replace('/#(\w+)/u', '<a href="tag-post/?tag=$1">#$1</a>', $excerpt);


        // report count
        $table_name2 = $wpdb->prefix . 'report';
        $query_report_count1 = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_type = %s",
          $post_id,
          'report'
        );
        $report_count1 = $wpdb->get_var($query_report_count1);
        // report present
        $query_report_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name2 WHERE report_post_ID = %s AND report_author_IP = %s AND report_type = %s",
          $post_id,
          $_POST['author_ip'],
          'report'
        );
        $report_present = $wpdb->get_var($query_report_present);
        // Like present check 
        $table_name = $wpdb->prefix . 'like';
        $query_like_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'Like'
        );
        $like_present = $wpdb->get_var($query_like_present);
        // like count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'Like'
        );
        $like_count = $wpdb->get_var($query_like_count);
        $extra_like_count = get_post_meta(get_the_ID(), 'add_extra_like_count', true);
        if(!empty($extra_like_count)){
        $like_count += $extra_like_count;
        }

        // dislike present
        $query_dislike_present = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_author_IP = %s AND like_type = %s",
          $post_id,
          $_POST['author_ip'],
          'DisLike'
        );
        $dislike_present = $wpdb->get_var($query_dislike_present);
        // dislike count
        $query_like_count = $wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE like_post_ID = %s AND like_type = %s",
          $post_id,
          'DisLike'
        );
        $dislike_like_count = $wpdb->get_var($query_like_count);
        $extra_Dislike_count = get_post_meta(get_the_ID(), 'add_extra_dislike_count', true);
        if(!empty($extra_Dislike_count)){
        $dislike_like_count += $extra_Dislike_count;
        }


        // get total comment
        $args = array(
          'post_id' => $post_id,
          'count' => true
        );
        $comments_count = get_comments($args);
      ?>

        <div class="card-wrap post-each light-gray-bg parent-comment-<?php echo $post_id; ?> <?php if ($comments_count > 0) {
          echo 'has-comment';} else {echo 'no-comment';}echo ' ', $post_id; ?>">

          <?php if ($comments_count > 0) { ?> <div class="comment-border"></div> <?php } ?>
          <div class="main-post-block <?php echo ($comments_count > 0) ? 'hascomment' : 'nocomment'; ?>">
          <div class="top-comment-each">
            <ul class="user-info d-flex justify-content-between">
              <li>
                <?php echo $unique_id;
                $meta_value = get_post_meta($post_id, 'sticky', true);
                if ($meta_value == 1) { ?>  <?php } ?>
                <!-- <span class="icon-pin"></span> -->
              </li>
              <li>
                <?php echo date('d/m/Y', get_the_time('U')); ?>
                <?php //echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ';
                //echo get_field('post_&_comment_how_many_days_old_text', 'option'); 
                ?>
              </li>
            </ul>

            <div class="author-name" ><h4><?php $custom_field_value = get_post_meta(get_the_ID(), 'past_author_name', true);
              if (!empty($custom_field_value)) { echo $custom_field_value;}else{ echo "Anonymous"; } ?></h4>
            </div>

            <div class="post-top-text" <?php if ($report_count1 >= 15) { ?>style="display: none;" <?php } ?>>

              <div class="post-content ">
                <div class="excerpt">
                  <p>
                    <?php echo $excerpt;
                    $wordCount = str_word_count($excerpt);
                    if ($wordCount > 100) { ?> <a href="javascript:void(0)" class="read-more"><?php echo get_field('read_more_button_text', 'option'); ?></a>
                    <?php } ?>
                  </p>
                </div>

                <div class="full-content" style="display: none;">
                  <p>
                    <?php echo $content; ?><a href="javascript:void(0)" class="read-less"><?php echo get_field('read_less_button_text', 'option'); ?></a>
                  </p>
                </div>

              </div>
            </div>

            <?php if ($report_count1 >= 15) { ?>
              <span class="read-hidden-read" style="display: flex;">
                <p><?php echo get_field('hidden_post_text_', 'option'); ?> </p>
                <a href="javascript:void(0)" class="read-hidden">
                  <?php echo get_field('hidden_post_display_button_text', 'option'); ?>
                </a>
              </span>
            <?php } ?>
            <div class="reply-block">
              <ul class="post-view d-flex justify-content-end">
                <li>
                  <?php
                  // $totalReaction = $like_count + $dislike_like_count;
                  // if ($totalReaction > 0 && $totalReaction < 2) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_less_than_2_reaction_text', 'option');
                  // } else if ($totalReaction > 1) {
                  //   echo $totalReaction . ' ';
                  //   echo get_field('for_greater_than_two_reaction_text', 'option');
                  // }
                  ?>
                </li>
              </ul>
              <div class="reply-bottom-info">
                <ul class="icon-group d-flex list-unstyled">
                  <?php if ($comments_count > 0) { ?>
                    <!-- <li class="show-comment"><a href="javascript:void(0)" class="post_comment_display <?php if (in_array($post_id, $post_id_array_forPostCommentShow)) {
                    echo "open"; } else { echo "close"; } ?>" data-id="<?php echo $post_id; ?>"></a>
                    </li>  -->
                  <?php } ?>

                  <li class="like  <?php if ($like_present == 1) { echo 'active'; } ?>" id="post-like" data-id="<?php echo $post_id; ?>" >
                    <a class="icon-like-new" href="javascript:void(0)"></a>
                    <?php  ?><span class="like-count"><?php if($like_count>0){ echo $like_count; } ?></span><?php ?>
                  </li>
                  <input type="hidden" id="like" name="custId" value="<?php echo $like_present; ?>">

                  <li class="dislike <?php if ($dislike_present == 1) {  echo 'active';  } ?>" id="post-dislike" data-id="<?php echo $post_id; ?>">
                    <a class="icon-dislike-new" href="javascript:void(0)"></a>
                    <?php  ?><span class="dislike_count"> <?php if($dislike_like_count>0){ echo $dislike_like_count; }?></span><?php ?>
                  </li>
                  <input type="hidden" id="dislike" name="custId" value="<?php echo $dislike_present; ?>">

                  <li class="comment"><a href="<?php the_permalink();?>" class=" comment icon-comment " data-id="<?php echo $post_id; ?>" href="javascript:void(0)"></a>
                    <?php if($comments_count>0){ ?> <span><?php echo $comments_count; ?></span><?php }?>
                  </li>

                  <li class="share a2a_counter share-<?php echo $post_id; ?>" data-id="<?php echo $post_id; ?>" data-toggle="modal" data-target="#exampleModal<?php echo $post_id; ?>">
                    <div class="icon-share">
                  </li>

                  <!-- Modal -->
                  <div class="modal fade share-modal-wrap" id="exampleModal<?php echo $post_id; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog share-modal modal-dialog-centered" role="document">
                      <div class="modal-content">

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>

                        <div class="modal-body">
                          <div class="a2a_kit a2a_kit_size_32 a2a_default_style share-option" data-a2a-url="<?php echo get_permalink(); ?>" data-a2a-title="<?php the_title(); ?>">
                            <a class="a2a_button_facebook"></a>
                            <a class="a2a_button_facebook_messenger"></a>
                            <a class="a2a_button_whatsapp"></a>
                            <a class="a2a_button_email"></a>
                            <a class="a2a_button_reddit"></a>
                            <a class="a2a_button_tumblr"></a>
                            <a class="a2a_button_linkedin"></a>
                            <a class="a2a_button_x"></a>
                            <a class="a2a_button_viber"></a>
                            <a class="a2a_button_threads"></a>
                            <a class="a2a_button_telegram"></a>
                            <a class="a2a_button_snapchat"></a>
                            <a class="a2a_button_sms"></a>
                            <a class="a2a_button_copy_link"></a>
                            <a class="a2a_dd a2a_counter" href="https://www.addtoany.com/share"></a>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>

                  <li>
                    <a class="icon-report" id="post-report" <?php if ($report_present == 1) { ?> style="color:#AB4D50;" <?php } ?> data-id="<?php echo $post_id; ?>" data-post-data-id="<?php echo $report_present; ?>" href="javascript:void(0)"></a>
                    <input type="hidden" id="report" name="custId" value="<?php echo $report_present; ?>">
                  </li>

                </ul>

              </div>
            </div>
          </div>

            <?php
            if (in_array($post_id, $post_id_array_forPostCommentShow)) {
              discussion_comment(get_the_ID(), $_POST['author_ip'], $comment_id_array_forCommentBox, $comment_id_array_forComment);
            } ?>

        </div>
        </div>


      <?php
      
    endwhile;
    wp_reset_postdata();
  else : ?>
    <h1 style="text-align:center; color:white;"><?php echo get_field('no_post_found_for_selected_date_text', 'option') ?></h1>
<?php endif;


  die();
}

add_action('wp_ajax_nopriv_LoadPost_tag_post', 'LoadPost_tag_post');
add_action('wp_ajax_LoadPost_tag_post', 'LoadPost_tag_post');