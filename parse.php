<?php

$json = file_get_contents( 'php://input' );

if ( ! $json ) {
	return;
}

$data = ( array ) json_decode( $json, true );

//telegram_sendmessage( '63480147', 'CIAO !!!');

if ( ! ( $data['message']['message_id'] > get_option( 'wp_telegram_last_id' ) ) ) {
    telegram_log( 'EXCEPTION', 'MESSAGE_ID', json_encode( $json, true ) );
    die();
}
update_option( 'wp_telegram_last_id', $data['message']['message_id'] );

if ( $data['message']['chat']['type'] == 'private' ) {
	$USERID = $data['message']['from']['id'];
	$CPT = 'telegram_subscribers';
	$PRIVATE = true;
	$GROUP = false;
} else if ( $data['message']['chat']['type'] == 'group' ) {
	$USERID = $data['message']['chat']['id'];
	$CPT = 'telegram_groups';
	$GROUP = true;
	$PRIVATE = false;
} else {
	telegram_log( 'EXCEPTION', './\.', json_encode( $json, true ) );
	die();
}

telegram_log( '>>>>', $USERID, $data['message']['text'] );

if ( telegram_option( 'debug' ) ) {
	telegram_log( '####', 'DEBUG', json_encode( $json, true ) );
}

$o = get_page_by_title( $USERID, OBJECT, $CPT );
if ( ! $o ) {
	$p = wp_insert_post( array(
		'post_title' => $USERID,
		'post_content' => '',
		'post_type' => $CPT,
		'post_status' => 'publish',
		'post_author' => 1,
	) );
	if ( $PRIVATE ) {
		update_post_meta( $p, 'telegram_first_name', $data['message']['from']['first_name'] );
		update_post_meta( $p, 'telegram_last_name', $data['message']['from']['last_name'] );
		update_post_meta( $p, 'telegram_username', $data['message']['from']['username'] );
		telegram_sendmessage( $USERID, telegram_option( 'wmuser' ) );
	} else if ( $GROUP ) {
		update_post_meta( $p, 'telegram_name', $data['message']['chat']['title'] );
		telegram_log( '-<--->-', '', 'Bot added to <strong>' . $data['message']['chat']['title'] . '</strong>' );
	}
	return;
} else if ( $PRIVATE ) {
    update_post_meta( $o->ID, 'telegram_first_name', $data['message']['from']['first_name'] );
	update_post_meta( $o->ID, 'telegram_last_name', $data['message']['from']['last_name'] );
	update_post_meta( $o->ID, 'telegram_username', $data['message']['from']['username'] );
    delete_post_meta( telegram_getid( $USERID ), 'telegram_status' );
} else if ( $GROUP ) {
	update_post_meta( $o->ID, 'telegram_name', $data['message']['chat']['title'] );
	delete_post_meta( telegram_getid( $USERID ), 'telegram_status' );
}

if ( isset( $data['message']['location'] ) ) {
    $page = get_page_by_title( 'telegram-location', '', 'telegram_commands' );
    update_post_meta( telegram_getid( $USERID ), 'telegram_last_latitude', $data['message']['location']['latitude'] );
    update_post_meta( telegram_getid( $USERID ), 'telegram_last_longitude', $data['message']['location']['longitude'] );
    telegram_sendmessage( $USERID, $page->ID );
    do_action( 'telegram_parse_location', $USERID, $data['message']['location']['latitude'], $data['message']['location']['longitude'] );
    return;
}

do_action( 'telegram_parse', $USERID, $data['message']['text'] ); //EXPERIMENTAL

$ok_found = false;
if ( $data['message']['text'] != '' ) {
    query_posts( 'post_type=telegram_commands&posts_per_page=-1' );
    while ( have_posts() ):
        the_post();
        $lowertitle = strtolower( get_the_title() );
        $lowermessage = strtolower( $data['message']['text'] );
        if (
            ( $lowertitle == $lowermessage )
            ||
            ( strpos( $lowermessage, $lowertitle.' ' ) === 0 )
            ||
            ( in_array(  $lowermessage, explode(",", $lowertitle ) ) )
           ) {
            $ok_found = true;

            if ( has_post_thumbnail( get_the_id() ) ) {
                $image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium', true );
                telegram_sendphoto( $USERID, get_the_id(), $image[0] );
            }
            else {
                telegram_sendmessage( $USERID, get_the_id() );
            }
        }

    endwhile;
}

if ( $PRIVATE ) {
	switch ( $data['message']['text'] ) {
		case '/start':
            $ok_found = true;
		    if ( ! telegram_useractive( $USERID ) ) {
			    telegram_sendmessage( $USERID, telegram_option('wmuser') );
		    } else {
                telegram_sendmessage( $USERID, telegram_option('wmuser') );
                delete_post_meta( telegram_getid( $USERID ), 'telegram_status' );
		    }
		    break;
		case '/stop':
            $ok_found = true;
		    if ( telegram_useractive( $USERID ) ) {
			    telegram_sendmessage( $USERID, telegram_option( 'bmuser' ) );
			    update_post_meta( telegram_getid( $USERID ), 'telegram_status', '1' );
		    } else {
			    telegram_sendmessage( $USERID, telegram_option( 'bmuser' ) );
		    }
		    break;
		default:
		    break;

		return;
	}
}

if ( $GROUP && $data['message']['new_chat_participant']['id'] == current( explode( ':', telegram_option( 'token' ) ) ) ) {
    telegram_sendmessage( $USERID, telegram_option( 'wmgroup' ) );
}
if ( $GROUP && $data['message']['left_chat_participant']['id'] == current( explode(':', telegram_option( 'token' ) ) ) ) {
	update_post_meta( telegram_getid( $USERID ), 'telegram_status', '1' );
	telegram_log( '-<--->-', '', 'Bot removed from <strong>' . $data['message']['chat']['title'] . '</strong>' );
}
if ( $PRIVATE && !$ok_found ) {
     telegram_sendmessage( $USERID, telegram_option( 'emuser' ) );
}
