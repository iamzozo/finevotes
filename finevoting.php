<?php

/*
Plugin Name: FH votes
Plugin URI: http://www.github.com/iamzozo/finevotes/
Description: A simple vote framework plugin. You can insert or delete vote for users and posts.
Author: Zoltan Varkonyi
Version: 1.0
*/

class Votes
{
    function __construct()
    {
        register_activation_hook(__FILE__, array(&$this, 'install_plugin'));
        register_uninstall_hook(__FILE__, array(&$this, 'uninstall_plugin'));
    }

    function create_vote($user_id, $post_id)
    {
        global $wpdb;
        $insert = $wpdb->insert($wpdb->prefix . 'votes', array(
            'created_at' => date('Y-m-d H:i:s'),
            'user_id' => $user_id,
            'post_id' => $post_id
        ), array(
            '%s',
            '%d',
            '%d'
        ));
        if ($insert) {
            return $wpdb->insert_id;
        } else {
            return false;
        }
    }

    function get_votes($id, $target)
    {
        global $wpdb;
        if ($target == 'post') {
            $where = 'post_id';
        } else {
            $where = 'user_id';
        }
        $table = $wpdb->prefix . 'votes';
        $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE $where = %d", $id), OBJECT);
        $return = array();
        if ($result) {
            foreach ($result as $row) {
                if($target == 'post')
                    $return[] = $row->user_id;
                else
                    $return[] = $row->post_id;
            }
        }
        return $return;
    }

    function delete_votes($id, $target)
    {
        global $wpdb;
        if ($target == 'post') {
            $where = 'post_id';
        } else {
            $where = 'user_id';
        }
        $wpdb->delete($wpdb->prefix . 'votes', array($where => $id), array('%d'));
    }

    function install_plugin()
    {
        $this->create_table();
    }

    function uninstall_plugin()
    {
        $this->drop_table();
    }

    function create_table()
    {
        global $wpdb;
        global $votes_db_version;

        $table_name = $wpdb->prefix . 'votes';

        $charset_collate = '';

        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            tag varchar(255) DEFAULT '',
            UNIQUE KEY id (id),
            INDEX post_id_i (post_id),
            INDEX user_id_i (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('votes_db_version', $votes_db_version);
    }

    function drop_table()
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}votes");
        remove_option('votes_db_version');
    }
}

if (!function_exists('create_vote')) {
    function create_vote($user_id, $post_id)
    {
        global $votes;
        $votes->create_vote($user_id, $post_id);
    }
}

if (!function_exists('get_votes')) {
    function get_votes($id, $target)
    {
        global $votes;
        return $votes->get_votes($id, $target);
    }
}

if (!function_exists('delete_votes')) {
    function delete_votes($id, $target)
    {
        global $votes;
        $votes->delete_votes($id, $target);
    }
}

$votes = new Votes();