<?php
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("postbit", "dealbutton_postbit");
$plugins->add_hook("showthread_start", "dealbutton_create");
$plugins->add_hook("datahandler_post_validate_post", "dealbutton_post_validate");
$plugins->add_hook("admin_config_settings_begin", "dealbutton_update");

function dealbutton_info()
{
    return array(
        "name"			=> "Dealbutton",
        "description"	=> "Dealbutton in Handelsforen",
        "website"		=> "http://jonesboard.de/",
        "author"		=> "Jones",
        "authorsite"	=> "http://jonesboard.de/",
        "version"		=> "1.5",
        "guid"			=> "",
        "compatibility"	=> "16*"
    );
}

function dealbutton_install()
{
    global $db;
    $group = array(
        "name" => "Dealbutton",
        "title" => "Dealbutton",
        "description" => "Settings for the \"Dealbutton\" Plugin.",
        "disporder" => "1",
        "isdefault" => "0",
        );
    $db->insert_query("settinggroups", $group);
    $gid = $db->insert_id();

    $setting = array(
        "name" => "dealbutton_activate_fid",
        "title" => "Foren in denen das Plugin aktiviert sein soll",
        "optionscode" => "text",
        "value" => "0",
        "disporder" => "1",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);
    
    $setting = array(
        "name" => "dealbutton_fid",
        "title" => "Forum in dem die Themen gepostet werden sollen",
        "optionscode" => "text",
        "value" => "0",
        "disporder" => "2",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);
    
    $setting = array(
        "name" => "dealbutton_text_subject",
        "title" => "Titel des Themas",
        "description" => "{creator} => Name des Themenerstellers<br />{answer} => Name des Antworters<br />{subject} => Posttitel",
        "optionscode" => "text",
        "value" => "{creator} -> {answer}-RE: {subject}",
        "disporder" => "3",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);

    $setting = array(
        "name" => "dealbutton_text_creator",
        "title" => "Text welcher fuer den Themenersteller des neuen Themas benutzt werden soll",
        "description" => "{link_plain} => Einfacher Link zum Thema<br />{link_subject} => Titel des Posts<br />{link} => Link mit Titel",
        "optionscode" => "textarea",
        "value" => "Themenersteller",
        "disporder" => "4",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);

    $setting = array(
        "name" => "dealbutton_text_answer",
        "title" => "Text welcher fuer die Antwort des neuen Themas benutzt werden soll",
        "optionscode" => "textarea",
        "value" => "Antworter",
        "disporder" => "5",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);

    $setting = array(
        "name" => "dealbutton_disable_flooding",
        "title" => "Soll das sogenannte Post Flooding deaktiviert werden?",
        "description" => "Durch Post Flooding wird verhindert das ein User mehrfach hintereinander postet",
        "optionscode" => "yesno",
        "value" => "no",
        "disporder" => "6",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);

    $setting = array(
        "name" => "dealbutton_disable_double",
        "title" => "Soll es möglich sein den Button mehr als einmal zu drücken?",
        "description" => "",
        "optionscode" => "yesno",
        "value" => "no",
        "disporder" => "7",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);
    rebuild_settings();

    $template="
<a href=\"showthread.php?tid={\$post[\'tid\']}&pid={\$post[\'pid\']}&uid={\$uid}&auid={\$post[\'uid\']}&action=dealbutton\"><img src=\"{\$theme[\'imglangdir\']}/postbit_dealbutton.gif\" alt=\"dealbutton\" title=\"dealbutton\" /></a>";
    $templatearray = array(
            "title" => "postbit_dealbutton",
            "template" => $template,
            "sid" => "-2",
            );
    $db->insert_query("templates", $templatearray);
    
   	$db->add_column("threads", "was_dealt", "boolean DEFAULT '0'");
}

function dealbutton_is_installed()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='dealbutton'");
    if($db->num_rows($query))
         return true;
    return false;
}

function dealbutton_uninstall()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='dealbutton'");
    $g = $db->fetch_array($query);
    $db->delete_query("settinggroups", "gid='".$g['gid']."'");
    $db->delete_query("settings", "gid='".$g['gid']."'");
    rebuild_settings();
    $db->delete_query("templates", "title='postbit_dealbutton'");
    $db->drop_column("threads", "was_dealt");
}

function dealbutton_update()
{
	global $mybb, $db;
	if($mybb->input['action'] != "dbupdate")
	    return;

	if(!$db->field_exists("was_dealt", "threads")) {
		//Update vor 1.5
	   	$db->add_column("threads", "was_dealt", "boolean DEFAULT '0'");
		
	    $query = $db->simple_select("settinggroups", "gid", "name='dealbutton'");
	    $gid = $db->fetch_field($query, "gid");
	    $setting = array(
	        "name" => "dealbutton_disable_double",
	        "title" => "Soll es möglich sein den Button mehr als einmal zu drücken?",
	        "description" => "",
	        "optionscode" => "yesno",
	        "value" => "no",
	        "disporder" => "7",
	        "gid" => (int)$gid,
	        );
	    $db->insert_query("settings", $setting);
	    rebuild_settings();
	    echo "Erfolgreich auf Version 1.5 geupdatet<br />";
	}
	
	echo "Updates komplett abgeschlossen";
	exit();
}

function dealbutton_activate()
{
    require MYBB_ROOT."inc/adminfunctions_templates.php";
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'button_edit\']}')."#i", '{$post[\'button_dealbutton\']}{$post[\'button_edit\']}');
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'button_edit\']}')."#i", '{$post[\'button_dealbutton\']}{$post[\'button_edit\']}');
}

function dealbutton_deactivate()
{
    require MYBB_ROOT."inc/adminfunctions_templates.php";
    find_replace_templatesets("postbit", "#".preg_quote('{$post[\'button_dealbutton\']}')."#i", "", 0);
    find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'button_dealbutton\']}')."#i", "", 0);
}

function dealbutton_postbit($post)
{
//    echo "<pre>";var_dump($post);echo "</pre>";
    global $templates, $theme, $mybb, $db;
    $fids = explode(',', $mybb->settings['dealbutton_activate_fid']);
    if(in_array($post['fid'], $fids) || (sizeOf($fids) == 1 && $fids[0] == 0)) {
        if($post['replyto'] != 0) {
            $query = $db->simple_select("threads", "uid, was_dealt", "firstpost='{$post['replyto']}'");
            $ar = $db->fetch_array($query);
           	$uid = $ar['uid'];
            if($ar['was_dealt'] && $mybb->settings['dealbutton_disable_double'] != 1)
                return;
            if($uid == $mybb->user['uid'] && $uid != $post['uid'])
                eval("\$post['button_dealbutton'] = \"".$templates->get("postbit_dealbutton")."\";");
        }
    }
    return $post;
}

function dealbutton_create()
{
    global $db, $mybb, $lang;
    $lang->load("dealbutton");
    if($mybb->input['action'] == "dealbutton") {
		$link_plain = $mybb->settings['bburl']."/".get_post_link($mybb->input['pid'],$mybb->input['tid'])."#pid{$mybb->input['pid']}";

   	    if(!$mybb->input['uid'])
            redirect($link_plain, $lang->db_no_uid);

        if(!$mybb->settings['dealbutton_fid'] || $mybb->settings['dealbutton_fid']==0)
            redirect($link_plain, $lang->db_no_fid);

		$dealt_with = $db->fetch_field($db->simple_select("threads", "was_dealt", "tid='".(int)$mybb->input['tid']."'"), "was_dealt");
		if($dealt_with && $mybb->settings['dealbutton_disable_double'] != 1)
		    redirect($link_plain, $lang->db_dealt_with);

        $name = $db->fetch_field($db->simple_select("users", "username", "uid='".(int)$mybb->input['uid']."'"), "username");
        $aname = $db->fetch_field($db->simple_select("users", "username", "uid='".(int)$mybb->input['auid']."'"), "username");
        $osubject = $db->fetch_field($db->simple_select("posts", "subject", "pid='".(int)$mybb->input['pid']."'"), "subject");
        $subject = str_replace('{creator}', $name, $mybb->settings['dealbutton_text_subject']);
        $subject = str_replace('{answer}', $aname, $subject);
        $subject = str_replace('{subject}', $osubject, $subject);
        
        $old_link = $link_plain;
        $link_titel = $osubject;
        $link = "[url={$link_plain}]{$lang->db_link}[/url]";
        $link_plain = "[url]{$link_plain}[/url]";
        $text = str_replace('{link_plain}', $link_plain, $mybb->settings['dealbutton_text_creator']);
        $text = str_replace('{link_subject}', $link_titel, $text);
        $text = str_replace('{link}', $link, $text);

		// Set up posthandler.
        require_once  MYBB_ROOT."inc/datahandlers/post.php";
        $posthandler = new PostDataHandler("insert");
        $posthandler->action = "thread";
  
        // Set the thread data that came from the input to the $thread array.
        $new_thread = array(
        	"fid" => $mybb->settings['dealbutton_fid'],
            "subject" => $subject,
            "prefix" => "",
            "icon" => "",
            "uid" => $mybb->input['uid'],
            "username" => $name,
            "message" => $text,
            "ipaddress" => get_ip(),
            "is_dealbutton" => true
        );         
        $posthandler->set_data($new_thread);
        $valid_thread = $posthandler->validate_thread();
		// Fetch friendly error messages if this is an invalid thread
		if(!$valid_thread)
		{
	        echo $lang->sprintf($lang->db_thread_error, inline_error($posthandler->get_friendly_errors()));
		}

        $info = $posthandler->insert_thread();
        $tid = $info['tid'];
        $pid = $info['pid'];

        $answer = new PostDataHandler("insert");
        // Set the post data that came from the input to the $post array.
        $post = array(
        	"tid" => $tid,
            "replyto" => $pid,
            "fid" => $mybb->settings['dealbutton_fid'],
            "subject" => $subject,
            "icon" => "",
            "uid" => $mybb->input['auid'],
            "username" => $aname,
            "message" => $mybb->settings['dealbutton_text_answer'],
            "is_dealbutton" => true
    	);
        $answer->set_data($post);
        $valid_thread = $answer->validate_post();
		// Fetch friendly error messages if this is an invalid thread
		if(!$valid_thread)
		{
	        echo $lang->sprintf($lang->db_post_error, inline_error($answer->get_friendly_errors()));
		}
        $answer->insert_post();
        $link_plain = get_post_link($pid,$tid)."#pid{$pid}";
        
        $db->update_query("threads", array("was_dealt" => 1), "tid='".(int)$mybb->input['tid']."'");
        
        redirect($link_plain, $lang->db_submit);
    }
}

function dealbutton_post_validate($handler)
{
	global $mybb;
	if(!$mybb->settings['dealbutton_disable_flooding'])
	    return true;
	$errors = &$handler->errors;
	$post = &$handler->data;
	if(!array_key_exists("is_dealbutton", $post) || !$post["is_dealbutton"])
	    return true;
	if(array_key_exists("post_flooding", $errors))
	    unset($errors['post_flooding']);
}
?>