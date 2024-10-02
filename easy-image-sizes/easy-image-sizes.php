<?php
/*
Plugin Name: Easy Image Sizes
Plugin URI:  https://github.com/ShiFuSteve/easy-image-sizes
Description: Add new images sizes to WordPress the easy way
Version: 1.3
Author: Stephen B
License: GPLv2 or later
*/

define('EAS_NAME', 'Easy Image Sizes');
define('EAS_SLUG', 'easy-image-sizes');
define('EAS_KEY',  'easy_image_sizes');

require(__DIR__ . "/resource.php");
require(__DIR__ . "/core.php");

new Easy_Image_Sizes();
