<?php
namespace MWHP\Inc\Services;

if ( ! defined( 'ABSPATH' ) ) exit;
use WP_Error;

class GPB_Places_Client {

    public static function fetch_place_opening_hours( $query, $api_key ) {
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key is empty.' );
        }
        if ( empty( $query ) ) {
            return new WP_Error( 'no_query', 'Query is empty.' );
        }

        // TextSearch
        $textsearch_url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . rawurlencode( $query ) . '&key=' . rawurlencode( $api_key );
        $resp = wp_remote_get( $textsearch_url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $resp ) ) {
            return new WP_Error( 'textsearch_failed', 'Text Search request failed: ' . $resp->get_error_message() );
        }
        $body = wp_remote_retrieve_body( $resp );
        $json = json_decode( $body, true );
        if ( empty( $json ) ) {
            return new WP_Error( 'textsearch_parse', 'Text Search returned invalid JSON.' );
        }
        if ( isset( $json['status'] ) && $json['status'] !== 'OK' && $json['status'] !== 'ZERO_RESULTS' ) {
            $msg = isset( $json['error_message'] ) ? $json['error_message'] : $json['status'];
            return new WP_Error( 'textsearch_status', 'Text Search error: ' . $msg );
        }
        if ( empty( $json['results'] ) || ! isset( $json['results'][0]['place_id'] ) ) {
            return new WP_Error( 'place_not_found', 'Place not found via Text Search.' );
        }
        $place_id = $json['results'][0]['place_id'];
        $address =  $json['results'][0]['formatted_address'];

        // Details
        $details_url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . rawurlencode( $place_id ) . '&fields=name,opening_hours&key=' . rawurlencode( $api_key );
        $resp2 = wp_remote_get( $details_url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $resp2 ) ) {
            return new WP_Error( 'details_failed', 'Details request failed: ' . $resp2->get_error_message() );
        }
        $body2 = wp_remote_retrieve_body( $resp2 );
        $j2 = json_decode( $body2, true );
        if ( empty( $j2 ) || ! isset( $j2['result'] ) ) {
            return new WP_Error( 'details_parse', 'Details response invalid or no result.' );
        }

        $result = $j2['result'];
        $name = isset( $result['name'] ) ? $result['name'] : '';
        $weekday_text = array();
        if ( isset( $result['opening_hours'] ) && isset( $result['opening_hours']['weekday_text'] ) && is_array( $result['opening_hours']['weekday_text'] ) ) {
            $weekday_text = $result['opening_hours']['weekday_text'];
        }

        return array( $place_id, $name, $weekday_text, $address );
    }
}
