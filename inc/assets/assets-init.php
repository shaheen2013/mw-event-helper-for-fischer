<?php 
namespace MWHP\Inc\Assets;

use MWHP\Inc\Traits\Singleton;

class Assets_Init{

    use Singleton;
    public function init(){
        Backend_Assets::get_instance();
        Frontend_Assets::get_instance();
    }
}