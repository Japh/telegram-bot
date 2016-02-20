<?php
echo '<div class="wrap"><h2>' . __( 'New message', 'telegram-bot' ) . '</h2>';
if ( isset( $_POST['telegram_new_message'] ) ) {
    if ( telegram_option( 'channelusername' ) ) {
        telegram_sendmessage( telegram_option( 'channelusername' ), $_POST['telegram_new_message'] );
    }

    query_posts( 'post_type=telegram_subscribers&posts_per_page=-1' );
    $count = 0;
    while ( have_posts() ) {
        the_post();
        if ( telegram_useractive( get_the_title() ) ) {
            telegram_sendmessage( get_the_title(), $_POST['telegram_new_message'] );
            $count++;
        }
    }
    query_posts( 'post_type=telegram_groups&posts_per_page=-1' );
    while ( have_posts() ) {
        the_post();
        if ( telegram_useractive( get_the_title() ) ) {
            telegram_sendmessage( get_the_title(), $_POST['telegram_new_message'] );
            echo $c;
            $count++;
        }
    }

    echo '<div class="updated">
            <p>Your message have been sent to <b>' . $count . '</b> subscribers!</p>
        </div>';
} else {
    echo '<form method="post" id="telegram_new_message">';
    echo '<textarea name="telegram_new_message" cols="40" rows="5"></textarea>';
    submit_button( __( 'Send Now', 'telegram-bot' ), 'primary' );
    echo '<small>' . __( 'Users and groups who blocked the bot will be automatically deactivated.', 'telegram-bot' ) . '</small>';
    echo '<br><small>' . __( 'Your message will be sent to users, groups and your channel.', 'telegram-bot' ) . '</small>';
    echo '</form>';
}
echo '</div>';
