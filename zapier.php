<?php
$json = file_get_contents( 'php://input' );
if ( ! $json ) {
    return;
}
$data = ( array ) json_decode( $json, true );
telegram_log( '------>', 'ZAPIER', json_encode( ( array ) file_get_contents( "php://input" ) ) );
telegram_sendmessagetoall( $data['hook'] );
