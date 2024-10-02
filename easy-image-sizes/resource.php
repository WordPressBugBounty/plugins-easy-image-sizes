<?php

class EAS_Resource {

    /**
     * Loads a resource the WordPress way for Easy Image Sizes
     *
     * @param  string $name
     * @param  string $file_name
     * @param  bool   $is_script
     * @return void
     *
     */
	public static function load($name, $file_name, $is_script=false) {

		$file_path = '/resources/'.$file_name;

		$url = plugins_url($file_path, __FILE__);
        
        $file = plugin_dir_path(__FILE__) . $file_path;

        if(file_exists($file)) {
        
            if($is_script) {
            
                wp_register_script( $name, $url, array('jquery') );
                
                wp_enqueue_script( $name );
                
            } else {
            
                wp_register_style($name, $url);
                
                wp_enqueue_style($name);
                
            } 
        }
	}    

}
