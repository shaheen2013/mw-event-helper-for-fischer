<?php 

namespace MWHP\Inc\Admin;

use MWHP\Inc\Traits\Singleton;

class Admin_Init{
    use Singleton;
    public function init(){
        Map_Settings::get_instance();
        Inspiration_Tracker_Page::get_instance();
    }
}