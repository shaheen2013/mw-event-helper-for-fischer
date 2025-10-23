<?php 

namespace MWHP\Inc\Services;

class Branch_Business_Hours{

    public static function get_status( $id ) {

        $tz_string = get_option( 'timezone_string' );
        if ( empty( $tz_string ) ) {
            $offset  = (float) get_option( 'gmt_offset' );
            $tz_name = timezone_name_from_abbr( '', (int) ( $offset * 3600 ), 0 );
            $tz      = new \DateTimeZone( $tz_name ?: 'UTC' );
        } else {
            $tz = new \DateTimeZone( $tz_string );
        }

        $now     = new \DateTime( 'now', $tz );
        $today   = $now->format( 'Y-m-d' );
        $weekday = $now->format( 'l' ); // e.g. Monday, Saturday, Sunday

        $default_open  = trim( (string) get_field( 'open_timing', $id ) );
        $default_close = trim( (string) get_field( 'close_timing', $id ) );

        $open_time  = '';
        $close_time = '';

        $special_days  = get_field( 'special_days', $id );
        $special_handled = false;
        if ( $special_days && is_array( $special_days ) ) {
            foreach ( $special_days as $day ) {
                if ( empty( $day['date'] ) ) {
                    continue;
                }

                $sd = parse_date_flexible( $day['date'], $tz );
                if ( ! $sd ) {
                    continue;
                }

                if ( $sd->format( 'Y-m-d' ) === $today ) {
                    $is_off = false;
                    if ( isset( $day['is_off_day'] ) ) {
                        $val = $day['is_off_day'];
                        if ( is_bool( $val ) ) {
                            $is_off = $val;
                        } else {
                            $val_norm = strtolower( trim( (string) $val ) );
                            $is_off = in_array( $val_norm, array( 'yes', 'true', '1', 'y' ), true );
                        }
                    }

                    if ( $is_off ) {
                        return 'Geschlossen';
                    }

                    if ( ! empty( $day['opening_time'] ) || ! empty( $day['closing_time'] ) ) {
                        $open_time  = isset( $day['opening_time'] ) ? trim( (string) $day['opening_time'] ) : '';
                        $close_time = isset( $day['closing_time'] ) ? trim( (string) $day['closing_time'] ) : '';
                    } else {
                        return 'Geschlossen';
                    }

                    $special_handled = true;
                    break;
                }
            }
        }

        if ( ! $special_handled || ( $special_handled && ( $open_time === '' || $close_time === '' ) ) ) {
            if ( $weekday === 'Saturday' ) {
                $open_time  = $open_time !== '' ? $open_time : trim( (string) get_field( 'open_timing_saturday', $id ) );
                $close_time = $close_time !== '' ? $close_time : trim( (string) get_field( 'close_timing_saturday', $id ) );

                if ( empty( $open_time ) || empty( $close_time ) ) {
                    return 'Geschlossen';
                }
            } elseif ( $weekday === 'Sunday' ) {
                $open_time  = $open_time !== '' ? $open_time : trim( (string) get_field( 'open_timing_sunday', $id ) );
                $close_time = $close_time !== '' ? $close_time : trim( (string) get_field( 'close_timing_sunday', $id ) );

                if ( empty( $open_time ) || empty( $close_time ) ) {
                    return 'Geschlossen';
                }
            } else {
                $open_time  = $open_time !== '' ? $open_time : $default_open;
                $close_time = $close_time !== '' ? $close_time : $default_close;

                if ( empty( $open_time ) || empty( $close_time ) ) {
                    return 'Geschlossen';
                }
            }
        }

        try {
            $open_dt  = new \DateTime( $today . ' ' . $open_time, $tz );
            $close_dt = new \DateTime( $today . ' ' . $close_time, $tz );
        } catch ( \Exception $e ) {
            return 'Geschlossen';
        }

        if ( $close_dt <= $open_dt ) {
            $close_dt->modify( '+1 day' );
        }

        $now_ts   = $now->getTimestamp();
        $open_ts  = $open_dt->getTimestamp();
        $close_ts = $close_dt->getTimestamp();

        if ( $now_ts >= $open_ts && $now_ts <= $close_ts ) {
            $time_difference = $close_ts - $now_ts;
            if ( $time_difference > 0 && $time_difference <= HOUR_IN_SECONDS ) {
                return 'Schließt innerhalb 1 Stunde';
            }
            return 'Geöffnet';
        }

        return 'Geschlossen';
    }
}