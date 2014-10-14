<?php
function message_compose(){

    global $wpdb, $current_user;
    $errors = array();
    $content = '';

    if( isset($_POST['submit-form-compose']) && $_POST['submit-form-compose'] == 102 ){
        if(empty($_POST['compose-to'])){
            $errors[] = 'Please insert destination To';
        }
        if(empty($_POST['compose-subject'])){
            $errors[] = 'Please insert Subject message';
        }
        if(empty($_POST['compose-content'])){
            $errors[] = 'Please insert Content message';
        }else{
            $content = $_POST['compose-content'];
        }

        if(empty($errors)){
            // Store db
            $new_message = array(
                'id' => null,
                'status' => 0,
                'subject'   => $_POST['compose-subject'],
                'content'   => $_POST['compose-content'],
                'author_id' => $current_user->ID,
                'recipient_id'  => $recipient_id,
                'timestamp_gmt' => current_time( 'mysql' )
            );

            if( $wpdb->insert( $wpdb->prefix . 'sys_messages', $new_message, array( '%d', '%d', '%s', '%s', '%d', '%d', '%s') ) ){
                $errors[] = "You have sent message successful";
                unset( $_POST['compose-to'], $_POST['compose-subject'], $_POST['compose-content'] );
                $content = '';
                wp_redirect( get_permalink() );
            }else{
                $errors[] = "You have wrong somewhere in sending message";
            }

        }else{
            // What do to do?? Haven't think!
        }
    }else{
        // What do to do?? Haven't think!
    }

?>

<div class="wrap">
    <h2>Compose Message</h2>
    <ul class="sys-message-notice">
        <?php if($errors){
            foreach ($errors as $error) {
                ?>
                <li><?php echo $error; ?></li>
            <?php }
        } ?>
    </ul>
    <form id="sys-message-form" action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="submit-form-compose" id="submit-form-compose" value="102" />
        <table class="form-table fixed sys-message-table">
            <tr>
                <th style="width: 12%;">To</th>
                <td><input type="text" class="large-text" name="compose-to" value="<?php if( isset($_POST['compose-to']) ) echo $_POST['compose-to']; ?>"></td>
            </tr>
            <tr>
                <th style="width: 12%;">Subject</th>
                <td><input type="text" class="large-text" name="compose-subject" value="<?php if( isset($_POST['compose-subject']) ) echo $_POST['compose-subject']; ?>"></td>
            </tr>
            <tr>
                <th style="width: 12%;">Content</th>
                <td>
                    <?php wp_editor( $content, 'compose-content' ); ?>
                </td>
            </tr>
        </table>
        <!-- <input name="publish" id="publish" class="button button-primary button-large" value="Publish" accesskey="p" type="submit"> -->
        <p class="submit" style="text-align: center;"><input type="submit" class="button button-primary button-large" value="Send" /></p>
    </form>
</div>


<?php } ?>