<?php
/*
Plugin Name: CropRefine
Plugin URI: http://wordpress.org/plugins/croprefine/
Description: Giving you greater control over how each of your media item sizes are cropped.
Version: 1.2.1
Author: era404
Author URI: http://www.era404.com
License: GPLv2 or later.
Copyright 2018 ERA404 Creative Group, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/***********************************************************************************
*     Globals
***********************************************************************************/
define('CROPREF_URL', admin_url() . 'admin.php?page=croprefine');

/***********************************************************************************
*     Setup Admin Menus
***********************************************************************************/
add_action( 'admin_init', 'croprefine_admin_init' );
add_action( 'admin_menu', 'croprefine_admin_menu' );
 
function croprefine_admin_init() {
	/* Register our stylesheet. */
	wp_register_style( 'croprefine-styles', plugins_url('croprefine.css', __FILE__) );
	wp_register_style( 'croprefine-cropper-styles', plugins_url('cropper/cropper.css', __FILE__) );
	/* and javascripts */
	wp_enqueue_script( 'croprefine-script', plugins_url('croprefine.js', __FILE__), array('jquery'), 1.0 ); 	// jQuery will be included automatically
	wp_enqueue_script( 'croprefine-cropper-script', plugins_url('cropper/cropper.js', __FILE__), array('jquery'), 1.0 ); 	// jQuery will be included automatically
	wp_localize_script('croprefine-script', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); 	// setting ajaxurl
}
add_action( 'wp_ajax_getimage', 'croprefine_getimage' ); 	//for loading image to refine
add_action( 'wp_ajax_cropimage', 'croprefine_cropimage' ); 	//for refining crop
 
function croprefine_admin_menu() {
	/* Register our plugin page */
	$page = add_menu_page(	'CropRefine', 
							'CropRefine', 
							'edit_posts', 
							'croprefine', 
							'croprefine_plugin_options', 
							plugins_url('croprefine/admin_icon.png') );

	/* Using registered $page handle to hook stylesheet loading */
	add_action( 'admin_print_styles-' . $page, 'croprefine_admin_styles' );
	add_action( 'admin_print_scripts-'. $page, 'croprefine_admin_scripts' );
}
 
function croprefine_admin_styles() {	
	wp_enqueue_style( 'croprefine-styles' ); 
	wp_enqueue_style( 'croprefine-cropper-styles' ); 
}
function croprefine_admin_scripts() {	
	wp_enqueue_script( 'croprefine-script' );
	wp_enqueue_script( 'croprefine-cropper-script' ); }
 
function croprefine_plugin_options(){
	echo "<div id='croprefine-administration'>"; //custom wrapper
	
	//uploads
	if(!empty($_FILES)) $results = croprefine_replaceimage();
	
	//successful uploads
	if(isset($_GET['done'])){
		$results = array("err"=>0,"msg"=>"");
		$r = array(	1=>" crop replaced. Uploaded image dimensions matched.<br />Clear your browser's cache and click the image name to see a preview.",
			   		2=>" crop replaced. Image uploaded and resized to match dimensions.<br />Clear your browser's cache and click the image name to see a preview.");
		foreach($r as $k=>$v) if(array_key_exists($k,$_GET['done'])) $results["msg"] .= "{$_GET['done'][$k]} {$r[$k]}<br />";
	}

	//requesting an image be refined
	$cropitem = (isset($_GET['item']) && is_numeric($_GET['item']) ? (int) sanitize_key( $_GET['item'] ) : false);
	if($cropitem && !$image = get_post( $cropitem )){
		echo "<br /><div class='notice notice-warning notice-nopad'><p>The image you have requested to refine could not be located.</p></div>";
		$cropitem = false;
	}
	
	//does this operation come from a post?
	$postitem = (isset($_GET['post']) && is_numeric($_GET['post']) ? (int) sanitize_key( $_GET['post'] ) : false);
	if($postitem && !$post = get_post( $postitem )){
		$postitem = false;
	}

	/* Output our admin page */
	echo "<h1>CropRefine</h1>";
	
	if($cropitem && $image){

		//form url 
		$formurl = admin_url()."admin.php?page=croprefine&item={$cropitem}";
		
		//javascript to fetch image from uploads directory
		echo "<script type='text/javascript'> var mediaitem = {$image->ID}; </script>";
		
		//path to wp-admin styles
		$styles = admin_url( 'load-styles.php?c=0&amp;dir=ltr&amp;load=media-views');
		
		//return to post button / close window
		$returnlink = (
			$postitem ? 
				'<a href="'.get_edit_post_link( $postitem, 1 ).'" class="button button-large modal-cropper-hide" id="cancel">Return to Post</a>' : 
				"<a href='javascript:window.close();' class='button button-large modal-cropper-hide' id='cancel'>Cancel</a>"
			);
		$closelink = (
			$postitem ? 
				get_edit_post_link( $postitem, 1 ) : 
				"javascript:window.close();"
			);
			
		//build modals
		echo "	
		<div id='modal-cropper' class='media-modal wp-core-ui' style='display:none;'>
				<a href='{$closelink}' class='media-modal-close modal-cropper-hide'><span class='media-modal-icon'><span class='screen-reader-text'>Close media panel</span></span></a>
				<div class='media-modal-content'><div class='edit-attachment-frame mode-select hide-menu hide-router'>
			<div class='media-frame-title'><h1>Re-Crop / Upload Image</h1></div>
			<div class='media-frame-content'><div tabindex='0' role='checkbox' aria-label='Embedded Image' aria-checked='false' data-id='10' class='attachment-details save-ready'>
			<div class='attachment-media-view landscape'>
				<div class='thumbnail thumbnail-image'>
					
					<div style='width: 500px; height: 500px;'>
						<div class='container' id='cropperimage'>
			  				<img />
					 	</div>
					 </div>
				</div>
			</div>
			<div class='attachment-info'>
				<span class='settings-save-status'>
					<span class='spinner'></span>
					<span class='saved'>Saved.</span>
				</span>
				<div class='missing'></div>
				<div class='details'>
					<form method='post' enctype='multipart/form-data' action='{$formurl}'>
				<table id='available-sizes' class='wp-list-table widefat fixed' style='display: none;'>
					<thead><tr><th>Name</th><th>Size</th><th class='actions'>Actions</th></tr></thead>
					<tbody id='sizes'>
					</tbody>
				</table>
			  </form>
			<div class='compat-meta'>
		
			</div>
		</div>
		
		<div class='actions'>
			{$returnlink}
			<a href='#' class='button button-primary button-large' id='savecrop'>Save Crop</a>							
		</div>
		
		<div class='results'>".
			(isset($results) ? ($results['err']<0 ? "<strong>Error: </strong>" : "<strong>Success: </strong>") . $results['msg'] : "").
		"</div>
	
	</div>
	</div></div>
	</div></div>
		</div>
		<div class='media-modal-backdrop' style='display:none;'></div>
		<div id='popover' class='popover' data-ui='popover-panel'>
			<div id='popover-preview'></div>
			<p><small>150 x 150 (native: 190 x 190)</small></p>
		</div>
		<style type='text/css'>
			.edit-attachment-frame .attachment-media-view, 
			.edit-attachment-frame .attachment-info { width: 50% !important; }
		</style>
		<link media='all' type='text/css' href='{$styles}' rel='stylesheet'>";	
	}
		$medialink = admin_url()."upload.php";

		//Plugin Images
		$pluginIMG = plugins_url('_img/', __FILE__);

		echo "<div class='instructions'>
					<div>
						Browse to <div class='dashicons-before dashicons-admin-media'></div> 
						<strong><a href='{$medialink}' title='Go to My Media Library'>Media</a></strong> 
						and select &quot;Refine&quot; beside any image.
						<p><a href='{$medialink}' title='Go to My Media Library'><img src='{$pluginIMG}screenshot-1.png' /></a></p>
					</div>
					<div>
						Or, select &quot;Refine&quot; from <div class='dashicons-before dashicons-edit'></div><strong>Image Details</strong> throughout WordPress.
						<p><a href='{$medialink}' title='Go to My Media Library'><img src='{$pluginIMG}screenshot-2.png' /></a></p>
					</div>
			  </div>";
		
		echo <<<PAYPAL
	<div id="croprefinefooter">
	<!-- paypal donations, please -->
<div class="footer">
	<div class="donate" style='display:none;'>
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="FPL96ZDKPHR72">
		<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/x-click-but04.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" class="donate">
		<p>If <b>ERA404's CropRefine WordPress Plugin</b> has made your life easier and you wish to say thank you, use the Secure PayPal link above to buy us a cup of coffee.</p>
	</div>
	<div class="bulcclub">
		<a href="https://www.bulc.club/?utm_source=wordpress&utm_campaign=croprefine&utm_term=croprefine" title="Bulc Club. It's Free!" target="_blank"><img src="{$pluginIMG}bulcclub.png" alt="Join Bulc Club. It's Free!" /></a>
		<p>For added protection from malicious code and spam, use Bulc Club's unlimited 100% free email forwarders and email filtering to protect your inbox and privacy. <strong><a href="https://www.bulc.club/?utm_source=wordpress&utm_campaign=croprefine&utm_term=croprefine" title="Bulc Club. It's Free!" target="_blank">Join Bulc Club &raquo;</a></strong></p>
	</div>
</div>
</div><!--end CropRefine -->
<footer><small>See more <a href='http://profiles.wordpress.org/era404/' title='WordPress plugins by ERA404' target='_blank'>WordPress plugins by ERA404</a> or visit us online: <a href='http://www.era404.com' title='ERA404 Creative Group, Inc.' target='_blank'>www.era404.com</a>. Thank you for using CropRefine.</small></footer>
</div>
PAYPAL;
		
	echo "</div>"; //custom wrapper
}

/**************************************************************************************************
*	Add a Button to the Media Library
**************************************************************************************************/
add_filter('media_row_actions', 'croprefine_media_edit_link', 10, 2);
function croprefine_media_edit_link($actions, $post) {
	//get media link
	$media_link = get_admin_url() . "admin.php?page=croprefine&item=".$post->ID;
    // adding the Action to the Quick Edit row
    $actions['CropRefine'] = "<a href='{$media_link}' title='CropRefine' target='_blank'>Refine</a>";
    return $actions;    
}

/**************************************************************************************************
*	Add a Button to the Media Modal
**************************************************************************************************/
add_action( 'attachment_fields_to_edit', 'croprefine_media_modal_edit_link', 10, 2 );
function croprefine_media_modal_edit_link($form_fields, $post) {
	$media_link = get_admin_url() . "admin.php?page=croprefine&item=".$post->ID;
	$back_link = (isset($_GET['post']) ? sanitize_key( $_GET['post'] ) : -1); 	
	$form_fields["croprefine"] = array(
			'label' => "", 
			'input' => "html", 
			'show_in_edit' => false,
			'html' =>  "<a href='{$media_link}' onclick='javascript:getBackLink(this);' title='CropRefine' target='_blank'>Refine</a>",
	);
	return $form_fields;
}
/**************************************************************************************************
*	Add a Button to the Image Details
**************************************************************************************************/
add_action( 'print_media_templates', function() { 
$media_link = get_admin_url() . "admin.php?page=croprefine"; 
if(isset($_GET['post']) && is_numeric($_GET['post'])){
	$cropitem = sanitize_key( $_GET['post'] );
	$media_link .= "&post={$cropitem}";
}
?>
   <script>
    jQuery(document).ready( function( $ ) {
        wp.media.view.ImageDetails = wp.media.view.ImageDetails.extend({
            resetFocus: function() {
            	var attid = parseInt(this.options.attachment.id);     	
            	if(typeof(attid) == "number" && this.$('.replace-attachment' ).length){
                	this.$("div.actions").append('<input type="button" class="refine-attachment button" value="Refine" />');
                	this.$(".refine-attachment").on("click",function(){ 
                    	var refine = '<?php echo $media_link;?>&item='+attid;
                    	document.location = refine;
                    });
            	}
            }
        });
    }); 
   </script> 
<?php } );
/**************************************************************************************************
*	Ajax Functions
*	full: $post->guid 	-or-   scaled: wp_get_attachment_image_url([id], "full" );
**************************************************************************************************/
function croprefine_getimage() {
	global $wpdb;
	header('Content-type: application/json');
	$uploads = wp_upload_dir();
	
	//some sanitizing
	if(sanitize_key($_POST['id'])!= $_POST['id']) returnerr("Bad Image ID.");
	$results = croprefine_image(sanitize_key($_POST['id']));	

	//return to crop tool
	die(json_encode($results));
}
//https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
function croprefine_get_sizes() {
	$wpsizes = get_intermediate_image_sizes(); 	//native sizes
	global $_wp_additional_image_sizes;			//custom sizes
	$sizes = array(); 							//results container
    foreach($wpsizes as $size){
        $sizes[ $size ][ 'width' ] = 	$w = intval( get_option( "{$size}_size_w" ) );
        $sizes[ $size ][ 'height' ] = 	$h = intval( get_option( "{$size}_size_h" ) );
        $sizes[ $size ][ 'crop' ] = 	$c = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
    }
    if(isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes)){
        $sizes = array_merge($sizes, $_wp_additional_image_sizes);
    }
    //myprint_r($sizes);
    return($sizes);
}
function croprefine_image( $cropitem ){
	$uploads = wp_upload_dir();
	if(!$image = get_post($cropitem)) returnerr("Image Not Found.");
	
	$sizes = croprefine_get_sizes();
	foreach($sizes as $sn=>$sv){
		$sizes[$sn]['size'] = 	$sn;
		$sizes[$sn]['url'] = 	$imageurl = 	wp_get_attachment_image_url($cropitem,$sn);
		$sizes[$sn]['path'] = 	$imagepath = 	str_replace($uploads['baseurl'],$uploads['basedir'],$imageurl);
		$sizes[$sn]['name'] = basename($imagepath);
		$sizes[$sn]['exists'] = (file_exists($imagepath)?1:-1);
		//use the aspect for non-crop images
		if(!$sv['crop'] && $sv['height']<1 && $is = getimagesize($imagepath)) $sizes[$sn]['height'] = $is[1];
		if(!$sv['crop'] && $sv['width']<1 && $is = getimagesize($imagepath)) $sizes[$sn]['width'] = $is[0];
	}
	//add the full image
	if($full = wp_get_attachment_image_src($cropitem,"full")){
		$sizes['full']['size'] = "full";
		$sizes['full']['width'] = $full[1];
		$sizes['full']['height'] = $full[2];
		$sizes['full']['crop'] = false;
		$sizes['full']['url'] = $imageurl = $full[0];
		$sizes['full']['path'] = $imagepath = str_replace($uploads['baseurl'],$uploads['basedir'],$full[0]);
		$sizes['full']['name'] = basename($imagepath);
		$sizes['full']['exists'] = (file_exists($imagepath)?1:-1);		
	}
	//add the original image, if it's not the same as the full image, for cropping
	if($image->guid != $full[0] && strstr($full[0],"-scaled")){
		$imageurl = $image->guid;
		$imagepath = str_replace($uploads['baseurl'],$uploads['basedir'],$image->guid);
		if($is = getimagesize($imagepath)){
			$sizes['original']['size'] = "original";
			$sizes['original']['width'] = $is[0];
			$sizes['original']['height'] = $is[1];
			$sizes['original']['crop'] = false;
			$sizes['original']['url'] = $imageurl;
			$sizes['original']['path'] = $imagepath;
			$sizes['original']['name'] = basename($imagepath);
			$sizes['original']['exists'] = 1;
		}
	}
	//myprint_r($sizes);
	//exit;
	$results = array(	"sizes"=> array_values($sizes),
						"path" => dirname($imagepath)."/",
						"url"  => dirname($imageurl),
						"image"=> $imageurl);
	//myprint_r($results);
	return($results);
}
/**************************************************************************************************
*	Crop Image
**************************************************************************************************/
function croprefine_cropimage(){
	header('Content-type: application/json');
	
	//sanitizing & validating
	if(empty($_POST)||!isset($_POST['cropitem'])||!isset($_POST['cropdata']))	returnerr("Bad Instructions.");
	if(!isset($_POST['id'])||sanitize_text_field($_POST['id'])!=$_POST['id'])	returnerr("Bad Item ID.");
	if(!isset($_POST['cropitem']) || count($_POST['cropitem'])!=4)				returnerr("Invalid Crop Source.");
	if(!isset($_POST['cropdata']) || count($_POST['cropdata'])!=4)				returnerr("Invalid Crop Instruction.");
	$item = $_POST['cropitem'];
	$crop = $_POST['cropdata'];
	if(!isset($item['w']) || !is_numeric($item['w']))							returnerr("Original Width is not valid.");
	if(!isset($item['h']) || !is_numeric($item['h'])) 							returnerr("Original Height is not valid.");
	if(!isset($crop['x']) || !is_numeric($crop['x'])) 							returnerr("Crop X value is not valid.");
	if(!isset($crop['y']) || !is_numeric($crop['y'])) 							returnerr("Crop Y value is not valid.");
	if(!isset($crop['width']) || !is_numeric($crop['width'])) 					returnerr("Crop Width value is not valid.");
	if(!isset($crop['height']) || !is_numeric($crop['height'])) 				returnerr("Crop Height value is not valid.");

	$id = 		(int) sanitize_text_field($_POST['id']);
	$width = 	(int) sanitize_text_field($item['w']);
	$height = 	(int) sanitize_text_field($item['h']);
	$size = 	(string) sanitize_text_field($item['size']);
	$cropx =	(int) round(sanitize_text_field($crop['x']));
	$cropy =	(int) round(sanitize_text_field($crop['y']));
	$cropw =	(int) round(sanitize_text_field($crop['width']));
	$croph =	(int) round(sanitize_text_field($crop['height']));

	//get all valid sizes, we need the original and the overwrite
	$results = croprefine_image( $id ); $match = false; $full = false;
	foreach($results['sizes'] as $s){
		if($s['size'] == "full" || $s['size'] == "original") $full = $s;
		if($s['size'] == $size && $s['height'] == $height && $s['width'] == $width) $match = $s;
	}
	if(!$match) return(returnerr("Bad Image Size.",false));
	if(!$full)  return(returnerr("Could Not Locate Original Image File.",false));

	//image properties
	$imagetemp = getimagesize($full['path']); 
	switch($imagetemp['mime']){
		case "image/jpeg":
		case "image/jpg":
			$source = imagecreatefromjpeg($full['path']);
			$ext = ".jpg"; break;
		case "image/png":
			$source = imagecreatefrompng($full['path']);
			$ext = ".png"; break;
		case "image/gif":
			$source = imagecreatefromgif($full['path']);
			$ext = ".gif"; break;
	}
	//recrop
	$recrop = imagecreatetruecolor( $width, $height );
	imagecopyresampled(	$recrop, $source, 	//resource $dst_image , resource $src_image 
						0, 0,  				//int $dst_x , int $dst_y ,
						$cropx, $cropy, 	//int $src_x , int $src_y 
						$width, $height, 	//int $dst_w , int $dst_h 
						$cropw, $croph);	//int $src_w , int $src_h
	// Output
	imagejpeg($recrop, $match['path'], 100);
	imagedestroy($recrop); imagedestroy($source);
	$item['err'] = (int) 0;
	die( json_encode( $item ));
}

function returnerr($err, $die=true){
	$err = array("err"=>-1,"msg"=>$err);
	if($die) die(json_encode($err));
	myprint_r($err);
	exit;
	return $err;
}


//if(!function_exists("myprint_r")){	function myprint_r($in) { echo "<pre>"; print_r($in); echo "</pre>"; return; }}

/**************************************************************************************************
*	Replace Image
**************************************************************************************************/
function croprefine_replaceimage(){
	//sanitizing & validating
	if(empty($_FILES) || !isset($_FILES['newimage'])) 							return(returnerr("Bad Upload.",false));
	if(empty($_POST)||!isset($_POST['cropitem'])) 								return(returnerr("Bad Instructions.",false));
	if(!isset($_POST['cropitem']['id']))										return(returnerr("Bad Item ID.",false));
	if(sanitize_key( $_POST['cropitem']['id'] ) != $_POST['cropitem']['id']) 	return(returnerr("Bad Item ID.",false));
	if(!isset($_POST['cropitem']['w'])||!is_numeric($_POST['cropitem']['w']))	return(returnerr("Bad Image Width.",false));
	if($_POST['cropitem']['w']<1)												return(returnerr("Bad Image Width.",false));
	if(!isset($_POST['cropitem']['h'])||!is_numeric($_POST['cropitem']['h']))	return(returnerr("Bad Image Height.",false));
	if($_POST['cropitem']['h']<1)												return(returnerr("Bad Image Height.",false));
	if(!isset($_POST['cropitem']['size'])||""==trim($_POST['cropitem']['size']))return(returnerr("Bad Image Size.",false));
	
	//instructions
	$id = 		sanitize_text_field($_POST['cropitem']['id']);
	$width = 	sanitize_text_field($_POST['cropitem']['w']);
	$height = 	sanitize_text_field($_POST['cropitem']['h']);
	$size = 	sanitize_text_field($_POST['cropitem']['size']);
	$newimage = $_FILES['newimage'];

	//get all valid sizes
	$results = croprefine_image( $id ); $match = false;
	foreach($results['sizes'] as $s){
		if($s['size'] == $size && $s['height'] == $height && $s['width'] == $width){
			$match = $s;
			break;
		}
	}
	if(!$match) return(returnerr("Bad Image Size.",false));

	$imagetemp = getimagesize($newimage['tmp_name']); //myprint_r($imagetemp);
	//image does not need to be resized
	if($imagetemp[0]==$width && $imagetemp[1]==$height){
		echo "Attempting to move: {$newimage['tmp_name']} to {$match['path']}";
		if(move_uploaded_file($newimage['tmp_name'], $match['path'])){
			$pluginurl = admin_url() . "admin.php?page=croprefine&item={$id}&done[1]={$width}x{$height}";
			return;
		}
		return(returnerr("Could not upload image.",false));		
	}
	//image does need to be resized
	switch($imagetemp['mime']){
		case "image/jpeg":
		case "image/jpg":
			$source = imagecreatefromjpeg($newimage['tmp_name']);
			$ext = ".jpg"; break;
		case "image/png":
			$source = imagecreatefrompng($newimage['tmp_name']);
			$ext = ".png"; break;
		case "image/gif":
			$source = imagecreatefromgif($newimage['tmp_name']);
			$ext = ".gif"; break;
	}
	//resize
	$resize = imagecreatetruecolor( $width, $height );
	imagecopyresampled(	$resize, $source, 						//resource $dst_image , resource $src_image 
						0, 0,  									//int $dst_x , int $dst_y ,
						0, 0, 									//int $src_x , int $src_y 
						$width, $height, 						//int $dst_w , int $dst_h 
						(int) $imagetemp[0], $imagetemp[1]);	//int $src_w , int $src_h
	// Output
	imagejpeg($resize, $match['path'], 100);
	imagedestroy($resize); imagedestroy($source);
	
	//store success to be displayed later
   	$pluginurl = admin_url() . "admin.php?page=croprefine&item={$id}&done[2]={$width}x{$height}";
   	echo "<script type='text/javascript'>window.location='{$pluginurl}';</script>";
   	return;
}
//add refine button to edit media page
add_action( 'attachment_submitbox_misc_actions', 'add_refinebutton_to_media_edit_page', 90 );
function add_refinebutton_to_media_edit_page() {
	global $post;
	if(!current_user_can('edit_posts'))	return;
	$media_link = get_admin_url() . "admin.php?page=croprefine&item=".$post->ID;
	$back_link = (isset($_GET['post']) ? sanitize_key( $_GET['post'] ) : -1); 	
	echo '<div class="misc-pub-section misc-pub-croprefine">';
	echo "<a href='{$media_link}' onclick='javascript:getBackLink(this);' title='CropRefine' target='_blank'>Refine</a>";
	echo '</div>';
}
?>