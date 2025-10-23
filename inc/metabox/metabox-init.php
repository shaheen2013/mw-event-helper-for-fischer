<?php 

namespace MWHP\Inc\Metabox;

use MWHP\Inc\Traits\Singleton;

class Metabox_Init{
    use Singleton;

    public function init(){
        GPB_Metabox::get_instance();
        Clear_Weekday_Cache::get_instance();
    }
}