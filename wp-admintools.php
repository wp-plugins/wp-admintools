<?php
/*
Plugin Name: WP AdminTools
Version: 1.3.9
Plugin URI: http://www.g2smedia.de/wp-admintools/
Description: Control additional Wordpress, SEO and Database features with this swiss army knife for WordPress.
Author: Stefan Seibel
Author URI: http://www.seibel-internet.de/
Text Domain: sisat
Domain Path: /lang
*/

define('SISAT_VERSION', '1.3.9');

$sisat_plugin_header_translate = array(
    __('Control additional Wordpress, SEO and Database features with this swiss army knife for WordPress.', 'sisat')
);

function sisat_lang(){
    $langDir = plugin_basename(dirname(__FILE__)) . '/lang';
    load_plugin_textdomain('sisat', false, $langDir);
	
	// check for plugin version
	if($pluginversion = get_option('sisat_version')) {
		if($pluginversion<'1.3.4') {
		    $options = get_option('sisat_settings');
		    $options['noarchive'] = 0;
		    update_option( 'sisat_settings', $options);
		}
		update_option( "sisat_version", SISAT_VERSION );
	} else {
		add_option("sisat_version", SISAT_VERSION);
		// add the plugin version number into options
	}
}

add_action('init', 'sisat_lang');

add_action('admin_init', 'sisat_init' );

function sisat_init(){
    register_setting( 'sisat_options', 'sisat_settings', 'sisat_validatedata' );
}

function sisat_validatedata($array) {
    
    $options = get_option('sisat_settings');

    if(isset($_POST['tab1'])) {
	update_option( $options['tab'], '0' );
	$options['tab'] = '0';
    } else if(isset($_POST['tab2'])) {
	update_option( $options['tab'], '1' );
	$options['tab'] = '1';
    } else if(isset($_POST['tab3'])) {
	update_option( $options['tab'], '2' );
	$options['tab'] = '2';
    }
    
    
    $array['tab']=$options['tab'];
    
    if(isset($array['disabletrash']) && is_numeric($array['disabletrash']) && $array['disabletrash']>=0 && $array['disabletrash']<=30) {
        $array['disabletrash']=$array['disabletrash'];
    }
    
    if(isset($array['autosave']) && is_numeric($array['autosave']) && $array['autosave']>=0 && $array['autosave']<=1000000) {
        $array['autosave']=$array['autosave'];
    }
    
    if(isset($array['netlink']) && is_numeric($array['netlink']) && $array['netlink']>=0 && $array['netlink']<=2) {
        $array['netlink']=$array['netlink'];
    }

    if(isset($array['robots']) && trim($array['robots'])!="") {
	$array['robots']=preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", esc_attr($array['robots']));
    }
    
    $alloptions = array(
	    'revisions',
	    'noindex-404',
	    'noindex-archive',
	    'noindex-category',
	    'noindex-tag',
	    'noindex-author',
	    'noindex-date',
	    'noindex-search',
	    'noindex-attachment',
	    'noindex-posts',
	    'noindex-pages',
	    'title',
	    'blogtitle',
	    'feedlink',
	    'feedlinkextra',
	    'rsdlink',
	    'wlwmanifest',
	    'indexrellink',
	    'parentpostrellink',
	    'startpostrellink',
	    'adjacentpostsrellink',
	    'wpgenerator',
	    'wpshortlink',
	    'canonical',
	    'noarchive');
    
    foreach($alloptions as $opt) {
	if(isset($array[$opt])) {
	    $array[$opt] = ($array[$opt] == 1 ? 1 : 0);
	}
    }

    return $array;
}

register_activation_hook( __FILE__, 'sisat_activate');
register_deactivation_hook( __FILE__, 'sisat_deactivate');
register_uninstall_hook(__FILE__, 'sisat_delete');

add_action('wpmu_new_blog', 'sisat_newblog', 10, 6);

function sisat_newblog($blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    global $wpdb;
    if (is_plugin_active_for_network(plugin_basename( __FILE__ ))) {
        $old_blog = $wpdb->blogid;
        switch_to_blog($blog_id);
        sisat_activate();
        switch_to_blog($old_blog);
    }
}

function sisat_activate($network_wide) {
	if ($network_wide) {
		$blog_list = get_blog_list( 0, 'all' );
		foreach ($blog_list as $blog) {
			switch_to_blog($blog['blog_id']);
			sisat_activate_it(); 
		}
		switch_to_blog($wpdb->blogid);
	} else {
		sisat_activate_it();
	}
}

function sisat_activate_it() {
    global $wpdb;
    $options=array(
	"tab" => 0,
	"disabletrash" => 7,
        "autosave" => 60,
        "revisions" => 1,
	"netlink" => 0,
        "search" => 0,
        "noindex-404" => 1,
        "noindex-archive" => 1,
            "noindex-category" => 1,
            "noindex-tag" => 1,
            "noindex-author" => 1,
            "noindex-date" => 1,
        "noindex-search" => 1,
        "noindex-attachment" => 1,
        "noindex-posts" => 0,
        "noindex-pages" => 0,
        "title" => 1,
	"blogtitle" => 1,
        "feedlink" => 0,
        "feedlinkextra" => 1,
        "rsdlink" => 1,
        "wlwmanifest" => 1,
        "indexrellink" => 1,
        "parentpostrellink" => 1,
        "startpostrellink" => 1,
        "adjacentpostsrellink" => 1,
        "wpgenerator" => 1,
        "wpshortlink" => 1,
        "canonical" => 0,
	"robots" => "",
	"noarchive" => 0
    );
	add_option("sisat_settings", $options);
	add_option("sisat_version", SISAT_VERSION);
}

function sisat_deactivate($network_wide) {
	if ($network_wide) {
		$blog_list = get_blog_list( 0, 'all' );
		foreach ($blog_list as $blog) {
			switch_to_blog($blog['blog_id']);
			sisat_deactivate_it(); 
		}
		switch_to_blog($wpdb->blogid);
	} else {
		sisat_deactivate_it();
	}   
}

function sisat_deactivate_it() {
	//do not delete options from database when deactiavting the plugin
	//sisat_delete_it();
}

function sisat_delete($network_wide) {
	if ($network_wide) {
		$blog_list = get_blog_list( 0, 'all' );
		foreach ($blog_list as $blog) {
			switch_to_blog($blog['blog_id']);
			sisat_delete_it(); 
		}
		switch_to_blog($wpdb->blogid);
	} else {
		sisat_delete_it();
	}   
}

function sisat_delete_it() {
    delete_option("sisat_settings");
	delete_option("sisat_version");
}


add_filter('plugin_action_links', 'sisat_plugin_settings', 10, 2 );

function sisat_plugin_settings($links,$file) {
    $basename = plugin_basename(__FILE__);
    if($file==$basename) {
        $settings_link = '<a href="admin.php?page=sisat-options">' . __('Settings') . '</a>';
	array_push( $links, $settings_link );
    }
return $links;
}

$sisat_metafields = array(
    "searchresults" => array(
        "boxname" => "sisat_includeinsearch",
        "boxvalue" => "0",
        "title" => __("Include in blog Search Results","sisat")),
    "robots" => array(
        "boxname" => "sisat_robots",
        "boxvalue" => "0",
        "title" => __("Robots Meta Tag","sisat")
    )
);

$sisat_metafields_dsc = array(
    "titletag" => array(
        "boxname" => "sisat_title",
        "boxvalue" => "",
        "title" => __("(optional) Custom Title &lt;title&gt;&lt;/title&gt;","sisat")),
    "desc" => array(
        "boxname" => "sisat_metadsc",
        "boxvalue" => "",
        "title" => __("Meta Description","sisat")),
    "kw" => array(
        "boxname" => "sisat_metakw",
        "boxvalue" => "",
        "title" => __("Meta Keywords (separated by comma)","sisat")
    )
);


add_action('admin_menu','sisat_metabox');  
add_action('save_post' ,'sisat_savepostdata');
add_action('save_post' ,'sisat_savepostdata_dsc');

function sisat_metabox() {
global $theme_name;
    if (function_exists('add_meta_box')) {
	if(current_user_can('administrator') || current_user_can('editor')) {
	    add_meta_box( 'sisat-newmeta', 'WP-AdminTools', 'sisat_newmeta', 'post', 'side', 'high' );
	    add_meta_box( 'sisat-newmeta', 'WP-AdminTools', 'sisat_newmeta', 'page', 'side', 'high' );
	    add_meta_box( 'sisat-newmeta-dsc', 'WP-AdminTools: Title & Meta Description & Keywords', 'sisat_newmeta_dsc', 'post', 'normal', 'high' );
	    add_meta_box( 'sisat-newmeta-dsc', 'WP-AdminTools: Title & Meta Description & Keywords', 'sisat_newmeta_dsc', 'page', 'normal', 'high' );
	}
    }
}

function sisat_newmeta_dsc() {
global $post, $sisat_metafields_dsc;
    foreach($sisat_metafields_dsc as $meta_box) {
        echo "<p>";
	$meta_box_value = get_post_meta($post->ID, $meta_box['boxname'], true);
        echo "<input type=\"hidden\" name=\"".$meta_box['boxname']."_noncename\" id=\"".$meta_box['boxname']."_noncename\" value=\"".wp_create_nonce( plugin_basename(__FILE__) )."\" />";
	echo '<label for="myplugin_new_field">';
        _e($meta_box['title'], 'sisat');
        echo ':</label><br />';
	if($meta_box['boxname']=='sisat_metadsc' || $meta_box['boxname']=='sisat_title') {
	    echo "<input onkeydown=\"sisat_countdesc('".$meta_box['boxname']."')\" onkeyup=\"sisat_countdesc('".$meta_box['boxname']."')\" type=\"text\" maxlength=255 style=\"width:80%\" id=\"".$meta_box['boxname']."\" name=\"".$meta_box['boxname']."\" value=\"".$meta_box_value."\" />";
	    echo " <input disabled type=\"text\" maxlength=3 size=3 id=\"".$meta_box['boxname']."-ctr\" value=\"".strlen(trim($meta_box_value))."\" />";
	    if($meta_box['boxname']=='sisat_metadsc') {
		echo "<br /><i>".__('optimal: ~155 chars','sisat')."</i>";
	    } else if($meta_box['boxname']=='sisat_title') {
		echo "<br /><i>".__('optimal: max. 70 chars','sisat')."</i>";
	    }
	} else {
	    echo "<input type=\"text\" maxlength=255 style=\"width:80%\" id=\"".$meta_box['boxname']."\" name=\"".$meta_box['boxname']."\" value=\"".$meta_box_value."\" />";
	}
	echo "</p>";
    }
}

function sisat_newmeta() {
global $post, $sisat_metafields;
    $sisat_includeinsearch = get_post_meta($post->ID, 'sisat_includeinsearch', true);
    $staticpage = get_option('show_on_front');
    if($staticpage=='page') {
        $pageforposts=get_option('page_for_posts');
    }
    if($pageforposts && $post->ID == $pageforposts) {
        echo "<p><strong>";
        _e('This page is set as your <a href="options-reading.php">Posts page</a>.<br />Changing settings has no effect!','sisat');
        echo "</strong></p>";
    }
    foreach($sisat_metafields as $meta_box) {
        echo "<p>";
	$meta_box_value = get_post_meta($post->ID, $meta_box['boxname'], true);
	if($meta_box_value == "" || !is_numeric($meta_box_value))  { $meta_box_value = $meta_box['boxvalue']; }
        
        echo "<input type=\"hidden\" name=\"".$meta_box['boxname']."_noncename\" id=\"".$meta_box['boxname']."_noncename\" value=\"".wp_create_nonce( plugin_basename(__FILE__) )."\" />";
	echo '<label for="myplugin_new_field">';
        _e($meta_box['title'], 'sisat');
        echo ':</label><br />';
        if($meta_box['boxname']=='sisat_includeinsearch') {
            echo "<select name='".$meta_box['boxname']."' id='".$meta_box['boxname']."'>";
            echo "<option ";
            if($meta_box_value==0) { echo "selected='selected' "; }
            echo "value='0'>".__('use global settings', 'sisat')."</option>";
            echo "<option ";
            if($meta_box_value==1) { echo "selected='selected' "; }
            echo "value='1'>".__('yes', 'sisat')."</option>";
            echo "<option ";
            if($meta_box_value==2) { echo "selected='selected' "; }
            echo "value='2'>".__('no', 'sisat')."</option>";
            echo "</select>";
        } else if($meta_box['boxname']=='sisat_robots') {
                echo "<select name='".$meta_box['boxname']."' id='".$meta_box['boxname']."'>";
                echo "<option ";
                if($meta_box_value==0) { echo "selected='selected' "; }
                echo "value='0'>".__('use global settings', 'sisat')."</option>";
                echo "<option ";
                if($meta_box_value==1) { echo "selected='selected' "; }
                echo "value='1'>noindex</option>";
                echo "<option ";
                if($meta_box_value==2) { echo "selected='selected' "; }
                echo "value='2'>noindex, nofollow</option>";
                echo "<option ";
                if($meta_box_value==3) { echo "selected='selected' "; }
                echo "value='3'>noindex, follow</option>";
                echo "<option ";
                if($meta_box_value==4) { echo "selected='selected' "; }
                echo "value='4'>index</option>";
                echo "<option ";
                if($meta_box_value==5) { echo "selected='selected' "; }
                echo "value='5'>index, follow</option>";
                echo "<option ";
                if($meta_box_value==6) { echo "selected='selected' "; }
                echo "value='6'>index, nofollow</option>";
                echo "</select>";
        } else {
            echo "<input type=\"text\" id=\"".$meta_box['boxname']."\" name=\"".$meta_box['boxname']."\" value=\"".$meta_box_value."\" />";
        }
        echo "</p>";
    }
}

function sisat_savepostdata($post_id) {
global $post, $sisat_metafields;
    foreach($sisat_metafields as $meta_box) {
	if (!wp_verify_nonce($_POST[$meta_box['boxname'].'_noncename'],plugin_basename(__FILE__))) {
	    return $post_id;
	}
	if ('page'==$_POST['post_type']) {
	    if (!current_user_can('edit_page',$post_id)) {
		return $post_id;
	    }
	} else {
	    if (!current_user_can('edit_post', $post_id)) {
		return $post_id;
	    }
	}
	$data = $_POST[$meta_box['boxname']];
	if(!is_numeric($data)) { $data=0; }
	if(get_post_meta($post_id, $meta_box['boxname']) == "") {
            add_post_meta($post_id, $meta_box['boxname'], $data, true);
        } else if ($data != get_post_meta($post_id, $meta_box['boxname'], true)) {
            update_post_meta($post_id, $meta_box['boxname'], $data);
        } else if($data == "") {
            delete_post_meta($post_id, $meta_box['boxname'], get_post_meta($post_id, $meta_box['boxname'], true));
        }
    }
}

function sisat_savepostdata_dsc($post_id) {
global $post, $sisat_metafields_dsc;
    
    foreach($sisat_metafields_dsc as $meta_box) {
	if (!wp_verify_nonce($_POST[$meta_box['boxname'].'_noncename'],plugin_basename(__FILE__))) {
	    return $post_id;
	}
	if ('page'==$_POST['post_type']) {
	    if (!current_user_can('edit_page',$post_id)) {
		return $post_id;
	    }
	} else {
	    if (!current_user_can('edit_post', $post_id)) {
		return $post_id;
	    }
	}
	$data = trim(esc_attr($_POST[$meta_box['boxname']]));
	if(get_post_meta($post_id, $meta_box['boxname']) == "") {
            add_post_meta($post_id, $meta_box['boxname'], $data, true);
        } else if ($data != get_post_meta($post_id, $meta_box['boxname'], true)) {
            update_post_meta($post_id, $meta_box['boxname'], $data);
        } else if($data == "") {
            delete_post_meta($post_id, $meta_box['boxname'], get_post_meta($post_id, $meta_box['boxname'], true));
        }
    }
}

add_action('admin_menu', 'sisat_menu');
add_action('admin_init', 'sisat_load_admin_custom_script');

function sisat_load_admin_custom_script() {
    if (basename($_SERVER['PHP_SELF']) == "post-new.php" || (basename($_SERVER['PHP_SELF']) == "post.php" && $_GET['action'] == "edit")) {
	$url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
	wp_enqueue_script('sisatjavascript',$url.'js/wp-admintools.js');     
    }
}

function sisat_menu() {
    $sisatmen = add_submenu_page ('options-general.php', 'WP-AdminTools', 'WP-AdminTools', 'administrator', 'sisat-options', 'sisat_options');
    add_action( "admin_print_scripts-$sisatmen", 'sisat_loader' );
}

function sisat_loader() {
    $url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
    wp_enqueue_script('sisatjavascript',$url.'js/wp-admintools.js');
    wp_enqueue_script('sisatjavascripttabs',$url.'js/tabber.js');
    echo "<link rel='stylesheet' href='".$url."css/wp-admintools.css' type='text/css' />\n";
}

function sisat_options() {
    if (!current_user_can('manage_options'))  { wp_die( __('You do not have sufficient permissions to access this page.') ); }
    $options = get_option('sisat_settings');
    
    $activetab = $options['tab'];
    $trash_values = array(
                array( 'val'  => 0, 'name' => __('disable trash feature','sisat')),
                array( 'val'  => 1, 'name' => __('1 day','sisat')),
                array( 'val'  => 3, 'name' => __('3 days','sisat')),
                array( 'val'  => 7, 'name' => __('1 week','sisat')),
                array( 'val'  => 30, 'name' => __('1 month','sisat'))
    );
    $autosave_values = array(
                array( 'val'  => 1000000, 'name' => __('do not autosave','sisat')),
                array( 'val'  => 60, 'name' => __('1 min','sisat')),
                array( 'val'  => 600, 'name' => __('10 min','sisat')),
                array( 'val'  => 1800, 'name' => __('30 min','sisat')),
                array( 'val'  => 3600, 'name' => __('1 hour','sisat'))
    );
    $showsisattab=false;
    if(isset($_POST['sisat-revision']) && $_POST['sisat-revision']=="clean") {
        check_admin_referer('sisat-revision');
        sisat_cleardb('revision');
        echo "<div id=\"message\" class=\"updated\">";
        echo "<p><strong>".__('All Revisions deleted','sisat')."</strong></p>";
        echo "</div>";
	update_option( $options['tab'], '3' );
	$activetab = 3;
	$showsisattab=true;
    } else if(isset($_POST['sisat-autodraft']) && $_POST['sisat-autodraft']=="clean") {
        check_admin_referer('sisat-autodraft');
        sisat_cleardb('autodraft');
        echo "<div id=\"message\" class=\"updated\">";
        echo "<p><strong>".__('All Autodrafts deleted','sisat')."</strong></p>";
        echo "</div>";
	update_option( $options['tab'], '3' );
	$activetab = 3;
	$showsisattab=true;
    } else if(isset($_POST['sisat-trash']) && $_POST['sisat-trash']=="clean") {
        check_admin_referer('sisat-trash');
        sisat_cleardb('trash');
        echo "<div id=\"message\" class=\"updated\">";
        echo "<p><strong>".__('Trash is now empty','sisat')."</strong></p>";
        echo "</div>";
	update_option( $options['tab'], '3' );
	$activetab = 3;
	$showsisattab=true;
    } else if(isset($_POST['sisat-opencomments']) && $_POST['sisat-opencomments']=="clean") {
        check_admin_referer('sisat-opencomments');
        sisat_cleardb('opencomments');
        echo "<div id=\"message\" class=\"updated\">";
        echo "<p><strong>".__('All pending comments were deleted','sisat')."</strong></p>";
        echo "</div>";
	update_option( $options['tab'], '3' );
	$activetab = 3;
	$showsisattab=true;
    } else if(isset($_POST['sisat-spamcomments']) && $_POST['sisat-spamcomments']=="clean") {
        check_admin_referer('sisat-spamcomments');
        sisat_cleardb('spamcomments');
        echo "<div id=\"message\" class=\"updated\">";
        echo "<p><strong>".__('All spam comments were deleted','sisat')."</strong></p>";
        echo "</div>";
	update_option( $options['tab'], '3' );
	$activetab = 3;
	$showsisattab=true;
    } else if ($_GET['updated']==true) {
        echo "<div id=\"message\" class=\"updated\">";
        echo "<p><strong>".__('Settings Saved','sisat').".</strong></p>";
        echo "</div>";
    }  

    $tabtest = array();
    $tabtest = $_GET;
    if(isset($tabtest['page']) && $tabtest['page']=='sisat-options') {
	if(isset($tabtest['tab']) && is_numeric($tabtest['tab']) && $tabtest['tab']>0 && $tabtest['tab']<4) {
	    $activetab = $tabtest['tab'];
	    $showsisattab=true;
	}
	unset($tabtest['page']);
    }
    if(count($tabtest)<1 && !$showsisattab) {
	$activetab = 0;
    }


    echo "<div class=\"wrap\">";
    echo "<div id='icon-options-general' class='icon32'><br /></div><h2>WP-AdminTools</h2>";
    
    echo "<div class=\"tabber\" id=\"sisattabs\">";
	
	echo "<div class=\"tabbertab\">";
	echo "<form name=\"wpaublformapikeys\" method=\"post\" action=\"options.php\">";
	settings_fields('sisat_options');
	
	echo "<h2>".__('General Settings','sisat')."</h2>";
	
	    echo "<table class=\"form-table\">";
		echo "<tr>";
		echo "<th scope=\"row\"><h3>".__('General Settings','sisat')."</h3></th>";
		echo "</tr>";  
		echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Empty Trash after','sisat').":</th>";                     // Empty Trash
		echo "<td>";
		echo "<select name=\"sisat_settings[disabletrash]\">";
		foreach($trash_values as $values) {
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['disabletrash']==$values['val']) { echo "selected=\"selected\" "; }
		    echo "value=\"".$values['val']."\">".__($values['name'],'sisat')."</option>";
		}
		echo "</select>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('Select to curtail the number of days trash posts are stored for. If you disable the trash feature the trash link will display <strong><em>Delete Permanently</em></strong> instead','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('AutoSave Interval','sisat').":</th>";                     // AutoSave Interval
		echo "<td>";
		echo "<select name=\"sisat_settings[autosave]\">";
		foreach($autosave_values as $values) {
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['autosave']==$values['val']) { echo "selected=\"selected\" "; }
		    echo "value=\"".$values['val']."\">".__($values['name'],'sisat')."</option>";
		}
		echo "</select>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('Select the AutoSave Interval','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Store Revisions','sisat').":</th>";                       // Store Revisions
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[revisions]\" value=\"1\" ";
		    if($options['revisions']=="1") { echo "checked=\"checked\" "; }
		echo "/>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Uncheck</strong> this if you want to prevent Wordpress from storing revisions into your database','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('WordPress Login Logo','sisat').":</th>";                  // WP Login Logo
		echo "<td>";
		echo "<select name=\"sisat_settings[netlink]\">";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['netlink']==0) { echo "selected=\"selected\" "; }
		    echo "value=\"0\">".__('do not change anything','sisat')."</option>";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['netlink']==1) { echo "selected=\"selected\" "; }
		    echo "value=\"1\">".__('change link to the site url','sisat')."</option>";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['netlink']==2) { echo "selected=\"selected\" "; }
		    echo "value=\"2\">".__('change to empty link','sisat')."</option>";
		echo "</select>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('Change the url of the logo on the login page. Can be useful if you are using a WP Network and want to avoid footprints to your main site','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Search Results','sisat').":</th>";                  // Search Results
		echo "<td>";
		echo "<select name=\"sisat_settings[search]\">";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['search']==0) { echo "selected=\"selected\" "; }
		    echo "value=\"0\">".__('do not restrict','sisat')."</option>";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['search']==1) { echo "selected=\"selected\" "; }
		    echo "value=\"1\">".__('restrict results to posts','sisat')."</option>";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['search']==2) { echo "selected=\"selected\" "; }
		    echo "value=\"2\">".__('restrict results to pages','sisat')."</option>";
		echo "</select>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('Change these settings if you want to restrict search results to posts or pages','sisat').".<br />".__('You can override these settings for individual post or pages on the edit screen','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\"></th>";
		echo "<td>";
		echo "<p class=\"submit\"><input name=\"tab1\" type=\"submit\" class=\"button-primary\" value=\"".__('save changes','sisat')."\" /></p>";
		echo "</td>";
		echo "<td></td>";
	    echo "</tr>";
	    echo "</table>";
	echo "</div>";

	echo "<div class='tabbertab' title='".__('SEO Settings','sisat')."'>"; // tabbertabdefault
	echo "<h2>".__('SEO Settings','sisat')."</h2>";
	    echo "<table class=\"form-table\">";
	    echo "<tr>";
		echo "<th scope=\"row\"><h3>".__('SEO Settings','sisat')."</h3></th>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Title Tag','sisat').":</th>";                  // Title
		echo "<td>";
		echo "<select name=\"sisat_settings[title]\">";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['title']==0) { echo "selected=\"selected\" "; }
		    echo "value=\"0\">".__('leave defaults','sisat')."</option>";
		    echo "<option style=\"padding:1px 10px 1px 1px;text-align:left;\" ";
		    if($options['title']==1) { echo "selected=\"selected\" "; }
		    echo "value=\"1\">".__('generate seo titles','sisat')."</option>";
		echo "</select>";
		echo "<p><input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[blogtitle]\" value=\"1\" ";
		if($options['blogtitle']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Add Blogtitle to title on posts and pages','sisat')."</p>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('Let WP-AdminTools generate a SEO friendly title for every page','sisat').".<br />";
		echo __('You can override these settings for individual post or pages on the edit screen','sisat').".<br />";
		echo "</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Robots Meta <strong><em>noindex</em></strong> Tag on','sisat').":</th>";                  // Meta Robots
		echo "<td>";
		echo "<p><input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[noindex-404]\" value=\"1\" ";
		if($options['noindex-404']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('404 Pages','sisat')."</p>";
		echo "<p><input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[noindex-search]\" value=\"1\" ";
		if($options['noindex-search']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Search Results','sisat')."</p>";
		echo "<p><input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[noindex-attachment]\" value=\"1\" ";
		if($options['noindex-attachment']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Attachment Site','sisat')."</p>";
		echo "<p><input style=\"margin-right:5px;\" onclick=\"sisat_setarchive();\" id=\"noind\" type=\"checkbox\" name=\"sisat_settings[noindex-archive]\" value=\"1\" ";
		if($options['noindex-archive']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Any type of Archive Pages','sisat')."</p>";
		echo "<p><input style=\"margin-left:20px;margin-right:5px;\" onclick=\"sisat_setarchive();\" id=\"noind1\" type=\"checkbox\" name=\"sisat_settings[noindex-category]\" value=\"1\" ";
		if($options['noindex-category']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Category archive','sisat')."</p>";
		echo "<p><input style=\"margin-left:20px;margin-right:5px;\" onclick=\"sisat_setarchive();\" id=\"noind2\" type=\"checkbox\" name=\"sisat_settings[noindex-tag]\" value=\"1\" ";
		if($options['noindex-tag']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Tag archive','sisat')."</p>";
		echo "<p><input style=\"margin-left:20px;margin-right:5px;\" onclick=\"sisat_setarchive();\" id=\"noind3\" type=\"checkbox\" name=\"sisat_settings[noindex-author]\" value=\"1\" ";
		if($options['noindex-author']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Author archive','sisat')."</p>";
		echo "<p><input style=\"margin-left:20px;margin-right:5px;\" onclick=\"sisat_setarchive();\" id=\"noind4\" type=\"checkbox\" name=\"sisat_settings[noindex-date]\" value=\"1\" ";
		if($options['noindex-date']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Date-based archive','sisat')."</p>";
		echo "<p><input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[noindex-posts]\" value=\"1\" ";
		if($options['noindex-posts']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Posts','sisat')."</p>";
		echo "<p><input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[noindex-pages]\" value=\"1\" ";
		if($options['noindex-pages']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Pages','sisat')."</p>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('Tell robots not to index the content of a page. Useful to avoid dublicate content in search engines','sisat').".<br />";
		echo __('You can override these settings for individual post or pages on the edit screen','sisat').".<br />";
		echo __('<strong>Attention:</strong><br />If you use a noindex meta on pages and have a static page as front page being displayed, remeber to override the robots meta settings for this individual page on the edit screen if you want your front page to appear in search engines','sisat').".<br />";
		echo "</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Noarchive Tag','sisat').":</th>";                  // noarchive
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[noarchive]\" value=\"1\" ";
		if($options['noarchive']=="1") { echo "checked=\"checked\" "; }
		echo "/> ".__('Check if you do not want your website cached by Google','sisat');
	    echo "</td></tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Robots.txt','sisat').":</th>";                  // Robots.txt
		echo "<td>";
		echo "<textarea name=\"sisat_settings[robots]\" rows=\"5\" cols=\"60\">";
		echo $options['robots'];
		echo "</textarea>";
		echo "</td>";
		echo "<td><span class=\"description\">".__('Add additional rules to append to the robots.txt','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\"></th>";
		echo "<td>";
		echo "<p class=\"submit\"><input name=\"tab2\" type=\"submit\" class=\"button-primary\" value=\"".__('save changes','sisat')."\" /></p>";
		echo "</td>";
		echo "<td></td>";
	    echo "</tr>";
	    echo "</table>";
	echo "</div>";
	
	echo "<div class='tabbertab' title='".__('WP Head CleanUp','sisat')."'>"; // tabbertabdefault
	echo "<h2>".__('General Settings','sisat')."</h2>";
	    echo "<table class=\"form-table\">";
	    echo "<tr>";
	    echo "<th scope=\"row\"><h3>".__('WP Head CleanUp','sisat')."</h3></th>";           // WP Head
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Feed links','sisat').":</th>";                    // Feed Links               
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[feedlink]\" value=\"1\" ";
		if($options['feedlink']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the links to the general feeds: Post and Comment Feed','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Feed Extra links','sisat').":</th>";              // Feed Extra Links               
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[feedlinkextra]\" value=\"1\" ";
		if($options['feedlinkextra']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the links to the extra feeds such as category feeds','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('RSD EditURI','sisat').":</th>";              // rsdlink              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[rsdlink]\" value=\"1\" ";
		if($options['rsdlink']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the link to the to the Really Simple Discovery service endpoint, EditURI link','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Windows Live Writer','sisat').":</th>";              // wlwmanifest              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[wlwmanifest]\" value=\"1\" ";
		if($options['wlwmanifest']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the link to to the Windows Live Writer manifest file','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Index Link','sisat').":</th>";              // indexrellink              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[indexrellink]\" value=\"1\" ";
		if($options['indexrellink']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the Index Link','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Parent Post Link','sisat').":</th>";              // parentpostrellink              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[parentpostrellink]\" value=\"1\" ";
		if($options['parentpostrellink']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the Parent Post Link','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Start Post Link','sisat').":</th>";              // startpostrellink              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[startpostrellink]\" value=\"1\" ";
		if($options['startpostrellink']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the Start Post Link','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Previous - Next Links','sisat').":</th>";              // adjacentpostsrellink              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[adjacentpostsrellink]\" value=\"1\" ";
		if($options['adjacentpostsrellink']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the links for the posts adjacent to the current post','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('WP Generator','sisat').":</th>";              // wpgenerator              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[wpgenerator]\" value=\"1\" ";
		if($options['wpgenerator']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the generator that is generated, WP version','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Shortlink','sisat').":</th>";              // wpshortlink              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[wpshortlink]\" value=\"1\" ";
		if($options['wpshortlink']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the shortlink','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\">".__('Canonical','sisat').":</th>";              // canonical              
		echo "<td>";
		echo "<input style=\"margin-right:5px;\" type=\"checkbox\" name=\"sisat_settings[canonical]\" value=\"1\" ";
		if($options['canonical']=="1") { echo "checked=\"checked\" />"; }
		echo "</td>";
		echo "<td><span class=\"description\">".__('<strong>Check</strong> to remove the canonical tag (not recommended)','sisat').".</span></td>";
	    echo "</tr>";
	    echo "<tr>";
	    echo "<tr valign=\"top\">";
		echo "<th scope=\"row\"></th>";
		echo "<td>";
		echo "<p class=\"submit\"><input name=\"tab3\" type=\"submit\" class=\"button-primary\" value=\"".__('save changes','sisat')."\" /></p>";
		echo "</td>";
		echo "<td></td>";
	    echo "</tr>";
	    echo "</table>";
	echo "</div>";
	echo "</form>";
	
	echo "<div class='tabbertab' title='".__('Database CleanUp','sisat')."'>"; // tabbertabdefault
	echo "<h2>".__('Database CleanUp','sisat')."</h2>";
	    echo "<table class=\"form-table\">";
	    echo "<tr>";
	    echo "<th scope=\"row\"><h3>".__('Database CleanUp','sisat')."</h3></th>";
	    echo "</tr>";  
	    echo "<tr valign=\"top\">";
	    echo "<th scope=\"row\">".__('Revisions','sisat').":</th>";
		echo "<td>";
		echo "<form method=\"post\" action=\"options-general.php?page=sisat-options\">";
		    wp_nonce_field('sisat-revision');
		    echo "<input type=\"hidden\" name=\"sisat-revision\" value=\"clean\" />";
		    $ct = 0;
		    $ct = sisat_showdbresults('revision');
		    if($ct>0) {
			echo "<input onclick=\"return confirm('".__('Are you sure?','sisat')."')\" type=\"submit\" class=\"button-primary\" value=\"".__('Delete Revisions','sisat')." (".$ct.")\" />";
		    } else {
			echo "<input disabled type=\"submit\" class=\"button-secondary\" value=\"".__('No Revisions found in database','sisat')."\" />";
		    }
		echo "</form>";
		echo "</td>";
	    echo "</tr>"; 
	    echo "<tr valign=\"top\">";
	    echo "<th scope=\"row\">".__('AutoDrafts','sisat').":</th>";
		echo "<td>";
		echo "<form method=\"post\" action=\"options-general.php?page=sisat-options\">";
		    wp_nonce_field('sisat-autodraft');
		    echo "<input type=\"hidden\" name=\"sisat-autodraft\" value=\"clean\" />";
		    $ct = 0;
		    $ct = sisat_showdbresults('autodraft');
		    if($ct>0) {
			echo "<input onclick=\"return confirm('".__('Are you sure?','sisat')."')\" type=\"submit\" class=\"button-primary\" value=\"".__('Delete AutoDrafts','sisat')." (".$ct.")\" />";
		    } else {
			echo "<input disabled type=\"submit\" class=\"button-secondary\" value=\"".__('No AutoDrafts found in database','sisat')."\" />";
		    }
		echo "</form>";
		echo "</td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
	    echo "<th scope=\"row\">".__('Trash','sisat').":</th>";
		echo "<td>";
		echo "<form method=\"post\" action=\"options-general.php?page=sisat-options\">";
		    wp_nonce_field('sisat-trash');
		    echo "<input type=\"hidden\" name=\"sisat-trash\" value=\"clean\" />";
		    $ct = 0;
		    $ct = sisat_showdbresults('trash');
		    if($ct>0) {
			echo "<input onclick=\"return confirm('".__('Are you sure?','sisat')."')\" type=\"submit\" class=\"button-primary\" value=\"".__('Empty Trash','sisat')." (".$ct.")\" />";
		    } else {
			echo "<input disabled type=\"submit\" class=\"button-secondary\" value=\"".__('Trash is empty','sisat')."\" />";
		    }
		echo "</form>";
		echo "</td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
	    echo "<th scope=\"row\">".__('Open Comments','sisat').":</th>";
		echo "<td>";
		echo "<form method=\"post\" action=\"options-general.php?page=sisat-options\">";
		    wp_nonce_field('sisat-opencomments');
		    echo "<input type=\"hidden\" name=\"sisat-opencomments\" value=\"clean\" />";
		    $ct = 0;
		    $ct = sisat_showdbresults('opencomments');
		    if($ct>0) {
			echo "<input onclick=\"return confirm('".__('Are you sure?','sisat')."')\" type=\"submit\" class=\"button-primary\" value=\"".__('Delete all pending comments','sisat')." (".$ct.")\" />";
		    } else {
			echo "<input disabled type=\"submit\" class=\"button-secondary\" value=\"".__('No pending comments found','sisat')."\" />";
		    }
		echo "</form>";
		echo "</td>";
	    echo "</tr>";
	    echo "<tr valign=\"top\">";
	    echo "<th scope=\"row\">".__('Spam Comments','sisat').":</th>";
		echo "<td>";
		echo "<form method=\"post\" action=\"options-general.php?page=sisat-options\">";
		    wp_nonce_field('sisat-spamcomments');
		    echo "<input type=\"hidden\" name=\"sisat-spamcomments\" value=\"clean\" />";
		    $ct = 0;
		    $ct = sisat_showdbresults('spamcomments');
		    if($ct>0) {
			echo "<input onclick=\"return confirm('".__('Are you sure?','sisat')."')\" type=\"submit\" class=\"button-primary\" value=\"".__('Delete all spam comments','sisat')." (".$ct.")\" />";
		    } else {
			echo "<input disabled type=\"submit\" class=\"button-secondary\" value=\"".__('No spam comments found','sisat')."\" />";
		    }
		echo "</form>";
		echo "</td>";
	    echo "</tr>";
	    echo "</table>";
	echo "</div>";

    echo "</div>";
    echo "<script type=\"text/javascript\">";
    echo "tabberAutomatic(tabberOptions);";
    echo "document.getElementById('sisattabs').tabber.tabShow(".$activetab.");";
    echo "</script>";
echo "</div>";
}

function sisat_showdbresults($type='autodraft') {
    global $wpdb;
    $count=0;
    switch ($type) {
        case "autodraft":
            $sql = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'auto-draft'";
            $count = $wpdb->get_var($sql);
        break;
        case "revision":
            $sql = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'";
            $count = $wpdb->get_var($sql);
        break;
	case "trash":
            $sql = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'trash'";
            $count = $wpdb->get_var($sql);
        break;
	case "opencomments":
	    $sql ="SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'";
	    $count = $wpdb->get_var($sql);
	break;
	case "spamcomments":
	    $sql ="SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = 'spam'";
	    $count = $wpdb->get_var($sql);
	break;
        default:
        break;
    }
return $count;
}

function sisat_cleardb($type='autodraft') {
    global $wpdb;
    switch ($type) {
        case "autodraft":
            $sql = "DELETE FROM $wpdb->posts WHERE post_status = 'auto-draft'";
            $wpdb->query($sql);
        break;
        case "revision":
			$sql = "DELETE a,b,c FROM $wpdb->posts a
	            LEFT JOIN $wpdb->term_relationships b
				ON (a.ID = b.object_id)
				LEFT JOIN $wpdb->postmeta c
				ON (a.ID = c.post_id)
				WHERE a.post_type = 'revision'";
			$wpdb->query($sql);
        break;
	case "trash":
            $sql = "DELETE FROM $wpdb->posts WHERE post_status = 'trash'";
            $wpdb->query($sql);
        break;
	case "opencomments":
            $sql = "DELETE FROM $wpdb->comments WHERE comment_approved = '0'";
            $wpdb->query($sql);
        break;
	case "spamcomments":
            $sql = "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'";
            $wpdb->query($sql);
        break;
        default:
        break;
    }
}

add_action('wp_dashboard_setup', 'sisat_dashboard_box');

function sisat_dashboard_box() {
    global $wp_meta_boxes;
    wp_add_dashboard_widget('wpav-rules', __('Database Stats','sisat'), 'sisat_dashboard_stats');
}

function sisat_dashboard_stats() {
    $revisions = sisat_showdbresults('revision');
    $autodrafts = sisat_showdbresults('autodraft');
    $trashitems = sisat_showdbresults('trash');
    $opencomments = sisat_showdbresults('opencomments');
    $spamcomments = sisat_showdbresults('spamcomments');

    echo "<div class=\"table table_content\">";
	echo "<table class='widefat'>";
	echo "<tr>";
	echo "<td><a href='options-general.php?page=sisat-options&tab=3'><strong>".__('Revisions:','sisat')."</strong></a> ".$revisions."</td>";
	echo "<td><a href='options-general.php?page=sisat-options&tab=3'><strong>".__('Open Comments:','sisat')."</strong></a> ".$opencomments."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><a href='options-general.php?page=sisat-options&tab=3'><strong>".__('AutoDrafts:','sisat')."</strong></a> ".$autodrafts."</td>";
	echo "<td><a href='options-general.php?page=sisat-options&tab=3'><strong>".__('Spam Comments:','sisat')."</strong></a> ".$spamcomments."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td><a href='options-general.php?page=sisat-options&tab=3'><strong>".__('Trashed Items:','sisat')."</strong></a> ".$trashitems."</td>";
	echo "<td>".__('powered by','sisat')." <a href=\"http://www.seibel-internet.de/wp-admintools/\" target=\"_blank\">WP AdminTools</a></td>";
	echo "</tr>";
	echo "</table>";
    echo "</div>";
}


// ------------------------------------ here comes the fun part


add_action('plugins_loaded', 'sisat_define_vars');

function sisat_define_vars() {
    $sisat_options = get_option('sisat_settings');
    if((isset($sisat_options['revisions']) && $sisat_options['revisions']==0) || !isset($sisat_options['revisions'])) {
	define('WP_POST_REVISIONS', false);
    }
    if(isset($sisat_options['disabletrash']) && is_numeric($sisat_options['disabletrash']) && $sisat_options['disabletrash']>=0) {
	define('EMPTY_TRASH_DAYS', $sisat_options['disabletrash']);
    }
    if(isset($sisat_options['autosave']) && is_numeric($sisat_options['autosave']) && $sisat_options['autosave']>=0) {
	define('AUTOSAVE_INTERVAL', $sisat_options['autosave']);
    }
    if(isset($sisat_options['netlink']) && $sisat_options['netlink']==1) {
	add_filter( 'login_headerurl', create_function(false,"return get_bloginfo( 'siteurl' );"));
	add_filter( 'login_headertitle', create_function(false,"return get_bloginfo( 'siteurl' );"));
    } else if(isset($sisat_options['netlink']) && $sisat_options['netlink']==2) {
	add_filter( 'login_headerurl', create_function(false,"return '#';"));
	add_filter( 'login_headertitle', create_function(false,"return '';"));
    }
}

add_filter('pre_get_posts','sisat_searchresults');
function sisat_searchresults($query) {
    if ($query->is_search) {
        $options = get_option('sisat_settings');
        if(isset($options['search']) && $options['search']==1) {
            $page_id = sisat_selectmetaid('page',1);
            $post_id = sisat_selectmetaid('post','all');
            $query->set('post__in',array_merge($page_id,$post_id));
        } else if(isset($options['search']) && $options['search']==2) {
            $post_id = sisat_selectmetaid('post',1);
            if(count($post_id)<1) {
                $query->set('post_type', 'page');
            } else {
                $page_id = sisat_selectmetaid('page','all');
                $query->set('post__in',array_merge($page_id,$post_id));
            }
        } else {
            $ids = sisat_selectmetaid('all');
            if(count($ids)>0) {
                $query->set('post__not_in',$ids);
            }
        }
        
    }
return $query;
}
function sisat_selectmetaid($type='post',$value=2) {
global $wpdb;
    $data = array();
    if($value=='all') {
        if($type=='post' || $type=='page') {
            $searchquery="SELECT $wpdb->posts.ID FROM $wpdb->posts, $wpdb->postmeta
            WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
            AND $wpdb->postmeta.meta_key = 'sisat_includeinsearch' AND $wpdb->postmeta.meta_value != '2'
            AND $wpdb->posts.post_status = 'publish'
            AND $wpdb->posts.post_type = '$type'
            AND $wpdb->posts.post_date < NOW()";
            
            $searchquerytwo = "SELECT $wpdb->posts.ID FROM $wpdb->posts
            WHERE $wpdb->posts.post_type = '$type'
            AND $wpdb->posts.post_status = 'publish'
            AND $wpdb->posts.post_date < NOW()
            AND $wpdb->posts.ID NOT IN( SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = 'sisat_includeinsearch' );";
        }
    } else {
        if($type=='post' || $type=='page') {
            $searchquery="SELECT $wpdb->posts.ID FROM $wpdb->posts, $wpdb->postmeta
            WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
            AND $wpdb->postmeta.meta_key = 'sisat_includeinsearch'
            AND $wpdb->postmeta.meta_value = '$value'
            AND $wpdb->posts.post_status = 'publish'
            AND $wpdb->posts.post_type = '$type'
            AND $wpdb->posts.post_date < NOW()";
        } else {
            $searchquery="SELECT $wpdb->posts.ID FROM $wpdb->posts, $wpdb->postmeta
            WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
            AND $wpdb->postmeta.meta_key = 'sisat_includeinsearch'
            AND $wpdb->postmeta.meta_value = '$value'
            AND $wpdb->posts.post_status = 'publish'
            AND ($wpdb->posts.post_type = 'post' OR $wpdb->posts.post_type = 'page')
            AND $wpdb->posts.post_date < NOW()";
        }
    }

    $pageposts = $wpdb->get_results($searchquery, ARRAY_A);
    if(is_array($pageposts) && count($pageposts)>0) {
        foreach($pageposts as $id) {
            $data[]=$id['ID'];
        }
    }
    if($searchquerytwo) {
        $pageposts = array();
        $pageposts = $wpdb->get_results($searchquerytwo, ARRAY_A);
        if(is_array($pageposts) && count($pageposts)>0) {
            foreach($pageposts as $id) {
                $data[]=$id['ID'];
            }
        }
    }
return $data;
}

add_action('wp_head', 'sisat_seo_head');
function sisat_seo_head() {
    $handlers = sisat_ob_list_handlers();
    $options = get_option('sisat_settings');
    if (sizeof($handlers) > 0 && $handlers[sizeof($handlers)-1] == 'sisat_head_rewrite') { ob_end_flush(); }
    if (is_feed()) { return; }
    if(isset($options['noarchive']) && $options['noarchive']==1) {
	echo "\r\n<meta name=\"robots\" content=\"noarchive\" />\r\n";
    }
    if (is_single() && !is_attachment()) {
        $meta_values = get_post_custom_values('sisat_robots');
        if(is_array($meta_values) && isset($meta_values[0])) {
            if($meta_values[0]==0 && $options['noindex-posts']==1) {
                echo sisat_printrobots(1);
            } else {
                echo sisat_printrobots($meta_values[0]);
            }
        } else {
            if($options['noindex-posts']==1) {
                echo sisat_printrobots(1);
            }
        }
    } else if(is_page() && !is_attachment()) {
        $meta_values = get_post_custom_values('sisat_robots');
        if(is_array($meta_values) && isset($meta_values[0])) {
            if($meta_values[0]==0 && $options['noindex-pages']==1) {
                echo sisat_printrobots(1);
            } else {
                echo sisat_printrobots($meta_values[0]);
            }
        } else {
            if(isset($options['noindex-pages']) && $options['noindex-pages']==1) {
                echo sisat_printrobots(1);
            }
        }
    } else if($options['noindex-archive']==1 && is_archive()) {
	echo sisat_printrobots(1);
    } else if($options['noindex-404']==1 && is_404()) {
	echo sisat_printrobots(1);
    } else if($options['noindex-attachment']==1 && is_attachment()) {
	echo sisat_printrobots(1);
    } else if($options['noindex-search']==1 && is_search()) {
	echo sisat_printrobots(1);
    } else if($options['noindex-category']==1 && is_category()) {
	echo sisat_printrobots(1);
    } else if($options['noindex-tag']==1 && is_tag()) {
	echo sisat_printrobots(1);
    } else if($options['noindex-author']==1 && is_author()) {
	echo sisat_printrobots(1);
    } else if($options['noindex-date']==1 && is_date()) {
	echo sisat_printrobots(1);
    }

    if (is_single() || is_page()) {
	$meta_desc = get_post_custom_values('sisat_metadsc');
	$meta_kw = get_post_custom_values('sisat_metakw');
	if(isset($meta_desc[0]) && trim($meta_desc[0])!="") {
	    echo "<meta name=\"description\" content=\"".trim($meta_desc[0])."\" />\r\n";
	}
	if(isset($meta_kw[0]) && trim($meta_kw[0])!="") {
	    echo "<meta name=\"keywords\" content=\"".trim($meta_kw[0])."\" />\r\n";
	}
    }
    if(is_home() || is_single() || is_page() || is_search() || is_404() || is_archive() || is_attachment()) {
        echo "<!-- powered by WP-AdminTools -->\r\n";
    }
}
function sisat_printrobots($id) {
    switch ($id) {
        case '1':
            return "<meta name=\"robots\" content=\"noindex\" />\r\n";
        break;
        case '2':
            return "<meta name=\"robots\" content=\"noindex, nofollow\" />\r\n";
        break;
        case '3':
            return "<meta name=\"robots\" content=\"noindex, follow\" />\r\n";
        break;
        case '4':
            return "<meta name=\"robots\" content=\"index\" />\r\n";
        break;
        case '5':
            return "<meta name=\"robots\" content=\"index, follow\" />\r\n";
        break;
        case '6':
            return "<meta name=\"robots\" content=\"index, nofollow\" />\r\n";
        break;
        default:
        break;
    }
    return;
}
function sisat_ob_list_handlers() {
    if(function_exists('ob_list_handlers')){ $handlers = ob_list_handlers(); }
    else { $handlers = array(); }
    return $handlers;
}

add_action('init', 'sisat_seo_head_start');
function sisat_seo_head_start() {
    $handlers = sisat_ob_list_handlers();
    if(empty($handlers) || sizeof($handlers) == 1) { ob_start('sisat_head_rewrite'); } 
}
function sisat_head_rewrite($head) {
    global $paged, $page;
    
    // titletag
    $new_custom_title="";
    if(is_single() || is_page()) {
	$new_custom_title = get_post_custom_values('sisat_title');
	if(isset($new_custom_title[0]) && trim($new_custom_title[0])!="") {
	    $title = trim(wp_filter_nohtml_kses($new_custom_title[0]));
	    $out = preg_replace("/<title>.*<\/title>/ims", "<title>".$title."</title>\r\n", $head);
	    return $out;
	}
    }
    
    $options = get_option('sisat_settings');
    if($options['title']==0 || is_admin() || is_feed() || is_trackback()) { return $head; }

    $bloginfo = get_bloginfo('name','display');
    $blogdesc = get_bloginfo('description','display');
    $staticpage = get_option('show_on_front');

    if (is_home() || is_front_page()) {
	$title = $bloginfo;
	if($staticpage=="page" && is_front_page() && $blogdesc!="") {
	    if(strlen(trim($title))>0 && substr(trim($title),0,1)!="|") { $title .= " | "; }
            $title .= $blogdesc;
	} else if($staticpage=="posts" && $blogdesc!="") {
	    if(strlen(trim($title))>0 && substr(trim($title),0,1)!="|") { $title .= " | "; }
            $title .= $blogdesc;
	}
    } else if(is_single() || is_page()) {
	$title .= single_post_title( '', false );
	if($options['blogtitle']==1 && trim($bloginfo)!="") {
	    $title .= " | ".$bloginfo;
	}
    } else if (is_search()) {
	$title = get_search_query()." ".__( 'Search results' , 'sisat');
	if(strlen(trim($title))>0 && substr(trim($title),0,1)!="|") { $title .= " | "; }
	$title .= $bloginfo;
    } else if(is_category()) {
	$cat_name = single_cat_title( $prefix = '', $display = false );
	if(is_paged()) {
	    $pageno = (get_query_var('paged')) ? get_query_var('paged') : 1; 
	    $title = $cat_name." | ".$pageno." | ".$bloginfo;
	} else {
	    $title = $cat_name." | ".$bloginfo;
	}
    } else if (is_author()) {
	$title = wp_title(' | ',false,'right').$bloginfo;
    } else {
	$title = wp_title(' ',false,'right');
    }
    $out = preg_replace("/<title>.*<\/title>/ims", "<title>".trim($title)."</title>\r\n", $head);
    return $out;
}

add_action('init', 'sisat_remove_header_crap');

function sisat_remove_header_crap() {
    $options = get_option('sisat_settings');
    if(isset($options['feedlink']) && $options['feedlink']==1) { remove_action( 'wp_head', 'feed_links', 2 ); }
    if(isset($options['feedlinkextra']) && $options['feedlinkextra']==1) { remove_action( 'wp_head', 'feed_links_extra', 3 ); }
    if(isset($options['rsdlink']) && $options['rsdlink']==1) { remove_action( 'wp_head', 'rsd_link' ); }
    if(isset($options['wlwmanifest']) && $options['wlwmanifest']==1) { remove_action( 'wp_head', 'wlwmanifest_link' ); }
    if(isset($options['indexrellink']) && $options['indexrellink']==1) { remove_action( 'wp_head', 'index_rel_link' ); }
    if(isset($options['parentpostrellink']) && $options['parentpostrellink']==1) { remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); }
    if(isset($options['startpostrellink']) && $options['startpostrellink']==1) { remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); }
    if(isset($options['adjacentpostsrellink']) && $options['adjacentpostsrellink']==1) { remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 ); }
    if(isset($options['wpgenerator']) && $options['wpgenerator']==1) { remove_action( 'wp_head', 'wp_generator' ); }
    if(isset($options['wpshortlink']) && $options['wpshortlink']==1) { remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 ); }
    if(isset($options['canonical']) && $options['canonical']==1) { remove_action( 'wp_head', 'rel_canonical');}
}


add_filter('robots_txt','sisat_custom_robots');
function sisat_custom_robots($robots) {
    $options = get_option('sisat_settings');
    if($options['robots']!="") {
	$robots .= $options['robots'];
    }
    return $robots;
}

?>