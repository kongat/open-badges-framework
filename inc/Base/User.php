<?php

namespace inc\Base;

use Inc\Pages\Admin;

/**
 * The User Class.
 *
 * @author      Alessandro RICCARDI
 * @since       x.x.x
 *
 * @package     OpenBadgesFramework
 */
class User {
    const STUDENT_ROLE = "obf_student";
    const TEACHER_ROLE = "obf_teacher";
    const ACADEMY_ROLE = "obf_academy";

    const RET_LOGIN_SUCCESS = 0;
    const RET_NO_MATCH_PASS = "The <strong>passwords</strong> doesn't match, please write correctly. <br>";
    const RET_USER_EXIST = "The <strong>username</strong> already exist, please chose another.";
    const RET_REGISTRATION_ERROR = "<strong>Registration error<strong>, please ask to the help desk";

    public $listRoles = array(
        array(
            'role' => self::STUDENT_ROLE,
            'display_name' => 'Student',
            'capabilities' => array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false
            ),
        ),
        array(
            'role' => self::TEACHER_ROLE,
            'display_name' => 'Teacher',
            'capabilities' => array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false
            ),
        ),
        array(
            'role' => self::ACADEMY_ROLE,
            'display_name' => 'Academy',
            'capabilities' => array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false
            ),
        )
    );


    /**
     * ...
     *
     * @author Alessandro RICCARDI
     * @since  x.x.x
     */
    public function register() {
        $this->initialize();
        add_action('user_register', array($this, 'registerUserClass'));
    }

    /**
     * ...
     *
     * @author Alessandro RICCARDI
     * @since  x.x.x
     */
    private function initialize() {
        foreach ($this->listRoles as $role) {
            // Resetting of the role
            if (get_role($role['role'])) {
                remove_role($role['role']);
            }
            // Creation of the role
            add_role($role['role'], $role['display_name'], $role['capabilities']);

        }
    }


    /**
     * ...
     *
     * @author Alessandro RICCARDI
     * @since  x.x.x
     *
     * @param $user_id
     */
    public function registerUserClass($user_id) {
        if (!$user_id > 0) {
            return;
        } else {
            $user = get_user_by('id', $user_id);

            $newClass = array(
                'post_title' => $user->user_login,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => Admin::POST_TYPE_CLASS_JL,
                'post_author' => $user->ID
            );

            wp_insert_post($newClass);
        }
    }


    public static function getCurrentUser() {
        global $current_user;
        wp_get_current_user();

        return $current_user;
    }

    /**
     * Check the rules of the user.
     *
     * @author Alessandro RICCARDI
     * @since  0.6.4
     *
     * @param $actual_roles , the roles that the user have in this moment.
     * @param infinity      roles that you can pass after the first parameter like this:
     *                      check_the_rules("academy", "teacher")
     *
     * @return bool
     */
    public static function checkTheRules() {
        $user = self::getCurrentUser();
        $res = array();
        foreach (func_get_args() as $param) {
            if (!is_array($param)) {
                $res = in_array($param, $user->roles) ? true : $res ? true : false;
            }
        }
        return $res;
    }
}