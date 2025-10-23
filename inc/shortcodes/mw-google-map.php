<?php 

namespace MWHP\Inc\Shortcodes;

use MWHP\Inc\Services\Branch_Business_Hours;
use MWHP\Inc\Settings\Map_Settings;
use MWHP\Inc\Traits\Singleton;

class Mw_Google_Map{
    use Singleton;
    private function init(){
        add_shortcode('mw_google_map', [$this, 'render']);
    }

    public function render()
    {
        global $wpdb, $wp;

        $distance_select = '';
        $distance_where = '';

        if (isset($_GET['latitude']) && !empty($_GET['latitude'])) {
            $lat = $_GET['latitude'];
            $lng = $_GET['longitude'];

            $distance_select = ', (
                6371 * ACOS(
                    COS(RADIANS(' . $lat . ')) * COS(RADIANS(postmeta.meta_value)) *
                    COS(RADIANS(postmeta2.meta_value) - RADIANS(' . $lng . ')) +
                    SIN(RADIANS(' . $lat . ')) * SIN(RADIANS(postmeta.meta_value))
                )
            ) AS distance';

            $distance_where = ' HAVING distance <= 50';
        }

        $qry = $wpdb->get_results("select posts.ID, posts.post_title, postmeta.meta_value as latitude, postmeta2.meta_value as longitude " . $distance_select . " from $wpdb->posts as posts, $wpdb->postmeta as postmeta, $wpdb->postmeta as postmeta2 where posts.post_type = 'filialen' and posts.post_status = 'publish' and posts.ID = postmeta.post_id and postmeta.meta_key = 'latitude' and posts.ID = postmeta2.post_id and postmeta2.meta_key = 'longitude' " . $distance_where . " order By posts.post_title asc");

        $markers = [];

        $map_api = Map_Settings::get_api_key();

        if ($qry) {
            foreach ($qry as $row) {
                $address = get_field('address', $row->ID);
                $latitude = get_field('latitude', $row->ID);
                $longitude = get_field('longitude', $row->ID);

                $markers[] = [
                    'name' => $row->post_title,
                    'address' => $address,
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'phone' => get_field('phone', $row->ID) ?? "",

                    'open_office_timings' => get_field('open_timing', $row->ID),
                    'close_office_timings' => get_field('close_timing', $row->ID),

                    'saturday_open_office_timings' => get_field('open_timing_saturday', $row->ID),
                    'saturday_close_office_timings' => get_field('close_timing_saturday', $row->ID),

                    'sunday_open_office_timings' => get_field('open_timing_sunday', $row->ID),
                    'sunday_close_office_timings' => get_field('close_timing_sunday', $row->ID),

                    'total_reviews' => get_field('total_reviews', $row->ID) ?? 10,
                    'total_rating' => get_field('total_rating', $row->ID) ?? 5,
                    'button_link' => get_the_permalink($row->ID),
                    'image' => wp_get_attachment_url(get_post_thumbnail_id($row->ID)),
                    'status' => Branch_Business_Hours::get_status($row->ID),
                ];
            }
        }

        ob_start(); // Start output buffering ?>
        <style>
            #map { width:auto; height:586px;}
        </style>

        <link rel="stylesheet preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
        <div id="map"></div>
        <form action="" method="get">
            <input type="hidden" name="latitude" id="latitude" value="">
            <input type="hidden" name="longitude" id="longitude" value="">
            <div class="search-box">
                <input type="search" class="form-control" name="search" id="places_autocomplete" placeholder="Stadt oder PLZ eingeben" autocomplete="off">
                <button type="submit" class="btn-1">Filiale suchen</button>
                <?php if (isset($_GET['latitude']) && !empty($_GET['latitude'])) {?>
                <a href="<?php echo home_url($wp->request); ?>" class="btn-1">Zurücksetzen</a>
                <?php }?>
            </div>
        </form>
        <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=<?php echo $map_api; ?>"></script>
        <script>
            var input = document.getElementById('places_autocomplete');
            var options = {
                types: ['geocode']
            };

            var autocomplete = new google.maps.places.Autocomplete(input, options);

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                var latitude = place.geometry.location.lat();
                var longitude = place.geometry.location.lng();

                document.getElementById('latitude').value = latitude;
                document.getElementById('longitude').value = longitude;
            });

            var gmarkers = [];
            var markers = <?php echo json_encode($markers); ?>;
            var centerCoordinates = { lat: 48.853576, lng: 10.492373 };

            var myOptions = {
                // mapTypeId: google.maps.MapTypeId.ROADMAP,
                // mapTypeControl: false,
                center: centerCoordinates,
                zoom: <?php echo wp_is_mobile() ? 6 : 7; ?>
            };

            var map = new google.maps.Map(document.getElementById("map"), myOptions);
            var infowindow = new google.maps.InfoWindow();
            var marker, i;
            // var bounds = new google.maps.LatLngBounds();

            for (i = 0; i < markers.length; i++) {
                var pos = new google.maps.LatLng(markers[i]['lat'], markers[i]['lng']);


                // get values (safe with optional chaining + fallback)
                const saturdayOpen = (markers[i]?.saturday_open_office_timings ?? '').trim();
                const saturdayClose = (markers[i]?.saturday_close_office_timings ?? '').trim();

                // build optional saturday row
                const saturdayRow = saturdayOpen
                ? `<div class="row no-gutters">
                    <div class="col-4"><p>Sa.</p></div>
                    <div class="col-8"><p>${saturdayOpen} - ${saturdayClose}</p></div>
                    </div>`
                : '';

                // get values (safe with optional chaining + fallback)
                const sundaydayOpen = (markers[i]?.sunday_open_office_timings ?? '').trim();
                const sundayClose = (markers[i]?.sunday_close_office_timings ?? '').trim();

                // build optional saturday row
                const sundayRow = sundaydayOpen
                ? `<div class="row no-gutters">
                    <div class="col-4"><p>Su.</p></div>
                    <div class="col-8"><p>${sundaydayOpen} - ${sundayClose}</p></div>
                    </div>`
                : '';

                const hasPhoneNumber = (markers[i]?.phone ?? '').trim();

                const phoneRow = hasPhoneNumber
                ? `<div class="xmseWebIconcontainer">
                    <div class="wrapper">
                    <a href="tel:${markers[i]['phone']}">${markers[i]['phone']}</a>
                    </div>
                </div>`
                : '';

                var content = `<div class="info_content">
                    <div class="text-nowrap p-1 bg-white rounded content">
                        <h3 class="mb-0">${markers[i]['name']}</h3>
                        <span><img src="<?php echo MWHP_PATH_URI; ?>/assets/images/stars-pop.png" alt="">${markers[i]['total_rating']} Sterne (${markers[i]['total_reviews']} Bewertungen)</span>
                        <div class="row">
                        <div class="col-12 col-md-6">
                            <img src="${markers[i]['image']}" alt="" class="map-b-img">
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="xmseWebIconcontainer">
                            <div class="wrapper">
                                <p class="mb-0">${markers[i]['address']}</p>
                            </div>
                            </div>
                            ${phoneRow}
                            <div class="xmseWebIconcontainer">
                            <div class="wrapper">
                                <div class="openingHours">
                                <div class="row no-gutters">
                                    <div class="col-4"><p>Mo.-Fr.</p></div>
                                    <div class="col-8"><p>${markers[i]['open_office_timings']} - ${markers[i]['close_office_timings']}</p></div>
                                </div>

                                ${saturdayRow}

                                ${sundayRow}

                                <span class="text-success"><p>${markers[i]['status']}</p></span>
                                </div>
                            </div>
                            </div>
                            <a class="d-block btn btn-primary mt-1" href="${markers[i]['button_link']}">Weitere Infos</a>
                        </div>
                        </div>
                    </div>
                </div>`;

                // bounds.extend(pos);
                marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    icon: '<?php echo MWHP_PATH_URI; ?>/assets/images/map-marker.png'
                });

                gmarkers.push(marker);
                google.maps.event.addListener(marker, 'click', (function(marker, content) {
                    return function() {
                        infowindow.setContent(content);
                        infowindow.open(map, marker);
                    }
                })(marker, content));
            }

            // map.fitBounds(bounds);
        </script>
    <?php
    echo ob_get_clean();
    }
}