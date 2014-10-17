<?php

function message_sent(){
    // Global variable of WP
    global $wpdb, $current_user;

    /*Action View one message*/
    if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'message-sent' ){

        if( isset($_REQUEST['action']) ){
            /*Group actions when select check-box: select read, unread, mark as read, delete*/
            $action = -1;

            /*The submit's action is priority heighter the action1*/
            if( isset($_REQUEST['action2']) && $_REQUEST['action2'] != -1 && $_REQUEST['action'] == -1 ){
                $action = $_REQUEST['action2'];
            }elseif( $_REQUEST['action'] != -1 ){
                $action = $_REQUEST['action'];
            }

            switch ( $action ) {
                case 'view':
                    if( isset($_REQUEST['mess_id']) && $_REQUEST['mess_id'] > 0 ){
                        $message = sys_mess_query_get_row_sent( $wpdb->prefix . "sys_messages", $_REQUEST['mess_id'] );
                        if( $message->status == 0){
                            sys_mess_query_update_sent( $wpdb->prefix . "sys_messages", array('status' => 1), array( 'id' => $_REQUEST['mess_id']), array('%d') );
                        }
                        sys_mess_view_mess($message);/*Call template View Message*/
                    }elseif( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ){
                        $order_by = "DESC";
                        $order = "id";

                        if( in_array( $_REQUEST['orderby'], array('recipient_id', 'timestamp_gmt') ) ){
                            $order_by = $_REQUEST['orderby'];
                        }
                        if( in_array($_REQUEST['order'], array('desc', 'asc') ) ){
                            $order = $_REQUEST['order'];
                        }

                        sys_mess_get_sent_messages($current_user, $status = array(0, 1, 3), $order_by, $order);
                    }

                    break;

                case 'delete':

                    /*Delete one message*/
                    if( isset($_REQUEST['mess_id']) && $_REQUEST['mess_id'] > 0 ){
                        $del_mess_arr = sys_mess_query_get_results_sent( array( 'sm.id', 'sm.recipient_id', 'sm.author_id' ), $wpdb->prefix . "sys_messages AS sm ", $_REQUEST['mess_id'] );

                        if( isset($del_mess_arr[0]->author_id) && $del_mess_arr[0]->author_id == $current_user->ID ){
                            $wpdb->delete( $wpdb->prefix . "sys_messages", array( 'id' => $_REQUEST['mess_id'], array( '%d' ) ) );
                        }

                        wp_redirect( admin_url( 'admin.php?page=message-sent' ) );
                    }else{
                        wp_redirect( admin_url( 'admin.php?page=message-sent' ) );
                    }

                    break;

                case 'read':

                    sys_mess_get_sent_messages($current_user, $status = array(1), 'id', 'desc'); /*Call template View Message*/
                    break;

                case 'unread':

                    sys_mess_get_sent_messages($current_user, $status = array(0), 'id', 'desc');
                    break;

                case 'trash':

                    $delete_mess_arr = $wpdb->get_results( "SELECT sm.id, sm.recipient_id, sm.author_id FROM " . $wpdb->prefix . "sys_messages AS sm WHERE sm.id IN (" . implode( ',', $_REQUEST['cid_messages']) . ")" );

                    foreach( $delete_mess_arr as $item){
                        if(  $current_user->ID == $item->author_id ){
                            $wpdb->delete( $wpdb->prefix . "sys_messages", array( 'id' => $item->id ), array( "%d" ) );
                        }
                    }
                    wp_redirect( admin_url( 'admin.php?page=message-sent' ) );
                    break;

                default:

                    if( isset( $_REQUEST['s'] ) && $_REQUEST['s'] != ''){

                        $messages = $wpdb->get_results( "SELECT sm.*, u.display_name, u.user_email, u.ID AS user_id FROM ". $wpdb->prefix ."sys_messages AS sm LEFT JOIN " . $wpdb->prefix . "users AS u ON sm.recipient_id = u.ID WHERE sm.recipient_id = " . $current_user->ID . " AND sm.status IN ( 0, 1, 3 ) AND sm.subject LIKE '%". trim($_REQUEST['s']) ."%' ORDER BY sm.id DESC", OBJECT );

                        if( $messages ){
                            $count_mess = count( $messages );
                        }else{
                            $count_mess = 0;
                        }

                        foreach ($messages as $key => $item) {
                            if( $item->status == 3 && $item->recipient_id != $current_user->ID ){
                                unset( $messages[$key] );
                            }
                        }
                        /*sys_mess_load_inbox($messages, $unread_mess, $count_mess,$count_unread)*/
                        sys_mess_load_sent($messages, 0, $count_mess, 0);
                    }else{
                        wp_redirect( admin_url( '?page=message-sent' ) );
                    }

                    break;
            }
        }else{

            sys_mess_get_sent_messages($current_user, array(0, 1, 3), 'id', 'desc');
        }
    }

}

function sys_mess_query_get_results_sent( $select = array(), $table, $where, $order_by = null, $order = null ){
    global $wpdb;

    $select = implode( ',', $select);
    $result = $wpdb->get_results( "SELECT " . $select . " FROM " . $table . " WHERE sm.id = " . $where );

    return $result;
}

function sys_mess_query_update_sent( $table, $data = array(), $where = array(), $format = null ){
    global $wpdb;

    $wpdb->update( $table, $data, $where, $format );
}

function sys_mess_query_get_row_sent($table, $where){
    global $wpdb;

    $result = $wpdb->get_row( "SELECT sm.*, u.display_name FROM " . $table . " AS sm LEFT JOIN " . $wpdb->prefix . "users AS u ON sm.author_id = u.ID WHERE sm.id = " . $where );

    return $result;
}

function sys_mess_get_sent_messages( $current_user, $status = array(), $order_by, $order ){
    global $wpdb;

    $status = implode(',', $status);
    // Get all messages of current user. Maybe then create one function in any where. Hihi
    $messages = $wpdb->get_results( "SELECT sm.*, u.display_name, u.user_email, u.ID AS user_id FROM ". $wpdb->prefix ."sys_messages AS sm LEFT JOIN " . $wpdb->prefix . "users AS u ON sm.recipient_id = u.ID WHERE sm.author_id = " . $current_user->ID . " AND sm.status IN ( ". $status ." ) ORDER BY sm." . $order_by . " " . $order, OBJECT );

    foreach ($messages as $key => $item) {
        if( $item->status == 3 && $item->author_id != $current_user->ID ){
            unset( $messages[$key] );
        }
    }

    if( $messages ){
        $count_mess = count( $messages );
    }else{
        $count_mess = 0;
    }

    /* In normal, will call template Inbox Messages*/
    sys_mess_load_sent($messages, 0, $count_mess, 0);
}

function sys_mess_load_sent($messages, $unread_mess, $count_mess, $count_unread){

    if( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'recipient_id' && isset($_REQUEST['order']) && $_REQUEST['order'] == 'asc' ){
        $sender_order = 'desc';
        $sender_css = 'sorted asc';
    }elseif( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'recipient_id' && isset($_REQUEST['order']) && $_REQUEST['order'] == 'desc' ){
        $sender_order = 'asc';
        $sender_css = 'sorted desc';
    }else{
        $sender_order = 'asc';
        $sender_css = 'sortable desc';
    }

    if( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'timestamp_gmt' && isset($_REQUEST['order']) && $_REQUEST['order'] == 'asc' ){
        $date_order = 'desc';
        $date_css = 'sorted asc';
    }elseif( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] == 'timestamp_gmt' && isset($_REQUEST['order']) && $_REQUEST['order'] == 'desc' ){
        $date_order = 'asc';
        $date_css = 'sorted desc';
    }else{
        $date_order = 'asc';
        $date_css = 'sortable desc';
    }
    $hid_search = '';
    if( isset( $_REQUEST['s'] ) && $_REQUEST['s'] != '' ){
        $hid_search = trim($_REQUEST['s']);
    }

    ?>
    <div class="wrap">
        <h2>
            Message Sent
            <?php
            if( $hid_search != '' ){
                echo '<span class="subtitle">Search results for “' .$hid_search. '”</span>';
            }
            ?>
        </h2>

        <ul class="sys-message-notice">
            <li></li>
        </ul>

        <form id="sys-message-form" action="" method="get">

            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <input type="hidden" name="sender_order" value="<?php echo $sender_order; ?>" />
            <input type="hidden" name="date_order" value="<?php echo $date_order; ?>" />

            <p class="search-box">
                <label class="screen-reader-text" for="message-search-input">Search Message:</label>
                <input id="message-search-input" name="s" value="<?php echo $hid_search != '' ? $hid_search : ''; ?>" type="search" />
                <input name="" id="search-submit" class="button" value="Search Message" type="submit" />
            </p>
            <!-- English language, PHP(Zend framework, Laravel, WordPress, Joomla, Drupal, Magento), Ruby on Rails, iOS -->
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1" selected="selected">Bulk Actions</option>
                        <option value="trash">Delete</option>
                    </select>
                    <input name="" id="doaction" class="button action" value="Apply" type="submit" />
                </div>

                <div class="tablenav-pages one-page">
                    <span class="displaying-num"><?php echo $count_mess; ?> items</span>
                    <span class="pagination-links">
                        <a class="first-page disabled" title="Go to the first page" href="http://fiverr.local/wp-admin/edit-comments.php">«</a>
                        <a class="prev-page disabled" title="Go to the previous page" href="http://fiverr.local/wp-admin/edit-comments.php?paged=1">‹</a>
                        <span class="paging-input">
                            <label for="current-page-selector" class="screen-reader-text">Select Page</label>
                            <input class="current-page" id="current-page-selector" title="Current page" name="paged" value="1" size="1" type="text" /> of <span class="total-pages">1</span>
                        </span>
                        <a class="next-page disabled" title="Go to the next page" href="http://fiverr.local/wp-admin/edit-comments.php?paged=1">›</a>
                        <a class="last-page disabled" title="Go to the last page" href="http://fiverr.local/wp-admin/edit-comments.php?paged=1">»</a>
                    </span>
                </div>
                <br class="clear" />
            </div>

            <table class="widefat fixed sys-message-table">
                <thead>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column" style="">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox" />
                        </th>
                        <th scope="col" id="sender" class="manage-column sortable <?php echo $sender_css; ?>" style="width: 20%;">

                            <a href="<?php echo admin_url( 'admin.php?page=message-sent&action=view&orderby=recipient_id&order=' . $sender_order ); ?>" >
                                <span>Sender</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="subject" class="manage-column column-comment" style="">Subject</th>
                        <th scope="col" id="date" class="manage-column column-response sortable desc" style="width: 25%;">
                            <a href="<?php echo admin_url('admin.php?page=message-sent&action=view&orderby=timestamp_gmt&order=' . $date_order ); ?>">
                                <span>Date</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                    </tr>
                </thead>

                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column" style="">
                            <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
                            <input id="cb-select-all-2" type="checkbox" />
                        </th>
                        <th scope="col" class="manage-column column-author sortable desc" style="">
                            <a href="<?php echo admin_url( '?page=message-sent&action=view&orderby=recipient_id&order=' . $sender_order ); ?>">
                                <span>Receiver</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-comment" style="">Subject</th>
                        <th scope="col" class="manage-column column-response sortable desc" style="">
                            <a href="<?php echo admin_url('?page=message-sent&action=view&orderby=timestamp_gmt&order=' . $date_order ); ?>">
                                <span>Date</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                    </tr>
                </tfoot>

                <tbody id="the-comment-list" data-wp-lists="list:comment">
                <?php if($messages){
                    foreach ($messages as  $item) {
                        $gravatar = get_avatar( $item->user_email, 32, 'http://0.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536', $item->display_name );
                    ?>
                    <tr id="comment-<?php echo $item->id; ?>" class="comment even thread-even depth-1 approved">
                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="cb-select-3">Select comment</label>
                            <input id="cb-select-<?php echo $item->id; ?>" name="cid_messages[]" value="<?php echo $item->id; ?>" type="checkbox" />
                        </th>
                        <td class="author column-author">
                            <strong><?php echo $gravatar; ?> <?php echo $item->display_name; ?></strong><br>
                            <a href="edit-comments.php?s=&amp;mode=detail"></a>
                        </td>
                        <td class="comment column-comment">
                            <a href=""><p><?php echo $item->subject; ?></p></a>
                            <div class="row-actions">
                                <span class="view"> | <a class="" title="View this message" href="<?php echo wp_nonce_url( admin_url('admin.php?page=message-sent&action=view&mess_id=' . $item->id) ); ?>">View</a></span>
                                <span class="reply"> | <a class="" title="Reply to message" href="<?php echo wp_nonce_url( admin_url('admin.php?page=message-compose') ); ?>">Reply</a></span>
                                <span class="trash"> | <a class="delete" title="Move this comment to the trash" href="<?php echo wp_nonce_url( admin_url('admin.php?page=message-sent&action=delete&mess_id=' . $item->id) ); ?>">Trash</a></span>
                            </div>
                        </td>
                        <td class="response column-response">
                            <div class="response-links">
                                <span class="post-com-count-wrapper">
                                    <p><?php echo $item->timestamp_gmt; ?></p>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php }
                }else{ ?>
                    <tr id="comment-3" class="comment even thread-even depth-1 approved">
                        <td colspan="4" style="text-align: center;">
                            <p style="margin: 20px;">You don't have message(s).</p>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
                    <select name="action2" id="bulk-action-selector-bottom">
                        <option value="-1" selected="selected">Bulk Actions</option>
                        <option value="trash">Delete</option>
                    </select>
                    <input name="" id="doaction2" class="button action" value="Apply" type="submit" />
                </div>
                <div class="alignleft actions"></div>
                <div class="tablenav-pages one-page">
                    <span class="displaying-num"><?php echo $count_mess; ?> items</span>
                    <span class="pagination-links">
                        <a class="first-page disabled" title="Go to the first page" href="http://fiverr.local/wp-admin/edit-comments.php">«</a>
                        <a class="prev-page disabled" title="Go to the previous page" href="http://fiverr.local/wp-admin/edit-comments.php?paged=1">‹</a>
                        <span class="paging-input">1 of <span class="total-pages">1</span></span>
                        <a class="next-page disabled" title="Go to the next page" href="http://fiverr.local/wp-admin/edit-comments.php?paged=1">›</a>
                        <a class="last-page disabled" title="Go to the last page" href="http://fiverr.local/wp-admin/edit-comments.php?paged=1">»</a>
                    </span>
                </div>
                <br class="clear">
            </div>
        </form>
    </div>
<?php }

function sys_mess_view_mess_sent($item){ ?>
    <div class="wrap">
        <h2>View Message</h2>

        <input type="hidden" name="submit-form-compose" id="submit-form-compose" value="102" />
        <table class="form-table fixed sys-message-table">
            <tr>
                <th style="width: 12%;">From</th>
                <td><?php echo ucwords($item->display_name); ?></td>
            </tr>
            <tr>
                <th style="width: 12%;">Subject</th>
                <td><?php echo $item->subject; ?></td>
            </tr>
            <tr>
                <th style="width: 12%;">Content</th>
                <td>
                    <?php echo $item->content; ?>
                </td>
            </tr>
        </table>

    </div>
<?php } ?>