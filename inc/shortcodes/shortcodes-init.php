<?php 

namespace MWHP\Inc\Shortcodes;

use MWHP\Inc\Traits\Singleton;

class Shortcodes_Init{
    use Singleton;

    public function init(){
        Mw_Google_Map::get_instance();
    }
}