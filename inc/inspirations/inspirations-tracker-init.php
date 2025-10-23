<?php 

namespace MWHP\Inc\Inspirations;

use MWHP\Inc\Traits\Singleton;

class Inspirations_Tracker_Init {
    use Singleton;

    public function init() {
        Inspirations_Tracker::get_instance();
        Inspirations_Tracker_Delete::get_instance();
    }
}