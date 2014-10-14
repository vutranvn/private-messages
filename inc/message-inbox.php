<?php


function message_inbox(){
    // Global variable of WP
    global $wpdb, $current_user;

    $status = array();
    // Get all messages of current user. Maybe then create one function in any where. Hihi
    $mess_status_01 = $wpdb->get_results( "SELECT sm.*, u.display_name, u.user_email, u.ID AS user_id FROM ". $wpdb->prefix ."sys_messages AS sm LEFT JOIN " . $wpdb->prefix . "users AS u ON sm.author_id = u.ID WHERE sm.recipient_id = " . $current_user->ID . " AND sm.status IN (0, 1) ORDER BY sm.id DESC", OBJECT );

    /*Get messages delete has status = 3*/
    $mess_status_3 = $wpdb->get_results( "SELECT sm.*, u.display_name, u.user_email, u.ID AS user_id FROM ". $wpdb->prefix ."sys_messages AS sm LEFT JOIN " . $wpdb->prefix . "users AS u ON sm.author_id = u.ID WHERE sm.recipient_id = " . $current_user->ID . " AND sm.status = 3 ORDER BY sm.id DESC", OBJECT );

    $messages = array_merge($mess_status_01, $mess_status_3);
    arsort($messages);
    /*Get number and id of messages which current user don't have read*/
    $unread_mess = $wpdb->get_col( "SELECT id FROM " . $wpdb->prefix . "sys_messages WHERE status = 0 AND recipient_id = " . $current_user->ID );

    if( $messages ){
        $count_mess = count( $messages );
    }else{
        $count_mess = 0;
    }

    /*Count number unread message*/
    if( $unread_mess ){
        $count_unread = count( $unread_mess );
        /*For purpose using later*/
        $tmp = array();
        foreach ($unread_mess as $value) {
            $tmp[$value] = $value;
        }
        $unread_mess = $tmp;
    }else{
        $count_unread = 0;
    }

    /*Action View one message*/
    if( $_REQUEST['page'] == 'message-inbox' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && $_REQUEST['mess_id'] > 0 ){

        $message = $wpdb->get_row( "SELECT sm.*, u.display_name FROM " . $wpdb->prefix . "sys_messages AS sm LEFT JOIN " . $wpdb->prefix . "users AS u ON sm.author_id = u.ID WHERE sm.id = " . $_REQUEST['mess_id'] );

        if( $message->status == 0){
            $wpdb->update( $wpdb->prefix . "sys_messages", array('status' => 1), array( 'id' => $_REQUEST['mess_id']), array('%d') );
        }

        sys_mess_view_mess($message);/*Call template View Message*/

    /* Action Delete one message
     *  If current user is author's message, then drop out database
     * If not set status = 3.
    */
    }elseif( ($_REQUEST['page'] == 'message-inbox') && isset($_REQUEST['action']) && ($_REQUEST['action'] == 'delete') && ($_REQUEST['mess_id'] > 0) ){

        $del_mess_arr = $wpdb->get_results( "SELECT sm.id, sm.author_id FROM " . $wpdb->prefix . "sys_messages AS sm WHERE sm.id = " . $_REQUEST['mess_id'] );

        foreach ($del_mess_arr as $mess) {
            if( $current_user->ID == $del_mess_arr[0]->author_id){

                $wpdb->delete( $wpdb->prefix . "sys_messages", array( 'id' => $_REQUEST['mess_id'], array( '%d' ) ) );

            }else{

                $wpdb->update( $wpdb->prefix . "sys_messages", array( 'status' => 3 ), array( 'id' => $_REQUEST['mess_id'] ) );

            }
        }

        wp_redirect( admin_url( '?page=message-inbox' ) );

    /*Group actions when select check-box*/
    }elseif( $_REQUEST['page'] == 'message-inbox' && isset($_REQUEST['action']) || isset($_REQUEST['action2']) ){

        if( isset($_REQUEST['cid_messages']) ){

            $action = -1;

            /*The submit's action is priority heighter the action1*/
            if( $_REQUEST['action2'] != -1 && $_REQUEST['action'] == -1 ){
                $action = $_REQUEST['action2'];
            }elseif( $_REQUEST['action'] != -1 ){
                $action = $_REQUEST['action'];
            }

            switch ( $action ) {

                case 'mark_read':

                    $delete_mess_arr = $wpdb->get_results( "SELECT sm.id, sm.author_id FROM " . $wpdb->prefix . "sys_messages AS sm WHERE sm.id IN (" . implode( ',', $_REQUEST['cid_messages']) . ")" );

                    foreach( $delete_mess_arr as $item ){
                        $wpdb->update( $wpdb->prefix . "sys_messages", array('status' => 1), array( 'id' => $item->id ), array('%d') );
                    }

                    break;

                case 'trash':
                    $delete_mess_arr = $wpdb->get_results( "SELECT sm.id, sm.author_id FROM " . $wpdb->prefix . "sys_messages AS sm WHERE sm.id IN (" . implode( ',', $_REQUEST['cid_messages']) . ")" );

                    foreach( $delete_mess_arr as $item){
                        if(  $current_user->ID == $item->author_id ){
                            $wpdb->delete( $wpdb->prefix . "sys_messages", array( 'id' => $item->id ), array( "%d" ) );
                        }else{
                            $wpdb->update( $wpdb->prefix . "sys_messages", array( 'status' => 3), array( 'id' => $item->id ), array( '%d' ) );
                        }
                    }

                    break;

                default:
                    wp_redirect( admin_url( '?page=message-inbox' ) );
                    break;
            }

            wp_redirect( admin_url( '?page=message-inbox' ) );

        }else{
            wp_redirect( admin_url( '?page=message-inbox' ) );
        }

    }else{

        /* In normal, will call template Inbox Messages*/
        sys_mess_load_inbox($messages, $unread_mess, $count_mess,$count_unread);

    }

?>

<?php } ?>

<?php
function sys_mess_load_inbox($messages, $unread_mess, $count_mess,$count_unread){
    ?>
    <div class="wrap">
        <h2>Message Inbox</h2>
        <ul class="sys-message-notice">
            <li></li>
        </ul>

        <p>You have <?php echo $count_unread; ?> message(s) unread</p>

        <ul class="subsubsub">
            <li class="all"><a href="#" class="current">All</a> |</li>
            <li class="moderated"><a href="#">Read <span class="count">(<span class="pending-count">0</span>)</span></a> |</li>
            <li class="approved"><a href="#">Unread</a> |</li>
            <li class="trash"><a href="#">Trash <span class="count">(<span class="trash-count">0</span>)</span></a></li>
        </ul>

        <form id="sys-message-form" action="" method="post">

            <p class="search-box">
                <label class="screen-reader-text" for="message-search-input">Search Message:</label>
                <input id="message-search-input" name="s" value="" type="search" />
                <input name="" id="search-submit" class="button" value="Search Message" type="submit" />
            </p>

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1" selected="selected">Bulk Actions</option>
                        <option value="read">Read</option>
                        <option value="unread">Unread</option>
                        <option value="mark_read">Mark as Read</option>
                        <option value="trash">Move to Trash</option>
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
                        <th scope="col" id="sender" class="manage-column sortable desc" style="width: 20%;">
                            <a href="http://fiverr.local/wp-admin/edit-comments.php?orderby=comment_author&amp;order=asc">
                                <span>Sender</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" id="subject" class="manage-column column-comment" style="">Subject</th>
                        <th scope="col" id="date" class="manage-column column-response sortable desc" style="width: 25%;">
                            <a href="http://fiverr.local/wp-admin/edit-comments.php?orderby=comment_post_ID&amp;order=asc">
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
                            <a href="http://fiverr.local/wp-admin/edit-comments.php?orderby=comment_author&amp;order=asc">
                                <span>Sender</span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th scope="col" class="manage-column column-comment" style="">Subject</th>
                        <th scope="col" class="manage-column column-response sortable desc" style="">
                            <a href="http://fiverr.local/wp-admin/edit-comments.php?orderby=comment_post_ID&amp;order=asc">
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
                        <?php if( isset( $unread_mess[$item->id] ) ){ ?>
                            <a href=""><p style="color: black;"><b><?php echo $item->subject; ?></b></p></a>
                        <?php }else{ ?>
                            <a href=""><p><?php echo $item->subject; ?></p></a>
                        <?php } ?>
                            <div class="row-actions">
                                <span class="view"> | <a class="" title="View this message" href="<?php echo wp_nonce_url( admin_url('?page=message-inbox&action=view&mess_id=' . $item->id) ); ?>">View</a></span>
                                <span class="reply"> | <a class="" title="Reply to message" href="<?php echo wp_nonce_url( admin_url('admin.php?page=message-compose') ); ?>">Reply</a></span>
                                <span class="trash"> | <a class="delete" title="Move this comment to the trash" href="<?php echo wp_nonce_url( admin_url('?page=message-inbox&action=delete&mess_id=' . $item->id) ); ?>">Trash</a></span>
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
                        <option value="read">Read</option>
                        <option value="unread">Unread</option>
                        <option value="mark_read">Mark as Read</option>
                        <option value="trash">Move to Trash</option>
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
<?php } ?>

<?php function sys_mess_view_mess($item){ ?>
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