<?php

class Easy_Image_Sizes {
    
    public $name = EAS_NAME;

    public $slug = EAS_SLUG;
    
    public $text_domain = EAS_KEY;
    
    public $post_type_name = EAS_KEY;

    public function __construct() {

        //Admin area
        add_action('admin_enqueue_scripts', array($this, 'registerResources'));
        
        add_action('init', array($this, 'registerPostType'));
        
        add_action('add_meta_boxes', array($this, 'addMetaBoxForEasyImageSizes'));
        
        add_action('save_post', array($this, 'saveForEasyImageSizes'));

        add_action('manage_easy_image_sizes_posts_custom_column', array($this, 'manageEasyImageSizesColumns'), 10, 2);
        
        add_filter('enter_title_here', array($this, 'changeTitlePlaceholderText'));
        
        add_filter('manage_edit-easy_image_sizes_columns', array($this, 'editEasyImageSizesColumns'));
        
        add_filter('post_row_actions', array($this, 'removeQuickEditFromPostList'), 10, 2);

        //Frontend
        add_filter( 'init', array($this, 'registerImageSizes'));
        
        add_filter( 'image_size_names_choose', array($this, 'loadImageSizes'));

    }
    
    /**
     * Admin resources for managing settings pages
     *
     * @return void
     *
     */
    public function registerResources() {

        EAS_Resource::load($this->slug . '-admin-script', 'admin.js', true);

        EAS_Resource::load($this->slug . '-admin-style', 'admin.css');

    }

    /**
     * Retrieves a collection of post types representing image sizes
     *
     * @return array
     *
     */
    public function getSizes() {

        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => $this->post_type_name,
            'post_status'      => 'publish',
        );
        
        return get_posts( $args );

    }

    /**
     * Create the post type to hold the images sizes
     * This approach makes it easy to import/export sizes via the built-in WordPress tool
     *
     * @return void
     *
     */
    public function registerPostType() {

        $labels = array(
            'name'                  => _x( 'Image Sizes', 'Post Type General Name', $this->text_domain ),
            'singular_name'         => _x( 'Image Size', 'Post Type Singular Name', $this->text_domain ),
            'menu_name'             => __( 'Easy Image Sizes', $this->text_domain ),
            'name_admin_bar'        => __( 'Easy Image Sizes', $this->text_domain ),
            'parent_item_colon'     => __( 'Parent Image Size:', $this->text_domain ),
            'all_items'             => __( 'All Image Sizes', $this->text_domain ),
            'add_new_item'          => __( 'Add New Image Size', $this->text_domain ),
            'add_new'               => __( 'Add New', $this->text_domain ),
            'new_item'              => __( 'New Item', $this->text_domain ),
            'edit_item'             => __( 'Edit Item', $this->text_domain ),
            'update_item'           => __( 'Update Item', $this->text_domain ),
            'view_item'             => __( 'View Item', $this->text_domain ),
            'search_items'          => __( 'Search Item', $this->text_domain ),
            'not_found'             => __( 'Not found', $this->text_domain ),
            'not_found_in_trash'    => __( 'Not found in Trash', $this->text_domain ),
            'items_list'            => __( 'Items list', $this->text_domain ),
            'items_list_navigation' => __( 'Items list navigation', $this->text_domain ),
            'filter_items_list'     => __( 'Filter items list', $this->text_domain ),
        );
        
        $capabilities = array(
            'edit_post'             => 'manage_options',
            'read_post'             => 'manage_options',
            'delete_post'           => 'manage_options',
            'edit_posts'            => 'manage_options',
            'edit_others_posts'     => 'manage_options',
            'publish_posts'         => 'manage_options',
            'read_private_posts'    => 'manage_options',
        );
        
        $args = array(
            'label'                 => __( 'Image Size', $this->text_domain ),
            'description'           => __( 'Easy Image Sizes Settings', $this->text_domain ),
            'labels'                => $labels,
            'supports'              => array( 'title', ),
            'hierarchical'          => false,
            'public'                => false,
            'menu_icon'             => 'dashicons-format-gallery',
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 80,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,		
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'capabilities'          => $capabilities,
        );
        
        register_post_type('easy_image_sizes', $args);

    }

    /**
     * Retrieves post meta for the specified image size
     * Uses the current $post object if no post_id passed
     *
     * @param  mixed $value
     * @param  int $post_id
     * @return mixed
     *
     */
    public function getMeta($value, $post_id = false) {

        global $post;
        
        $id = ($post_id) ? $post_id : $post->ID;

        $field = get_post_meta($id, $value, true);
        
        if ($field && $field != '') {
            
            return stripslashes(wp_kses_decode_entities($field));
            
        }
            
        return false;
        
    }
    
    /**
     * Changes placeholder text on post title for image size
     *
     * @param  string $title
     * @return string
     *
     */
    public function changeTitlePlaceholderText($title) {
    
        $current_screen = get_current_screen();
        
        if(isset($current_screen->post_type)) {
        
            if('easy_image_sizes' == $current_screen->post_type) {
        
                $title = 'Enter image size name';
            }
            
        }
        
        return $title;
    }

    /**
     * Adds a settings metabox to the corresponding images sizes post type
     *
     * @return void
     *
     */
    public function addMetaBoxForEasyImageSizes() {
    
        add_meta_box(
            'easy_image_sizes-easy_image_sizes',
            __( 'Easy Image Sizes', $this->text_domain ),
            array($this, 'fieldsForEasyImageSizes'),
            'easy_image_sizes',
            'normal',
            'core'
        );
        
    }
    
    /**
     * Settings HTML for the metabox callback
     *
     * @param  object $post
     * @return void
     *
     */
    public function fieldsForEasyImageSizes($post) {
        
        wp_nonce_field( '_easy_image_sizes_nonce', 'easy_image_sizes_nonce' );
        ?>

        <div class="easy_image_sizes_item">
            <label for="easy_image_sizes_width"><?php _e( 'Width', $this->text_domain ); ?> <span><?php _e( 'in px', $this->text_domain ); ?></span></label><br>
            <input type="number" name="easy_image_sizes_width" id="easy_image_sizes_width" placeholder="e.g. 500" value="<?php echo $this->getMeta( 'easy_image_sizes_width' ); ?>" required>
        </div>	
        <div class="easy_image_sizes_item">
            <label for="easy_image_sizes_height"><?php _e( 'Height', $this->text_domain ); ?> <span><?php _e( 'in px', $this->text_domain ); ?></span></label><br>
            <input type="number" name="easy_image_sizes_height" id="easy_image_sizes_height" placeholder="e.g. 250" value="<?php echo $this->getMeta( 'easy_image_sizes_height' ); ?>" required>
        </div>
        <div class="easy_image_sizes_item">
            <label for="easy_image_sizes_cropped"><?php _e( 'Cropped', $this->text_domain ); ?></label><br>
            <select name="easy_image_sizes_cropped" id="easy_image_sizes_cropped">
                <option <?php echo ($this->getMeta( 'easy_image_sizes_cropped' ) === 'No' ) ? 'selected' : '' ?>>No</option>
                <option <?php echo ($this->getMeta( 'easy_image_sizes_cropped' ) === 'Yes' ) ? 'selected' : '' ?>>Yes</option>
            </select>
        </div>
        <div class="easy_image_sizes_item easy_image_sizes_advanced">
            <label for="easy_image_sizes_crop_x"><?php _e( 'Crop X Axis', $this->text_domain ); ?> <span><?php _e( 'Default: centre', $this->text_domain ); ?></span></label><br>
            <select name="easy_image_sizes_crop_x" id="easy_image_sizes_crop_x">
                <option value="center" <?php echo ($this->getMeta( 'easy_image_sizes_crop_x' ) === 'center' ) ? 'selected' : '' ?>>Centre</option>
                <option value="left" <?php echo ($this->getMeta( 'easy_image_sizes_crop_x' ) === 'left' ) ? 'selected' : '' ?>>Left</option>
                <option value="right" <?php echo ($this->getMeta( 'easy_image_sizes_crop_x' ) === 'right' ) ? 'selected' : '' ?>>Right</option>
            </select>
        </div>
        <div class="easy_image_sizes_item easy_image_sizes_advanced">
            <label for="easy_image_sizes_crop_y"><?php _e( 'Crop Y Axis', $this->text_domain ); ?> <span><?php _e( 'Default: centre', $this->text_domain ); ?></span></label><br>
            <select name="easy_image_sizes_crop_y" id="easy_image_sizes_crop_y">
                <option value="center"<?php echo ($this->getMeta( 'easy_image_sizes_crop_y' ) === 'center' ) ? 'selected' : '' ?>>Centre</option>
                <option value="top" <?php echo ($this->getMeta( 'easy_image_sizes_crop_y' ) === 'top' ) ? 'selected' : '' ?>>Top</option>
                <option value="bottom" <?php echo ($this->getMeta( 'easy_image_sizes_crop_y' ) === 'bottom' ) ? 'selected' : '' ?>>Bottom</option>
            </select>
        </div>
        <?php
    }

    /**
     * Save settings to post meta for image sizes
     *
     * @param  int $post_id
     * @return void
     *
     */
    public function saveForEasyImageSizes($post_id) {
        
        $min_permission = 'edit_post';
        
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can($min_permission, $post_id)) {
            return;
        } 
        
        if(!isset($_POST['easy_image_sizes_nonce']) || !wp_verify_nonce($_POST['easy_image_sizes_nonce'], '_easy_image_sizes_nonce')) {
            return;
        }
        
        $fields = array(
            'easy_image_sizes_width',
            'easy_image_sizes_height',
            'easy_image_sizes_cropped',
            'easy_image_sizes_crop_x',
            'easy_image_sizes_crop_y'
        );
        
        $fields_to_strip = array(
            'easy_image_sizes_width',
            'easy_image_sizes_height'
        );
        
        foreach($fields as $field) {
            
            if (isset($_POST[$field])) {
            
                $field_val = (in_array($field, $fields_to_strip)) ? preg_replace("/[^0-9]/","",$_POST[$field]) : $_POST[$field];
                
                update_post_meta($post_id, $field, esc_attr($field_val));
                
            }
            
        }

    }

    /**
     * Define custom columns to display in the WordPress admin post list
     *
     * @param  array $columns
     * @return array
     *
     */
    public function editEasyImageSizesColumns($columns) {
    
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __( 'Image Size Name' ),
            'dimensions' => __( 'Dimensions' ),
            'cropped' => __( 'Cropped' ),
            'date' => __( 'Date' )
        );
    
        return $columns;
        
    }
    
    /**
     * Define content for custom columns used in WordPress admin post list
     *
     * @param  string $column
     * @param  int    $post_id
     * @return void
     *
     */
    function manageEasyImageSizesColumns($column, $post_id) {
        
        global $post;
    
        switch($column) {
    
            case 'dimensions':
                $width = $this->getMeta('easy_image_sizes_width', $post_id);
                
                $height = $this->getMeta('easy_image_sizes_height', $post_id);
                
                $dimensions = ($width) ? $width : 'Not Set';
                $dimensions .= 'x';
                $dimensions .= ($height) ? $height : 'Not Set';
                
                echo $dimensions;
                
                break;
    
            /* If displaying the 'genre' column. */
            case 'cropped':
                $cropped = $this->getMeta('easy_image_sizes_cropped', $post_id);
                
                echo ($cropped) ? $cropped : 'No';
    
                break;

            default :
                break;
                
        }
        
    }
    
    /**
     * Removes the quick edit inline action from post list
     *
     * @param  array $actions
     * @return array
     *
     */
    function removeQuickEditFromPostList($actions) {
        
        global $post;
        
        if($post->post_type == $this->post_type_name && is_admin()) {
            
            unset($actions['inline hide-if-no-js']);
            
        }
        
        return $actions;
    
    }

    /**
     * Registers all created image sizes with WordPress for use throughout the site
     *
     * @return void
     *
     */
    public function registerImageSizes() {

        $sizes = $this->getSizes();
        
        if(!empty($sizes)) {
        
            foreach($sizes as $size) {
                
                $is_cropped = $this->getMeta( 'easy_image_sizes_cropped', $size->ID);
                
                if($is_cropped == 'No') {
                    
                    $cropped = false;
                    
                }
                
                if($is_cropped == 'Yes') {
                    
                    $cropped = array(0 => 'center', 1 => 'center');
                                     
                    if($crop_x = $this->getMeta('easy_image_sizes_crop_x', $size->ID)) {
                        
                        $cropped[0] = $crop_x;
                        
                    }
                    
                    if($crop_y = $this->getMeta('easy_image_sizes_crop_y', $size->ID)) {
                        
                        $cropped[1] = $crop_y;
                        
                    }

                }
        
                add_image_size(
                    sanitize_title($size->post_title),
                    $this->getMeta( 'easy_image_sizes_width', $size->ID),
                    $this->getMeta( 'easy_image_sizes_height', $size->ID),
                    $cropped
                ); 
            
            }
            
        }
        
    }

    /**
     * Adds custom image sizes into the select list when inserting an image into a post
     *
     * @param  array $sizes
     * @return array
     *
     */
    public function loadImageSizes($sizes) {

        $addsizes = array();
        
        $image_sizes = $this->getSizes();
        
        foreach($image_sizes as $size) {
        
            $addsizes[sanitize_title($size->post_title)] = __($size->post_title);
        
        }
        
        $newsizes = array_merge($sizes, $addsizes);
        
        return $newsizes;
        
    }

}