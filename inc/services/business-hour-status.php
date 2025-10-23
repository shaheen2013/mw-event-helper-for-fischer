<?php
namespace MWHP\Inc\Services;

if ( ! defined( 'ABSPATH' ) ) exit;

class Business_Hour_Status {

    /**
     * Transient key prefix (used when you want to persist weekday/time data retrieved from Google API)
     */
    private static $transient_prefix = 'gp_business_hours_';

    /**
     * Per-request static cache keyed by post_id.
     * null => computed and no data
     * array => computed result
     */
    private static $business_status = [];


    public static function get_status( ?int $post_id = null ) : string {
        if ( ! $post_id ) {
            $post_id = get_the_ID() ?: null;
        }
        if ( ! $post_id ) {
            return 'Close';
        }

        if ( array_key_exists( $post_id, self::$business_status ) ) {
            return (string) self::$business_status[ $post_id ];
        }

        $trans_key = self::$transient_prefix . $post_id;
        $cached = get_transient( $trans_key );
        if ( $cached !== false && is_array( $cached ) && ! empty( $cached['weekday'] ) ) {
            $weekday_arr = $cached['weekday'];
        } else {
            $weekday_arr = get_post_meta( $post_id, '_gp_weekday_text', true );
        }

        if ( empty( $weekday_arr ) || ! is_array( $weekday_arr ) ) {
            self::$business_status[ $post_id ] = 'Close';
            return 'Close';
        }

        $tz_string = ( function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : '' );
        if ( empty( $tz_string ) ) {
            $tz_string = get_option( 'timezone_string' ) ?: 'UTC';
        }
        $tz = new \DateTimeZone( $tz_string );

        $now_ts = (int) current_time( 'timestamp' );

        $by_day = [];
        foreach ( $weekday_arr as $line ) {
            $parts = explode( ':', $line, 2 );
            if ( ! isset( $parts[0] ) ) {
                continue;
            }
            $day = trim( $parts[0] );
            $times = isset( $parts[1] ) ? trim( $parts[1] ) : '';
            $day_norm = ucfirst( strtolower( $day ) );
            $by_day[ $day_norm ] = $times;
        }

        $parse_time_for_date = function( string $time_text, string $date ) use ( $tz ) {
            $time_text = trim( $time_text );
            if ( $time_text === '' ) {
                return false;
            }

            if ( preg_match( '/\b(24\s*hours|open\s*24|all\s*day)\b/i', $time_text ) ) {
                try {
                    $start = new \DateTimeImmutable( $date . ' 00:00', $tz );
                    return (int) $start->format( 'U' );
                } catch ( \Exception $e ) {
                    return false;
                }
            }

            try {
                $d = new \DateTimeImmutable( $date . ' ' . $time_text, $tz );
                return (int) $d->format( 'U' );
            } catch ( \Throwable $e ) {
                $formats = [ 'g:i A', 'g A', 'H:i', 'G:i', 'h:i A' ];
                foreach ( $formats as $fmt ) {
                    $dt = \DateTime::createFromFormat( 'Y-m-d ' . $fmt, $date . ' ' . $time_text, $tz );
                    if ( $dt !== false ) {
                        return (int) $dt->format( 'U' );
                    }
                }
            }

            return false;
        };

        $parse_intervals = function( string $times_str, string $date ) use ( $parse_time_for_date ) {
            $intervals = [];
            $times_str = trim( $times_str );
            if ( $times_str === '' ) {
                return $intervals;
            }

            if ( preg_match( '/\b(closed|stängt|geschlossen|cerrado)\b/i', $times_str ) ) {
                return $intervals;
            }

            $parts = preg_split( '/\s*(?:,|;|\/|\band\b)\s*/i', $times_str );
            foreach ( $parts as $part ) {
                $part = trim( $part );
                if ( $part === '' ) {
                    continue;
                }

                $segs = preg_split( '/\s*(?:–|—|-|to)\s*/u', $part );
                if ( count( $segs ) < 2 ) {
                    if ( preg_match( '/\b(24\s*hours|open\s*24|all\s*day)\b/i', $part ) ) {
                        $start_ts = $parse_time_for_date( '00:00', $date );
                        if ( $start_ts !== false ) {
                            $end_ts = $start_ts + 24 * 3600;
                            $intervals[] = [ 'start' => $start_ts, 'end' => $end_ts, 'start_text' => '00:00', 'end_text' => '24:00' ];
                        }
                    }
                    continue;
                }

                $start_text = trim( $segs[0] );
                $end_text   = trim( $segs[1] );

                $start_ts = $parse_time_for_date( $start_text, $date );
                $end_ts   = $parse_time_for_date( $end_text, $date );

                if ( $start_ts === false || $end_ts === false ) {
                    continue;
                }

                if ( $end_ts <= $start_ts ) {
                    $end_ts += 24 * 3600;
                }

                $intervals[] = [
                    'start' => $start_ts,
                    'end'   => $end_ts,
                    'start_text' => $start_text,
                    'end_text'   => $end_text,
                ];
            }

            return $intervals;
        };

        $today_name = date( 'l', $now_ts );
        $today_date = date( 'Y-m-d', $now_ts );
        $today_times = $by_day[ $today_name ] ?? '';
        $intervals_today = $parse_intervals( $today_times, $today_date );

        foreach ( $intervals_today as $iv ) {
            if ( $now_ts >= $iv['start'] && $now_ts < $iv['end'] ) {
                self::$business_status[ $post_id ] = 'Open';
                return 'Open';
            }
        }

        self::$business_status[ $post_id ] = 'Close';
        return 'Close';
    }

}
