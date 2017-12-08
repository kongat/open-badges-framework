<?php
/**
 * This is the ajax file.
 *
 * @author     Nicolas TORION
 * @package    custom_ajax.php
 * @subpackage includes/ajax
 * @since      0.6.3
 */

require_once '../../../../../wp-load.php';

require_once plugin_dir_path(dirname(__FILE__)) . 'utils/functions.php';
//mimic the actuall admin-ajax
define('DOING_AJAX', true);

if (!isset($_POST['action'])) {
    die('-1');
}

//Typical headers
header('Content-Type: text/html');
send_nosniff_header();

//Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');

$action = esc_attr(trim($_POST['action']));

//A bit of security
$allowed_actions = array(
    'action_save_metabox_students',
    'action_languages_form',
    'get_right_levels',
    'action_select_class',
    'action_select_badge',
    'action_save_comment',
    'action_select_description_preview',
    'send_message_badge',
);

/**
 * AJAX action to save metabox of students in class job listing type.
 *
 * @author Nicolas TORION
 * @since  0.4.1
 */
add_action('CUSTOMAJAX_action_save_metabox_students', 'action_save_metabox_students');

function action_save_metabox_students() {
    $post_id = $_POST['post_id'];
    update_post_meta($post_id, '_class_students', $_POST['class_students']);
    echo $_POST['class_students'];
}


/**
 * AJAX action to load all languages in a select form
 *
 * @author Nicolas TORION
 * @since  0.6.1
 */
add_action('CUSTOMAJAX_action_languages_form', 'action_languages_form');
function action_languages_form() {
    display_fieldEdu($category = $_POST['slug']);
}


/**
 * This function return all the level that contain badges of the
 * same field selected in the first step of the send badge form.
 *
 * @author Alessandro RICCARDI
 * @since  0.6.3
 */
add_action('CUSTOMAJAX_get_right_levels', 'get_right_levels');

function get_right_levels() {
    $fieldEdu = $_POST['fieldEdu'];
    $levels = get_all_levels($fieldEdu);

    // Display the level ...
    display_sendBadges_info("Select one of the below levels");

    foreach ($levels as $level) {

        echo '<div class="rdi-tab">';
        echo '<label class="radio-label" for="level_' . $level . '">' . $level . ' </label><input type="radio" class="radio-input level" name="level" id="level_' . $level . '" value="' . $level . '"> ';
        echo '</div>';
    }
}


/**
 * AJAX action to load the badges of the level given.
 *
 * @author Nicolas TORION
 * @since  0.6.2
 * @since  0.6.3 recoded and made it easy
 */
add_action('CUSTOMAJAX_action_select_badge', 'action_select_badge');
function action_select_badge() {
    global $current_user;
    $badges = get_all_badges();
    $form = $_POST['form'];
    $lang = $_POST['fieldEdu'];
    $level = $_POST['level'];

    // Get user information
    wp_get_current_user();

    display_sendBadges_info("Select one of the below badges");


    if (check_the_rules("administrator", "academy", "editor")) {
        $badges_corresponding = get_all_badges_level($badges, $lang, $level, $certification = true);
    } else {
        $badges_corresponding = get_all_badges_level($badges, $lang, $level);
    }

    // Sort an array by values using a user-defined comparison function
    usort($badges_corresponding, function ($a, $b) {
        return strcmp($a->post_title, $b->post_title);
    });

    foreach ($badges_corresponding as $badge) { ?>
        <!-- HTML -->
        <div class="cont-badge-sb">
        <input type="radio" name="input_badge_name" class="input-badge input-hidden"
               id="<?php echo $form . $badge->post_title; ?>"
               value="<?php echo $badge->post_name; ?>"/>
    <label for="<?php echo $form . $badge->post_title; ?>">
            <img class="img-send-badge" src="<?php

        if (get_the_post_thumbnail_url($badge->ID)) {
            // Badge WITH image
            echo get_the_post_thumbnail_url($badge->ID, 'thumbnail');
            echo '" /> </label>';
            echo '</br> <b>' . $badge->post_title.'</b>';
            echo "</b>";
        } else {
            // Badge WITHOUT image
            echo plugins_url('../../assets/default-badge-thumbnail.png', __FILE__);
            echo '" width="40px" height="40px" /></label>';
            echo '</br><b>' . $badge->post_title .'</b>';

        }
        echo "</div>";
    }
}

/**
 * AJAX action to load a preview of the description selected in a select form
 *
 * @author Nicolas TORION
 * @since  0.6.2
 */
add_action('CUSTOMAJAX_action_select_description_preview', 'action_select_description_preview');
function action_select_description_preview() {
    $badgeName = $_POST['badge_name'];
    $langDesc = $_POST['language_description'];

    display_sendBadges_info("Select the language of the badge, if you cannot select it, the below text it will be used.");

    $badges = get_all_badges();
    foreach ($badges as $badge) {
        if ($badgeName == $badge->post_name) {
            $badge_description = get_badge_descriptions($badge)[$langDesc];
            echo str_replace("\n", "<br>", "<p>" . $badge_description . "</p><br>");
        }
    }
}


/**
 * AJAX action to load the classes corresponding to the level and the language selected
 *
 * @author Nicolas TORION
 * @since  0.6.3
 */
add_action('CUSTOMAJAX_action_select_class', 'action_select_class');
function action_select_class() {
    global $current_user;
    $settings = new SaveSetting();
    $fieldEducation = $_POST['language_selected'];
    wp_get_current_user();

    display_sendBadges_info("Select one of the below classes (by default is selected your default class)");

    // Get the class from the Plugin = wp-job-manager
    if (is_plugin_active("WP-Job-Manager-master/wp-job-manager.php")) {
        if (check_the_rules("administrator", "editor")) {
            $classes = get_classes_plugin();
            $classes_job_listing = get_classes_job_listing();
            $classes = array_merge($classes, $classes_job_listing);
        } elseif (check_the_rules("academy")) {
            $classes = get_classes_teacher($current_user->user_login);
        } elseif (check_the_rules("teacher")) {
            $classes = get_class_teacher($current_user->user_login);
        }
    }

    if(empty($classes) && check_the_rules("administrator", "editor")) {
        echo 'You don\'t have classes, create a new one!';
    }

    if (check_the_rules("administrator", "editor")) {
        echo '</br><b>Default Class:</b><br><br>';
        foreach ($classes as $class) {
            if ($class->post_type == 'class') {
                echo '<div class="rdi-tab">';
                echo '<label for="class_' . $class->ID . '">' . $class->post_title . ' </label>
                        <input type="radio" name="class_for_student" id="class_' . $class->ID . '" value="' . $class->ID . '"/>';
                echo '</div>';
            }
        }
        echo '</br>';
    }

    if (check_the_rules("administrator", "academy", "editor")) {
        $first = true;
        foreach ($classes as $class) {
            if ($class->post_type == 'job_listing') {
                $fields = get_the_terms($class->ID, 'job_listing_category');

                // Checking if the class of the class is the same of the badge that we want to send.
                if ($fieldEducation == $fields[0]->name) {
                    if ($first){
                        // The first time it will be printed the title
                        echo '<br><b>Specific Class:</b><br><br>';
                        $first = false;
                    }
                    // Printing of the Job listing CLASS
                    echo '<div class="rdi-tab">';
                    echo '<label for="class_' . $class->ID . '">' . $class->post_title . ' </label><input type="radio" name="class_for_student" id="class_' . $class->ID . '" value="' . $class->ID . '"/>';
                    echo '</div>';
                }
            }
        }
    }

    $settings_id_links = $settings->get_settings_links();

    // In the case we don't have classes.
    if (check_the_rules("teacher")) {
        echo 'Your teacher plan don\'t allow you to select different class than the default one.<br><a href="'.get_page_link($settings_id_links["link_not_academy"]).'">Click here to update your plan and became Academy!</a>';
    } elseif (check_the_rules("academy")) {
        echo '<br></vr><a href="' . get_page_link($settings_id_links["link_create_new_class"]) . '">Create other classes!</a>';
    }

}

/**
 * AJAX action to save the modifications made on a comment
 *
 * @author Nicolas TORION
 * @since  0.5.1
 */
add_action('CUSTOMAJAX_action_save_comment', 'action_save_comment');

function action_save_comment() {
    $comment_id = $_POST['comment_id'];
    $comment_text = $_POST['comment_text'];

    $comment_arr = array();
    $comment_arr['comment_ID'] = $comment_id;
    $comment_arr['comment_content'] = $comment_text;

    wp_update_comment($comment_arr);
}

/**
 * AJAX action to salve and send the badge.
 *
 * @author Alessandro RICCARDI
 * @since  0.5.1
 * @since  0.6.3
 */
add_action('CUSTOMAJAX_send_message_badge', 'send_message_badge');

function send_message_badge() {

    /* Variables */
    $language = $_POST['language'];
    $level = $_POST['level'];
    $badge_name = $_POST['badge_name'];
    $language_description = $_POST['language_description'];
    $listings_class = $_POST['class_student'];
    $mails = $_POST['mail'];
    $comment = $_POST['comment'];
    $sender = $_POST['sender'];
    $curForm = $_POST['curForm'];

    $class = null;
    $notsent = array();
    $badge = null;

    //User default class
    $teacher_information = get_user_by('email', $sender);
    $default_class = get_class_teacher($teacher_information->user_login);
    /* JSON file */
    $url_json_files = content_url('uploads/badges-issuer/json/');
    $path_dir_json_files = plugin_dir_path(dirname(__FILE__)) . '../../../uploads/badges-issuer/json/';
    /* Check if there are sufficient param */
    if (!isset($language) || !isset($level) || !isset($badge_name) ||
        !isset($language_description) || !isset($comment) || !isset($sender)) {

        echo "No enough information";

    } else {

        /* Get badge CERTIFICATION */
        $badge_others_items = get_badge($badge_name, $language_description);
        $certification = get_post_meta($badge_others_items['id'], '_certification', true);

        /* Set the email(s) */
        if (isset($mails)) {
            $mails_list = explode("\n", $mails);
        } else {
            $mails_list[0] = $sender;
        }

        /* Set the right class */
        if (isset($listings_class)) {
            $class = get_class_by_id($listings_class);
        } elseif (isset($default_class)) {
            $class = $default_class;
        }

        /* Creation of the badge */
        $badge = new Badge($badge_others_items['name'], $level, $language, $certification, $comment,
            $badge_others_items['description'], $language_description, $badge_others_items['image'],
            $url_json_files, $path_dir_json_files);

        /* Sending all the email */
        foreach ($mails_list as $mail) {

            /* operation for system not unix */
            $mail = str_replace("\r", "", $mail);

            $badge->create_json_files($mail);

            //SENDING THE EMAIL
            if (!$badge->send_mail($mail, $class->ID)) {
                $notsent[] = $mail;
            } else {
                if ($curForm == "a") {
                    $badge->add_student_to_class_zero($mail);
                } else {
                    $badge->add_student_to_class_zero($mail);
                    $badge->add_student_to_class($mail, $class->ID);
                    $badge->add_badge_to_user_profile($mail, $_POST['sender'], $class->ID);
                }
            }
        }

        if (sizeof($notsent) > 0) {
            $message = "Badge not sent to these persons : ";
            foreach ($notsent as $notsent_mail) {
                $message = $message . $notsent_mail . " ";
            }
            echo($message);
        } else {
            echo("Badge ($badge->name) sent to all the persons and stored in the class $class->post_title.");
        }
    }
}


if (in_array($action, $allowed_actions)) {
    if (is_user_logged_in()) {
        do_action('CUSTOMAJAX_' . $action);
    }
} else {
    die('-1');
}