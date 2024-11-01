<?php
/**
 * Plugin Name: TD Instagram Import
 * Plugin URI: http://www.transcendevelopment.com/td-instagram-import/
 * Description: Import your instagram pics
 * Version: 1.0.2
 * Author: TranscenDevelopment
 * Author URI: http://www.transcendevelopment.com
 * License: GPL2
 

 /*  Copyright 2014  Mike Ramirez  (email : transcendev@gmail.com)

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
 
global $wpdb;

define('DIRURL', plugin_dir_url( __FILE__ ));
define('TDIIVER', '1.0.2');
define('TDIIICON', '../wp-content/plugins/td-instagram-import/images/td_ii_icon.png');

$installed_ver = get_option( "td_ii_db_version" );
if ($installed_ver != TDIIVER) {
    update_option( "td_ii_db_version", TDIIVER );
}
register_activation_hook( __FILE__, 'td_ii_install');

add_action('admin_footer', 'td_ii_adminAjax');
add_action('admin_menu', 'td_ii_regMenuPage');
add_action('wp_ajax_td_ii_createPost', 'td_ii_createPost');
add_action('wp_ajax_td_ii_loadMore', 'td_ii_loadMore');

//---------------------------------------------------------//
function td_ii_page() {
//---------------------------------------------------------//
global $wpdb;

if (isset($_POST['td_ii_action'])) {$td_ii_action = filter_var($_POST['td_ii_action'], FILTER_SANITIZE_STRING);} else {$td_ii_action='';}

if ($td_ii_action == 'removeCID') {
	delete_option('td_ii_clientID');
	delete_option('td_ii_clientSEC');
	delete_option('td_ii_AccessToken');
	delete_option('td_ii_username');
	delete_option('td_ii_bio');
	delete_option('td_ii_website');
	delete_option('td_ii_picture');
	delete_option('td_ii_fullname');
	delete_option('td_ii_uID');
}

if (isset($_POST['td_ii_clientID'])) {$clientID = filter_var($_POST['td_ii_clientID'], FILTER_SANITIZE_STRING);} else {$clientID='';}
if (isset($_POST['td_ii_clientSEC'])) {$clientSEC = filter_var($_POST['td_ii_clientSEC'], FILTER_SANITIZE_STRING);} else {$clientSEC='';}
if ($clientID) {
    $theID 	= $clientID;
    $theSEC = $clientSEC;
	add_option('td_ii_clientID', $theID, '', 'yes');
	add_option('td_ii_clientSEC', $theSEC, '', 'yes');
}

$redirectURI 	= admin_url() . '?page=ttdii10';
$reURLnoEnc  	= $redirectURI;
$redirectURI 	= urlencode($redirectURI);
$adminURL 		= admin_url();
$td_ii_clientID = get_option('td_ii_clientID');
$td_ii_clientSEC = get_option('td_ii_clientSEC');

if (isset($_GET['code'])) {$code = filter_var($_GET['code'], FILTER_SANITIZE_STRING);} else {$code='';}
if ($code) {
	if (($td_ii_clientID) && ($td_ii_clientSEC)) {
		td_ii_getAuthorization($code, $reURLnoEnc, $td_ii_clientID, $td_ii_clientSEC);
	}
}

	$td_ii_AccessToken 	= get_option('td_ii_AccessToken');	
    $td_ii_username 	= get_option('td_ii_username');	
    $td_ii_bio 			= get_option('td_ii_bio');			
    $td_ii_website 		= get_option('td_ii_website');		
    $td_ii_picture 		= get_option('td_ii_picture');		
    $td_ii_fullname 	= get_option('td_ii_fullname');
    $td_ii_uID 			= get_option('td_ii_uID');
	
if (empty($td_ii_clientID)) {
 $whatToShow = "
 <form method='post'>
    <a href='http://instagram.com/developer/register/' target='_blank'>Create an Instagram Client</a>
	[ <a href='http://www.transcendevelopment.com/td-instagram-importer/help.html' target='_blank'>?</a> ]<br /><br />
	Your <strong>WEBSITE URL</strong> & <strong>REDIRECT URI</strong> should both be set to:<br /><strong>$adminURL</strong>
	<br /><br />
 	<strong>Instagram Client ID</strong><br />
 	<input type='text' name='td_ii_clientID' /><br />
 	<strong>Instagram Client Secret</strong><br />
 	<input type='text' name='td_ii_clientSEC' /><br />
 	<input type='submit' value='Set' />
 </form>
 ";
} elseif ($td_ii_AccessToken) {
 $whatToShow = "
    <form method='post' name='td_ii_removeCIDForm'>
    <input type='hidden' name='td_ii_action' value='removeCID' />
    </form>
    Instagram Client ID: $td_ii_clientID<br />
    <button onclick='document.td_ii_removeCIDForm.submit();'>Remove</button>
	<button onclick=\"location.href='https://api.instagram.com/oauth/authorize/?client_id=$td_ii_clientID&redirect_uri=$redirectURI&response_type=code'\">Re-Authorize</button>
	<div style='max-width:350px;padding:15px;background:#e1e1e1;border-radius:15px;margin-top:15px;margin-bottom:15px;'>
		<h2 style='padding:0 0 8px 0;margin:0;'>Your Instagram Account</h2>
		<div style='width:100%'>
			<div style='float:left;width:25%;text-align:left;'>
				<img src='$td_ii_picture' width='50' height='50' />
			</div>
			<div style='float:left;width:75%;'>
				Username: <a href='http://instagram.com/$td_ii_username' target='_blank'>$td_ii_username</a><br />
				Bio: $td_ii_bio<br />
			</div>
			<div style='clear:both'></div>
		</div>
	</div>
	<div>
		<h2>So...What shall we do?</h2>
		<a href='?page=ttdii10&action=showfeed&i=myposts'>Show My Photos</a> | <a href='?page=ttdii10&action=showfeed'>Show My Feed</a> 
	</div>
 ";
} else {
 $whatToShow = "
    <form method='post' name='td_ii_removeCIDForm'>
    <input type='hidden' name='td_ii_action' value='removeCID' />
    </form>
    Instagram Client ID: $td_ii_clientID<br />
    Instagram Client SEC: $td_ii_clientSEC<br />
    <button onclick='document.td_ii_removeCIDForm.submit();'>Remove</button><br /><br />
	<strong>Client Info Saved</strong><br />
	One last step: 
 	<a href='https://api.instagram.com/oauth/authorize/?client_id=$td_ii_clientID&redirect_uri=$redirectURI&response_type=code'>Authorize Your Blog</a>
 ";
}

$showFeed='';
if (isset($_GET['action'])) {$action = filter_var($_GET['action'], FILTER_SANITIZE_STRING);} else {$action='';}
if ($action == 'showfeed') {
	if ($td_ii_AccessToken) {
		$showFeed = td_ii_getUsersFeed(0, 0);
	}
} elseif ($action == 'bulkimport') {
	$showFeed = "
	<h3>Bulk Import</h3>
	
		<strong>Select a Date Range</strong><br />
		From: <input type=\"text\" id=\"MyDate\" name=\"MyDate\" value=\"\"/> &nbsp;&nbsp;
		To: <input type=\"text\" id=\"MyDate2\" name=\"MyDate\" value=\"\"/>
		<br /><br />
		
	";
}

echo <<<"HTML"
<div style="float:left;width:50%">
    <div style="padding:25px 0 25px 15px;">
    <h1>TD Instagram Import</h1>
    $whatToShow
    </div>
</div>
<div style="float:left;width:50%;">
	<div style="text-align:center;padding:25px;background:#e1e1e1;border-radius:15px;margin-top:25px;margin-right:25px;">
	<h2>Wanna Support This Project?</h2>
	If you like this plugin and would like to see continued development, please consider
	a donation. Thank you!<br /><br />
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="hosted_button_id" value="PBM7V2TGX9AM6">
	<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
	<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
	</form>
	<h3>Like this, but need something customized?</h3>
	<a href="http://www.transcendevelopment.com/contact_transcendev.html">Drop me a line</a>
    </div>
</div>
<div style="clear:both"></div>
<div style="width:100%" id="td_ii_loadBox">
$showFeed
</div>
HTML;
}
//---------------------------------------------------------//
function td_ii_getUsersFeed( $recursive, $count ) {
//---------------------------------------------------------//
global $wpdb;
	$retVal='';
    $td_ii_AccessToken 	= get_option('td_ii_AccessToken');
	$td_ii_uID			= get_option('td_ii_uID');
	if (isset($_GET['i'])) {$whichURL = filter_var($_GET['i'], FILTER_SANITIZE_STRING);} else {$whichURL='';}
	
	$extraQuery = "&count=10000";
	
	$page = $recursive;
	if ($page) {
		$getAccessURL = urldecode($page);
	} else {
		if ($whichURL == 'myposts') {
			$getAccessURL = 'https://api.instagram.com/v1/users/' . $td_ii_uID . '/media/recent/?access_token='.$td_ii_AccessToken . $extraQuery;
		} else {
			$getAccessURL = 'https://api.instagram.com/v1/users/self/feed?access_token='.$td_ii_AccessToken . $extraQuery;
		}
	}
	
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $getAccessURL); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $output = curl_exec($ch); 
    curl_close($ch);   
    
    $instAr = json_decode($output, true);
    
	if (isset($instAr['pagination']['next_url'])) {$nextPage = $instAr['pagination']['next_url'];} else {$nextPage='';}
	if ($nextPage) {$nextPage = urlencode($nextPage);}
	
    foreach ($instAr['data'] as $key => $value) {
		unset($tags);
		$tags='';
		if ($value['tags']) {
			foreach ($value['tags'] as $tag => $tagVal) {
				if ($tags) {
					$tags = $tags . ', ' . $tagVal;
				} else {
					$tags = $tagVal;
				}
			}
		}
		$thisImage = basename($value['images']['standard_resolution']['url']);
		$isPosted='none';
		$isPosted = td_ii_checkIfPosted($thisImage);
    	$retVal .= "
			<div style='position:relative;float:left;width:160px;height:175px;' onmouseover=\"document.getElementById('tdinst_". $value['id'] ."').style.display='block';\" onmouseout=\"document.getElementById('tdinst_". $value['id'] ."').style.display='none';\">
				<a href='". $value['link'] ."' target='_blank'><img src='" . $value['images']['thumbnail']['url'] . "'/></a>
				<div style='font-size:10px;'>Likes: ". $value['likes']['count'] ."&nbsp;&nbsp;Comments: " . $value['comments']['count'] . "</div>
				<div id='tdinst_". $value['id'] ."' style='display:none;position:absolute;text-align:center;top:50%;width:100%;background:#fff;opacity:0.8;padding:8px 0 8px 0;cursor:pointer;' onclick=\"td_ii_createNewPost('".$value['id']."');\">Import as Post</div>
				<input type='hidden' id=\"caption_".$value['id']."\" value=\"".addslashes($value['caption']['text'])."\" />
				<input type='hidden' id=\"link_".$value['id']."\" value=\"".$value['link']."\" />
				<input type='hidden' id=\"created_time_".$value['id']."\" value=\"".$value['created_time']."\" />
				<input type='hidden' id=\"thumbnail_".$value['id']."\" value=\"".$value['images']['thumbnail']['url']."\" />
				<input type='hidden' id=\"standard_resolution_".$value['id']."\" value=\"".$value['images']['standard_resolution']['url']."\" />
				<input type='hidden' id=\"tags_".$value['id']."\" value=\"".$tags."\" />
				<div id='tdinstI_". $value['id'] ."' style='display:$isPosted;position:absolute;text-align:center;top:50%;width:100%;background:#000;color:#fff;opacity:0.8;padding:8px 0 8px 0;cursor:pointer;' onclick=\"td_ii_createNewPost('".$value['id']."');\">Imported!</div>
			</div>
		";
	}
	if ($nextPage) {
		$retVal .= "
			<div id=\"load_$count\" style='clear:both;text-align:center;margin:0 auto;width:80%;font-size:15px;cursor:pointer;font-weight:bold;background:#e3e3e3;border-radius:5px;padding:7px 0 7px 0;' onclick=\"td_ii_loadMore('$nextPage',$count);\">
					Load more...
			</div>
		";
	} else {
		$retVal .= "<div style='clear:both;'></div>";
	}
    return $retVal;
}
//---------------------------------------------------------//
function td_ii_checkIfPosted( $theImage ) {
//---------------------------------------------------------//	
global $wpdb;

$flagFound='none';
$dbpre 		= $wpdb->prefix;
$posttable 	= $dbpre . 'posts'; 	
$sqlQuery = "
    SELECT id
    FROM $posttable
    WHERE post_content LIKE \"%$theImage%\"
";

$ids = $wpdb->get_results($sqlQuery);
foreach ( $ids as $id ) {$flagFound='block';}

return $flagFound;

}
//---------------------------------------------------------//
function td_ii_loadMore() {
//---------------------------------------------------------//
	$count 	= filter_var($_POST['count'], FILTER_SANITIZE_STRING);
	$page 	= filter_var($_POST['page'], FILTER_SANITIZE_STRING);
	$page 	= urldecode($page);
	echo td_ii_getUsersFeed($page, $count);
	die();
}
//---------------------------------------------------------//
function td_ii_createPost() {
//---------------------------------------------------------//	

$theCaption         = stripslashes( filter_var($_POST[theCaption], FILTER_SANITIZE_STRING) );
$theLink         	= filter_var($_POST[theLink], FILTER_SANITIZE_STRING);
$theCreated         = filter_var($_POST[theCreated], FILTER_SANITIZE_STRING);
$theThumb         	= filter_var($_POST[theThumb], FILTER_SANITIZE_URL);
$theStandard      	= filter_var($_POST[theStandard], FILTER_SANITIZE_URL);
$theTags         	= filter_var($_POST[theTags], FILTER_SANITIZE_STRING);
$theID				= filter_var($_POST[theID], FILTER_SANITIZE_STRING);

$wp_upload_dir 		= wp_upload_dir();
$imageURL			= $wp_upload_dir['url'];
$standardName 		= basename($theStandard);

$td_ii_importTags 	= get_option('td_ii_importTags');
$td_ii_cat 			= array( get_option('td_ii_cat') );
$td_ii_postType 	= get_option('td_ii_postType');
$theContent 		= html_entity_decode( get_option('td_ii_theTemplate') );

if (empty($td_ii_importTags)) {unset($theTags);} else {
	# remove hashtags from the caption since they're going in as WP tags.
	$theCaption		= preg_replace('/ #([\w-]+)/i', '', $theCaption);
}

$theContentAr 		= explode('\n', $theContent);
$theContentSwapped;
foreach ($theContentAr as $line) {
	$line			= preg_replace("/\[%theLink%\]/", $theLink, $line);
	$line			= preg_replace("/\[%theImage%\]/", "$imageURL/$standardName", $line);
	$line			= preg_replace("/\[%theCaption%\]/", $theCaption, $line);
	$theContentSwapped .= $line;
}
$post = array(
 'post_title' => wp_strip_all_tags($theCaption),
 'post_content' => $theContentSwapped,
 'post_status' => $td_ii_postType,
 'post_category' => $td_ii_cat,
 'tags_input' => $theTags
);
$post_id = wp_insert_post( $post, $wp_error );

$retVal;

if ($post_id > 0) {$retVal = td_ii_addImageToPost($post_id, $theThumb, $theStandard);}

echo 'Imported!';
die();
}
//---------------------------------------------------------//
function td_ii_addImageToPost($post_id, $theThumb, $theStandard) {
//---------------------------------------------------------//

// Not entirely sure we need to upload the thumb...leaving this here for now.
#$thumbName = basename($theThumb);
#td_ii_doImageAttach($theThumb, $thumbName, $post_id);

$standardName = basename($theStandard);
td_ii_doImageAttach($theStandard, $standardName, $post_id);
  
}
//---------------------------------------------------------//
function td_ii_doImageAttach($imageURL, $imageName, $post_id) {
//---------------------------------------------------------//	

$wp_upload_dir 	= wp_upload_dir();
$uploadTo 		= $wp_upload_dir['path'];
$subdir 		= $wp_upload_dir['subdir'];
$wp_filetype 	= wp_check_filetype($imageName, null );

	$ch = curl_init($imageURL);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$rawdata=curl_exec ($ch);
		curl_close ($ch);
	
	$fp = fopen("$uploadTo/$imageName",'w');
	fwrite($fp, $rawdata); 
	fclose($fp);
	  
	  $attachment = array(
		 'guid' => $wp_upload_dir['url'] . '/' . $imageName , 
		 'post_mime_type' => $wp_filetype['type'],
		 'post_title' => preg_replace( '/\.[^.]+$/', '', $imageName ),
		 'post_content' => '',
		 'post_status' => 'inherit'
	  );
	  $pathToImage = $subdir . '/' . $imageName;
	  $attach_id = wp_insert_attachment( $attachment, $pathToImage, $post_id );
	  require_once( ABSPATH . 'wp-admin/includes/image.php' );
	  $attach_data = wp_generate_attachment_metadata( $attach_id, $pathToImage );
	  wp_update_attachment_metadata( $attach_id, $attach_data );	
}
//---------------------------------------------------------//
function td_ii_adminAjax() {
//---------------------------------------------------------//
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
?>
<script>
jQuery(document).ready(function() {
    jQuery('#MyDate').datepicker({
        dateFormat : 'dd-mm-yy',
		changeMonth: true,
		changeYear: true
    });
    jQuery('#MyDate2').datepicker({
        dateFormat : 'dd-mm-yy',
		changeMonth: true,
		changeYear: true
    });	
});	
function td_ii_createNewPost(x) {
            var pID = x;
            var caption = document.getElementById("caption_"+pID).value;
			var link = document.getElementById("link_"+pID).value;
			var created = document.getElementById("created_time_"+pID).value;
			var thumb = document.getElementById("thumbnail_"+pID).value;
			var standard = document.getElementById("standard_resolution_"+pID).value;
			var tags = document.getElementById("tags_"+pID).value;

            var data = {
                    action: 'td_ii_createPost',
                    theCaption: caption,
					theLink: link,
					theCreated: created,
					theThumb: thumb,
					theStandard: standard,
					theTags: tags,
					theID: pID
            };
            jQuery.post(ajaxurl, data, function(response) {
                   document.getElementById('tdinstI_'+x).style.display='block';
            });
}
function td_ii_loadMore(x, y) {
			document.getElementById('load_'+y).innerHTML = 'Hey...give me a second will ya...';
			var nextCount = parseInt(y+1);
            var data = {
                    action: 'td_ii_loadMore',
					page: x,
					count: nextCount
            };
            jQuery.post(ajaxurl, data, function(response) {
				  document.getElementById('load_'+y).style.display = 'none';
                  document.getElementById('td_ii_loadBox').innerHTML = document.getElementById('td_ii_loadBox').innerHTML + response;
            });	
}
</script>
<?
}
//---------------------------------------------------------//
function td_ii_getAuthorization($rtnCode, $reURLnoEnc, $td_ii_clientID, $td_ii_clientSEC) {
//---------------------------------------------------------//
	$theCode = filter_var($rtnCode, FILTER_SANITIZE_STRING);
	$theQuery = array(
		'client_id'		=> $td_ii_clientID,
		'client_secret' => $td_ii_clientSEC,
		'grant_type'	=> 'authorization_code',
		'redirect_uri'	=> $reURLnoEnc,
		'code'			=> $theCode
	);
	$getAccessURL = 'https://api.instagram.com/oauth/access_token';
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $getAccessURL); 
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($theQuery));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    $output = curl_exec($ch); 
    curl_close($ch);   
    
    $instObj = json_decode($output);
    
		$td_ii_AccessToken 	= $instObj->access_token;
		$td_ii_username 	= $instObj->user->username;
		$td_ii_bio 			= $instObj->user->bio;
		$td_ii_website 		= $instObj->user->website;
		$td_ii_picture		= $instObj->user->profile_picture;
		$td_ii_fullname		= '';
		$td_ii_uID			= $instObj->user->id;
		delete_option('td_ii_AccessToken');	add_option('td_ii_AccessToken', $td_ii_AccessToken,'', 'yes');
		delete_option('td_ii_username');	add_option('td_ii_username', $td_ii_username,'', 'yes');
		delete_option('td_ii_bio');			add_option('td_ii_bio', $td_ii_bio,'', 'yes');
		delete_option('td_ii_website');		add_option('td_ii_website', $td_ii_website,'', 'yes');
		delete_option('td_ii_picture');		add_option('td_ii_picture', $td_ii_picture,'', 'yes');
		delete_option('td_ii_fullname');	add_option('td_ii_fullname', $td_ii_fullname,'', 'yes');
		delete_option('td_ii_uID');			add_option('td_ii_uID', $td_ii_uID,'', 'yes');

}
//---------------------------------------------------------//
function td_ii_settings() {
//---------------------------------------------------------//
if (isset($_POST['action'])) {$action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);} else {$action='';}
if ($action == 'td_ii_saveSettings') {
if (isset($_POST['td_ii_importTags'])) {$td_ii_importTags = filter_var($_POST['td_ii_importTags'], FILTER_SANITIZE_STRING);}
	else {$td_ii_importTags='';}
$td_ii_cat 				= filter_var($_POST['td_ii_cat'], FILTER_SANITIZE_STRING);
$td_ii_postType 		= filter_var($_POST['td_ii_postType'], FILTER_SANITIZE_STRING);
$td_ii_theTemplate		= filter_var($_POST['td_ii_theTemplate'], FILTER_SANITIZE_SPECIAL_CHARS);

delete_option('td_ii_importTags');
delete_option('td_ii_cat');
delete_option('td_ii_postType');
delete_option('td_ii_theTemplate');

add_option('td_ii_importTags', $td_ii_importTags, '', 'yes');
add_option('td_ii_cat', $td_ii_cat, '', 'yes');
add_option('td_ii_postType', $td_ii_postType, '', 'yes');
add_option('td_ii_theTemplate', $td_ii_theTemplate, '', 'yes');

echo "<div style='color:green;'>Settings Saved</div>";
}

$select1=''; $select2='';
$td_ii_importTags 		= get_option('td_ii_importTags');
if ($td_ii_importTags == 1) {$checkBox1='checked';} else {$checkBox1='';}
$td_ii_cat 				= get_option('td_ii_cat');
$td_ii_postType 		= get_option('td_ii_postType');
if ($td_ii_postType == 'publish') {$select2='selected';} else {$select1='selected';}
$td_ii_theTemplate		= stripslashes( get_option('td_ii_theTemplate') );
if (empty($td_ii_theTemplate)) {$td_ii_theTemplate = td_ii_defaultTemplate();}

echo <<<"HTML"
<form method="post">
<div style="width:100%">
    <div style="padding:25px 0 25px 15px;">
    <h1>TD Instagram Import - Settings</h1>
	<input type="checkbox" name="td_ii_importTags" value="1" $checkBox1 />&nbsp;<strong>Import hashtags as Wordpress tags</strong>
	<br /><br />
	<strong>Default category to post to</strong><br />
HTML;
	wp_dropdown_categories("hide_empty=0&name=td_ii_cat&selected=$td_ii_cat");
echo <<<"HTML"
	<br /><br />
	<strong>Save imported posts as</strong><br />
	<select name="td_ii_postType">
	<option value="draft" $select1>Draft</value>
	<option value="publish" $select2>Published</value>
	</select>
	<br /><br />
	<strong>Post Formatting</strong><br />
	<textarea name="td_ii_theTemplate" cols="75" rows="10">$td_ii_theTemplate</textarea>
	<div id="td_ii_shortCodes" style="display:none">
		[%theLink%] = The Instagram link to an imported post.<br />
		[%theImage%] = The url to the imported image.<br />
		[%theCaption%] = The caption of an imported Instagram post.
	</div>
	<br /><a href="javascript:void(0);" id="td_ii_scLink" onclick="
		if (document.getElementById('td_ii_shortCodes').style.display=='block') {
			document.getElementById('td_ii_shortCodes').style.display='none';
			document.getElementById('td_ii_scLink').innerHTML='Show Available Shortcodes';
		} else {
			document.getElementById('td_ii_scLink').innerHTML='Hide Shortcodes';
			document.getElementById('td_ii_shortCodes').style.display='block';
		}
	">Show Available Shortcodes</a>
</div>
<input type="submit" value="Save Settings" />
<input type="hidden" name="action" value="td_ii_saveSettings" />
</form>
HTML;
}
//---------------------------------------------------------//
function td_ii_regMenuPage() {
//---------------------------------------------------------//    
	//add_menu_page('The Ticket System', 'Ticket System', 'administrator', 'tdii10', 'td_tts_mainTTSAdmin', TDTTSICON, 26);
   add_menu_page('TD Instagram', 'TD Instagram', 'administrator', 'ttdii10', 'td_ii_page', TDIIICON, '312.312');
   add_submenu_page('ttdii10', 'Settings', 'Settings', 'administrator', 'ttdii10_1', 'td_ii_settings');
}
//---------------------------------------------------------//
function td_ii_defaultTemplate() {
//---------------------------------------------------------//	
$retVal = '
<div>
  <div>
  <a href="[%theLink%]" rel="nofollow" target="_blank"><img src="[%theImage%]" /></a>    
  </div>
  <div>[%theCaption%]</div>
</div>
';
return $retVal;
}
//---------------------------------------------------------//
function td_ii_install() {
//---------------------------------------------------------//    
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$theTemplate = '
		<div>
			<div><a href="[%theLink%]" rel="nofollow" target="_blank"><img src="[%theImage%]" /></a></div>
			<div>[%theCaption%]</div>
		</div>
	';
	add_option( "td_ii_theTemplate", $theTemplate, '', 'yes');
    add_option( "td_ii_db_version", "1.0.2" );
	
}


?>
