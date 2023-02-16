<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// HOOKS
$plugins->add_hook("misc_start", "residences_misc");
$plugins->add_hook('global_intermediate', 'residences_global');
$plugins->add_hook('modcp_nav', 'residences_modcp_nav');
$plugins->add_hook("modcp_start", "residences_modcp");
$plugins->add_hook("member_profile_end", "residences_memberprofile");

// MyAlerts
if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
$plugins->add_hook("global_start", "residences_myalert_alerts");
}
 
// Die Informationen, die im Pluginmanager angezeigt werden
function residences_info(){
	return array(
		"name"			=> "Wohnübersicht",
		"description"	=> "Dieses Plugin erweitert das Board um eine interaktive Liste über die Wohnorte der Charaktere. Ausgewählte Gruppen können Straßen und Wohnorte hinzufügen, welche vom Team freigeschaltet werden müssen.",
		"website"		=> "https://github.com/little-evil-genius/interaktive-Wohnuebersicht",
		"author"		=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"		=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function residences_install(){
    
    global $db, $cache, $mybb;

    // Datenbank-Tabelle erstellen

    // STRASSEN - HIER WERDEN DIE INFOS ZU DEN STRASSEN GESPEICHERT
	$db->query("CREATE TABLE ".TABLE_PREFIX."residences_streets(
        `sid` int(10) NOT NULL AUTO_INCREMENT,
		`streetname` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
        `rate` int(1) NOT NULL,
		`description` VARCHAR(2000) COLLATE utf8_general_ci NOT NULL,
        `accepted` int(1) NOT NULL,
        `sendedby` int(11) NOT NULL,
        PRIMARY KEY(`sid`),
        KEY `sid` (`sid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1"
    );

    // WOHNORTE - HIER WERDEN DIE INFOS ZU DEN WOHNORTEN GESPEICHERT
	$db->query("CREATE TABLE ".TABLE_PREFIX."residences_home(
        `hid` int(10) NOT NULL AUTO_INCREMENT,
        `sid` int(10) NOT NULL,
		`street` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
		`number` VARCHAR(250) COLLATE utf8_general_ci NOT NULL,
        `type` VARCHAR(500) COLLATE utf8_general_ci NOT NULL,
		`personcount` VARCHAR(20) COLLATE utf8_general_ci NOT NULL,
        `accepted` int(1) NOT NULL,
        `sendedby` int(11) NOT NULL,
        PRIMARY KEY(`hid`),
        KEY `hid` (`hid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1"
    );

    // USER - HIER WERDEN DIE INFOS ZU DEN USERN GESPEICHERT
	$db->query("CREATE TABLE ".TABLE_PREFIX."residences_user(
        `uhid` int(11) NOT NULL AUTO_INCREMENT,
        `sid` int(11) NOT NULL,
        `hid` int(11) NOT NULL,
        `uid` int(11) NOT NULL,
        PRIMARY KEY(`uhid`),
        KEY `uhid` (`uhid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1"
    );

    // EINSTELLUNGEN HINZUFÜGEN
    $setting_group = array(
        'name'          => 'residences',
        'title'         => 'Wohnübersicht',
        'description'   => 'Einstellungen für die Wohnübersicht',
        'disporder'     => 1,
        'isdefault'     => 0
    );
        
    $gid = $db->insert_query("settinggroups", $setting_group); 
        
    $setting_array = array(
        'residences_streets_allow_groups' => array(
            'title' => 'Erlaubte Gruppen für die Straßen',
            'description' => 'Welche Gruppen dürfen Straßen hinzufügen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 1
        ),

        'residences_home_allow_groups' => array(
            'title' => 'Erlaubte Gruppen für die Wohnorte',
            'description' => 'Welche Gruppen dürfen Wohnorte hinzufügen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 2
        ),

        'residences_streets_user_delete' => array(
            'title' => 'Straßen löschen User',
            'description' => 'Dürfen User selbsterstellte Straßen selbstständig löschen? <b>Vorsicht!</b> Dabei werden alle Wohnorte von der dieser Straße und User innerhalb der Wohnorte aus der DB gelöscht.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 3
        ),

        'residences_home_user_delete' => array(
            'title' => 'Wohnorte löschen User',
            'description' => 'Dürfen User selbsterstellte Wohnorte selbstständig löschen? <b>Vorsicht!</b> Dabei werden alle User innerhalb dieses Wohnorts aus der DB gelöscht.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 4
        ),
    );
        
    foreach($setting_array as $name => $setting)
    {
        $setting['name'] = $name;
        $setting['gid']  = $gid;
        $db->insert_query('settings', $setting);
    }
    rebuild_settings();
    
    // Templategruppe erstellen
    $templategroup = array(
        'prefix' => 'residences',
        'title' => $db->escape_string('Wohnübersicht'),    
    );

    $db->insert_query("templategroups", $templategroup);

	$insert_array = array(
		'title'        => 'residences',
		'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} - {$lang->residences}</title>
            {$headerinclude}
        </head>
        <body>
            {$header}
            <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                <tr>
                    <td class="thead">
                        <strong>{$lang->residences}</strong>
                    </td>
                </tr>
                <tr>
                    <td class="trow1" align="center">
                        {$streets_add}
                        {$home_add}
                    </td>
                </tr>
                <tr>
                    <td class="thead" colspan="2">
                        Stadtname
                    </td>
                </tr>
                <tr>
                    <td>
                        {$residences_streets}
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_home',
		'template'    => $db->escape_string('<div class="tcat">Hausnummer {$number} » {$type} {$edit} {$delete}</div>
        <div class="trow1" align="center">{$count_person} von {$personcount} offene Plätze frei {$joinlink}</div>
        <div align="center">{$user_bit}</div>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_home_add',
		'template'    => $db->escape_string('<form id="add_home" method="post" action="misc.php?action=add_home">
        <table width="100%">
            <tbody>
                <tr>
                    <td class="thead" colspan="4">
                        <strong>{$lang->residences_home_add}</strong>
                    </td>
                </tr>
                
                <tr>
                    <td class="tcat">
                        <strong>{$lang->residences_home_add_street}</strong>
                    </td>
                    <td class="tcat">
                        <strong>{$lang->residences_home_add_number}</strong>
                    </td>
                    <td class="tcat">
                        <strong>{$lang->residences_home_add_type}</strong>
                    </td>
                    <td class="tcat">
                        <strong>{$lang->residences_home_add_personcount}</strong>
                    </td>
                </tr>
                
                <tr>
                    <td class="trow1" align="center">
                        <select name="street" id="street">
                            <option value="">{$lang->residences_home_add_street_desc}</option>              
                            {$streetname_drop}    
                        </select>
                    </td>
                    <td class="trow1" align="center">
                        <input type="text" name="number" id="number" placeholder="{$lang->residences_home_add_number_desc}" class="textbox">
                    </td>
                    <td class="trow1" align="center">	
                        <input type="text" name="type" id="type" placeholder="{$lang->residences_home_add_type_desc}" class="textbox">
                    </td>
                    <td class="trow1" align="center">	
                        <input type="text" name="personcount" id="personcount" placeholder="{$lang->residences_home_add_personcount_desc}" class="textbox">
                    </td>
                </tr>
                <tr>
                    <td class="trow1" align="center" colspan="4">	
                        <input type="hidden" name="action" value="add_home">                    
                        <input type="submit" value="{$lang->residences_home_add_send}" name="add_home" class="button">
                    </td>
                </tr>
            </tbody>
        </table>
    </form>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_home_edit',
		'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} -  {$lang->residences_home_edit}</title>
            {$headerinclude}
        </head>
        <body>
            {$header}
            <form method="post" action="misc.php?action=residences_home_edit&hid={$hid}">	
                <input type="hidden" name="hid" id="hid" value="{$hid}" class="textbox" />
                <table style="width: 100%; table-layout: fixed;">
                    <tbody>
                        <tr>
                            <td class="thead" colspan="2">{$lang->residences_home_edit}</td>
                        </tr>
                        <tr>
                            <td valign="top"> 
                                <div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->residences_home_add_street}</div>
                                <select name="street" id="street" style="width: 100%;height: 30px;margin-bottom: 10px;">
                                    <option value="{$street}">{$street}</option>              
                                    {$streetname_drop}
                                </select>
                                
                                <div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->residences_home_add_type}</div>
                                <textarea name="type" id="type" style="width: 98%; height: 25px;margin-bottom: 10px;">{$type}</textarea>
                            </td>
                            
                            <td valign="top"> 
                                <div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->residences_home_add_number}</div>
                                <textarea name="number" id="number" style="width: 98%; height: 25px;margin-bottom: 10px;">{$number}</textarea>
                                
                                <div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->residences_home_add_personcount}</div>
                                <textarea name="personcount" id="personcount" style="width: 98%; height: 25px;margin-bottom: 10px;">{$personcount}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <center>  
                                    <input type="submit" name="edit_homes" value="{$lang->residences_home_edit_send}" id="submit" class="button">
                                </center>
                            </td>
                    </tbody>
                </table>
            </form>
            {$footer}
        </body>
    </html>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_memberprofile_address',
		'template'    => $db->escape_string('{$street} {$number}'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_postbit_address',
		'template'    => $db->escape_string('{$street} {$number}'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_memberprofile_roomates',
		'template'    => $db->escape_string('{$user}<br />'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_modcp_home',
		'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} -  {$lang->residences_modcp_home}</title>
            {$headerinclude}
        </head>
        <body>
            {$header}
            <table width="100%" border="0" align="center">
                <tr>
                    {$modcp_nav}
                    <td valign="top">
                        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                            <tr>
                                <td class="thead">
                                    <strong>{$lang->residences_modcp_home}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="trow1">{$residences_modcp_home_bit}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_modcp_home_bit',
		'template'    => $db->escape_string('<table width="100%" border="0">
        <tbody>
            <tr>
                <td class="thead" colspan="2">{$street} {$number}</td>
            </tr>
            <tr>
                <td class="tcat" align="center" colspan="2">{$type} mit einer Bewohneranzahl von {$personcount}</td>
            </tr>
            <tr>
                <td align="center" colspan="2">Eingesendet von {$sendedby}</td>
            </tr>
            <tr>
                <td class="trow1" align="center" width="50%">
                    <a href="modcp.php?action=residences_home&accept={$hid}" class="button">{$lang->residences_modcp_home_bit_accept}</a>
                </td>
                
                <td class="trow1" align="center" width="50%">
                    <a href="modcp.php?action=residences_home&delete={$hid}" class="button">{$lang->residences_modcp_home_bit_delete}</a> 
                </td>
            </tr>
        </tbody>
    </table>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_modcp_nav',
		'template'    => $db->escape_string('<tr>
        <td class="trow1 smalltext">
            <a href="modcp.php?action=residences_home" class="modcp_nav_item modcp_residences_control">{$lang->residences_modcp_nav_home}</a>		
        </td>    
    </tr>                
    <tr>
        <td class="trow1 smalltext">
            <a href="modcp.php?action=residences_streets" class="modcp_nav_item modcp_residences_control">{$lang->residences_modcp_nav_street}</a>		
        </td>
    </tr>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_modcp_streets',
		'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} -  {$lang->residences_modcp_streets}</title>
            {$headerinclude}
        </head>
        <body>
            {$header}
            <table width="100%" border="0" align="center">
                <tr>
                    {$modcp_nav}
                    <td valign="top">
                        <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                            <tr>
                                <td class="thead">
                                    <strong>{$lang->residences_modcp_streets}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="trow1">{$residences_modcp_streets_bit}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_modcp_streets_bit',
		'template'    => $db->escape_string('<table width="100%" border="0">
        <tbody>
            <tr>
                <td class="thead" colspan="2">{$streetname}</td>
            </tr>
            <tr>
                <td class="tcat" align="center" colspan="2">Mit der Bewertung <b>{$modcp[\'rate\']}/5</b></td>
            </tr>
            <tr>
                <td align="center" colspan="2">Eingesendet von {$sendedby}</td>
            </tr>
            <tr>
                <td class="trow1" colspan="2" align="justify">
                    {$description}
                </td> 
            </tr>
            <tr>
                <td class="trow1" align="center" width="50%">
                    <a href="modcp.php?action=residences_streets&accept={$sid}" class="button">{$lang->residences_modcp_streets_bit_accept}</a>
                </td>
                
                <td class="trow1" align="center" width="50%">
                    <a href="modcp.php?action=residences_streets&delete={$sid}" class="button">{$lang->residences_modcp_streets_bit_delete}</a> 
                </td>
            </tr>
        </tbody>
    </table>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_streets',
		'template'    => $db->escape_string('<tr>
        <td colspan="2">
            <div class="thead">{$streetname} {$edit} {$delete}</div>
            <div class="trow1" align="center">{$rate}</div>
            <div class="trow1">{$description}</div>
        </td>
    </tr>
    
    <tr>
        <td>
            {$residence_home}
        </td>    
    </tr>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_streets_add',
		'template'    => $db->escape_string('<form id="add_streets" method="post" action="misc.php?action=add_streets">
        <table width="100%">
            <tbody>
                <tr>
                    <td class="thead" colspan="4">
                        <strong>{$lang->residences_streets_add}</strong>
                    </td>
                </tr>
                
                <tr>
                    <td class="tcat">
                        <strong>{$lang->residences_streets_add_name}</strong>
                    </td>
                    <td class="tcat">
                        <strong>{$lang->residences_streets_add_rate}</strong>
                    </td>
                    <td class="tcat">
                        <strong>{$lang->residences_streets_add_desc}</strong>
                    </td>
                </tr>
                
                <tr>
                    <td class="trow1" align="center">
                        <input type="text" name="streetname" id="streetname" placeholder="Name der Straße" class="textbox">
                    </td>
                    <td class="trow1" align="center">
                        <select name="rate" id="rate">          
                            <option value="">Bewertung auswählen</option>              
                            <option value="0">0/5</option>
                            <option value="1">1/5</option>
                            <option value="2">2/5</option>
                            <option value="3">3/5</option>
                            <option value="4">4/5</option>
                            <option value="5">5/5</option>
                        </select>
                    </td>
                    <td class="trow1" align="center">	
                        <textarea name="description" id="description" style="width: 200px; height: 50px;"></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="trow1" align="center" colspan="4">	
                        <input type="hidden" name="action" value="add_streets">                
                        <input type="submit" value="{$lang->residences_streets_add_send}" name="add_streets" class="button">
                    </td>
                </tr>
            </tbody>
        </table>
    </form>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_streets_edit',
		'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} -  {$lang->residences_streets_edit}</title>
            {$headerinclude}
        </head>
        <body>
            {$header}
            <form method="post" action="misc.php?action=residences_street_edit&sid={$sid}">	
                <input type="hidden" name="sid" id="sid" value="{$sid}" class="textbox" />
                <table style="width: 100%; table-layout: fixed;">
                    <tbody>
                        <tr>
                            <td class="thead" colspan="2">{$lang->residences_streets_edit}</td>
                        </tr>
                        <tr>
                            <td valign="top"> <div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->residences_streets_add_name}</div>
                                <textarea name="streetname" id="streetname" style="width: 98%; height: 25px;margin-bottom: 10px;">{$streetname}</textarea>
                              
                                <div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->residences_streets_add_rate}</div>
                                <select name="rate" id="rate" style="width: 100%;height: 25px;margin-bottom: 10px;">          
                                    <option value="{$rate}">{$rate}/5</option>              
                                    <option value="0">0/5</option>
                                    <option value="1">1/5</option>
                                    <option value="2">2/5</option>
                                    <option value="3">3/5</option>
                                    <option value="4">4/5</option>
                                    <option value="5">5/5</option>
                                </select>
                            </td>
                            
                            <td valign="top"> 
                                <div class="tcat" style="margin-bottom: 5px;text-align: center;">{$lang->residences_streets_add_desc}</div>
                                <textarea name="description" id="description" style="width: 99%;height: 100px;">{$description}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td align="center"  colspan="2">
                                <input type="submit" name="edit_streets" value="{$lang->residences_streets_edit_send}" id="submit" class="button">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
            {$footer}
        </body>
    </html>'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'        => 'residences_user',
		'template'    => $db->escape_string('{$profilelink}<br />'),
		'sid'        => '-2',
		'version'    => '',
		'dateline'    => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
    
}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function residences_is_installed(){

    global $db, $cache, $mybb;
  
    if($db->table_exists("residences_streets"))  {
        return true;
    }
    
    return false;
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function residences_uninstall(){

    global $db;

    //DATENBANKEN LÖSCHEN
    // DATENBANK STRASSEN
    if($db->table_exists("residences_streets"))
    {
        $db->drop_table("residences_streets");
    }

    // DATENBANK WOHNORTE
    if($db->table_exists("residences_home"))
    {
        $db->drop_table("residences_home");
    }

    // DATENBANK USER
    if($db->table_exists("residences_user"))
    {
        $db->drop_table("residences_user");
    }
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'residences%'");
    $db->delete_query('settinggroups', "name = 'residences'");

    rebuild_settings();

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE '%residences%'");

}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function residences_activate(){

    global $db, $cache;
    
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    require MYBB_ROOT."/inc/adminfunctions_templates.php";

    // MyALERTS STUFF
    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

        // Alert annehmen - STRASSEN
		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('residences_street_accept'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        // Alert ablehnen - STRASSEN
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('residences_street_delete'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        // Alert annehmen - WOHNORT
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('residences_home_accept'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);

        // Alert abnehmen - WOHNORT
        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('residences_home_delete'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
    }

    // VARIABLEN EINFÜGEN
	find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$new_street_alert} {$new_home_alert} {$bbclosedwarning}');
    find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch} {$nav_residences}');
    find_replace_templatesets("member_profile", "#".preg_quote('{$warning_level}').'#', '{$warning_level} {$residences_address} {$residences_roommate}');
   
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function residences_deactivate(){

    global $db, $cache;

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    require MYBB_ROOT."/inc/adminfunctions_templates.php";

    // VARIABLEN ENTFERNEN
    find_replace_templatesets("header", "#".preg_quote('{$new_street_alert} {$new_home_alert}')."#i", '', 0);
    find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_residences}')."#i", '', 0);
    find_replace_templatesets("member_profile", "#".preg_quote('{$residences_address} {$residences_roommate}')."#i", '', 0);

    // STYLESHEET ENTFERNEN
	$db->delete_query("themestylesheets", "name = 'residences.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}

    // MyALERT STUFF
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('residences_street_delete');
        $alertTypeManager->deleteByCode('residences_street_accept');
        $alertTypeManager->deleteByCode('residences_home_delete');
        $alertTypeManager->deleteByCode('residences_home_accept');
	}

}

##################################
##### FUNKTIONEN - THE MAGIC #####
##################################

// TEAMHINWEIS ÜBER NEUE STRASSEN/WOHNORTE
function residences_global(){
    global $db, $cache, $mybb, $templates, $new_street_alert, $new_home_alert;

    // NEUE STRASSE
    $count_street = $db->fetch_field($db->query("SELECT COUNT(*) as street FROM ".TABLE_PREFIX."residences_streets
    WHERE accepted = 0"), 'street');

    if( $mybb->usergroup['canmodcp'] == "1" && $count_street == "1"){
        $new_street_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=residences_streets\">{$count_street} neue Straße muss freigeschaltet werden</a></div>";
    } elseif ($mybb->usergroup['canmodcp'] == "1" && $count_street > "1") {
       $new_street_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=residences_streets\">{$count_street} neue Straßen müssen freigeschaltet werden</a></div>";
    }

    // NEUER WOHNORT
    $count_home = $db->fetch_field($db->query("SELECT COUNT(*) as home FROM ".TABLE_PREFIX."residences_home
    WHERE accepted = 0"), 'home');

    if( $mybb->usergroup['canmodcp'] == "1" && $count_home == "1"){
        $new_home_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=residences_home\">{$count_home} neuer Wohnort muss freigeschaltet werden</a></div>";
    } elseif ($mybb->usergroup['canmodcp'] == "1" && $count_home > "1") {
       $new_home_alert = "<div class=\"red_alert\"><a href=\"modcp.php?action=residences_home\">{$count_home} neue Wohnorte müssen freigeschaltet werden</a></div>";
    }
}

// DIE MISC SEITEN
function residences_misc() {
    global $db, $cache, $mybb, $page, $lang, $templates, $theme, $header, $headerinclude, $footer, $streets_add, $home_add, $residences_streets;

    // SPRACHDATEI LADEN
    $lang->load('residences');
    
    // USER-ID
    $user_id = $mybb->user['uid'];

    // ACTION-BAUM BAUEN
    $mybb->input['action'] = $mybb->get_input('action');

    // WOHNÜBERSICHT
    if($mybb->input['action'] == "residences") {

        // NAVIGATION
        add_breadcrumb("Listen", "listen.php");
        add_breadcrumb($lang->residences, "misc.php?action=residences"); 
   
        // HINZUFÜGE FELDER
        // Nur den Gruppen, den es erlaubt ist, neue Straßen hinzuzufügen, ist es erlaubt, den Link zu sehen.
       
        if(is_member($mybb->settings['residences_streets_allow_groups'])) {
            eval("\$streets_add = \"".$templates->get("residences_streets_add")."\";");
        }

        // Nur den Gruppen, den es erlaubt ist, neue Wohnorte hinzuzufügen, ist es erlaubt, den Link zu sehen.
        if(is_member($mybb->settings['residences_home_allow_groups'])) {
         
            // DROPBOX STRASSEN AUS DER DB
            $street_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_streets
            WHERE accepted = 1
            ORDER BY streetname ASC      
            ");
 
            $streetname_drop = "";
            while($street_drop = $db->fetch_array($street_query)){

                $streetname = $street_drop['streetname'];

                $streetname_drop .= "<option value='{$streetname}'>{$streetname}</option>";      
            }
 
            eval("\$home_add = \"".$templates->get("residences_home_add")."\";");
        }

        $streets_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_streets
        WHERE accepted = 1
        ORDER BY streetname ASC
        ");

        while($street = $db->fetch_array($streets_query)) {

            // LEER LAUFEN LASSEN
            $sid = "";
            $streetname = "";
            $rate = "";
            $description = "";
            $sendedby = "";

            // MIT INFORMATIONEN FÜLLEN
            $sid = $street['sid'];
            $streetname = $street['streetname'];
            $description = $street['description'];
            $sendedby = $street['sendedby'];

            if($street['rate'] == 0) {
                $rate = "<i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>			
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($street['rate'] == 1) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>			
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($street['rate'] == 2) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($street['rate'] == 3) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($street['rate'] == 4) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($street['rate'] == 5) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>";
            }

            // WOHNORTE AUSLESEN
            $home_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_home
            WHERE accepted = 1
            AND street = '".$streetname."'
            ORDER BY number ASC
            ");
      
            $residence_home = "";
          
            while($home = $db->fetch_array($home_query)) {
    
                // LEER LAUFEN LASSEN
                $hid = "";
                $street = "";
                $number = "";
                $type = "";
                $personcount = "";      
                $sendedby = "";
    
                // MIT INFORMATIONEN FÜLLEN
                $hid = $home['hid'];
                $street = $home['street'];
                $number = $home['number'];
                $type = $home['type'];
                $personcount = $home['personcount'];
                $sendedby = $home['sendedby'];

                // BEWOHNER AUSZÄHLEN
                $count_person = $db->fetch_field($db->query("SELECT COUNT(*) as bewohner FROM ".TABLE_PREFIX."residences_user ru
                WHERE ru.hid = '$hid'"), 'bewohner');
        
                // USER DES WOHNORTS
                $user_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_user ru
                LEFT JOIN ".TABLE_PREFIX."users u
                ON (ru.uid = u.uid)
                WHERE ru.hid = '$hid'
                AND u.uid IN (SELECT uid FROM ".TABLE_PREFIX."users)
                ORDER BY u.username ASC
                ");
                    
                $user_bit = "";
        
                while($user = $db->fetch_array($user_query)){
                 
                    $user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
                    $profilelink = build_profile_link($user['username'], $user['uid']);
        
                    eval("\$user_bit .= \"".$templates->get("residences_user")."\";");
                }

                 
                // Zählen, ob man schon irgendwo wohnt
                $count_user = $db->fetch_field($db->query("SELECT COUNT(*) as me_residence FROM ".TABLE_PREFIX."residences_user ru
                WHERE ru.uid = '$user_id'"), 'me_residence');

                // EINZIEHEN UND AUSZIEHEN
                if(!empty($mybb->user['uid'])){

                    $check = $db->fetch_field($db->query("SELECT COUNT(*) AS checked FROM ".TABLE_PREFIX."residences_user ru
                    WHERE ru.uid = '$user_id'
                    AND ru.hid = '$hid'
                    "), "checked");

                    // Wohnt noch nicht im Wohnort && Wohnort ist noch nicht voll && Wohnt noch nicht wo anders - EINZIEHEN
                    if(!$check && $personcount > $count_person && $count_user == 0){
                        $joinlink = "<a href=\"misc.php?action=residences&join={$hid}\"><i class=\"fas fa-truck-loading\" original-title=\"Einziehen\"></i></a>";
                    } 
                    // Wohnt dort && Wohnort ist voll - AUSZIEHEN
                    elseif ($check && $personcount < $count_person){
                        $joinlink = "<a href=\"misc.php?action=residences&leave={$hid}\"><i class=\"fas fa-sign-out-alt\" original-title=\"Ausziehen\"></i></a>";
                    } 
                    // Wohnt da nicht && Wohnort ist voll - NICHTS
                    elseif (!$check && $personcount == $count_person){
                        $joinlink = "";
                    }
                    // Wohnt da nicht && wohnt schon wo anders - NICHTS
                    elseif (!$check && $count_user == 1) {
                        $joinlink = "";
                    }
                    // AUSZIEHEN
                    else{
                        $joinlink = "<a href=\"misc.php?action=residences&leave={$hid}\"><i class=\"fas fa-sign-out-alt\" original-title=\"Ausziehen\"></i></a>";
                    }
                }
                $check = "";
     
                // OPTIONEN FÜR DIE WOHNORTE
                if($mybb->usergroup['canmodcp'] == "1"){
                    $edit = "<a href=\"misc.php?action=residences_home_edit&hid={$hid}\"><i class=\"fas fa-edit\" original-title=\"Wohnort bearbeiten\"></i></a>";
                    $delete = "<a href=\"misc.php?action=residences&delete_home={$hid}\"><i class=\"fas fa-trash\" original-title=\"Wohnort löschen\"></i></a>";  
                } elseif ($user_id == $sendedby) {
                    // USER DÜRFEN LÖSCHEN
                    if($mybb->settings['residences_home_user_delete'] == 1) {
                        $edit = "<a href=\"misc.php?action=residences_home_edit&hid={$hid}\"><i class=\"fas fa-edit\" original-title=\"Wohnort bearbeiten\"></i></a>";
                        $delete = "<a href=\"misc.php?action=residences&delete_home={$hid}\"><i class=\"fas fa-trash\" original-title=\"Wohnort löschen\"></i></a>";
                    } else {
                        $edit = "<a href=\"misc.php?action=residences_home_edit&hid={$hid}\"><i class=\"fas fa-edit\" original-title=\"Wohnort bearbeiten\"></i></a>";
                        $delete = "";
                    }
                } else {
                    $edit = "";
                    $delete = "";     
                }

                eval("\$residence_home .= \"" . $templates->get ("residences_home") . "\";");
            }

            // OPTIONEN FÜR DIE STRASSE
            if($mybb->usergroup['canmodcp'] == "1"){
                $edit = "<a href=\"misc.php?action=residences_street_edit&sid={$sid}\"><i class=\"fas fa-edit\" original-title=\"Straße bearbeiten\"></i></a>";
                $delete = "<a href=\"misc.php?action=residences&delete_street={$sid}\"><i class=\"fas fa-trash\" original-title=\"Straße löschen\"></i></a>";
            } elseif ($user_id == $sendedby) {
                
                // USER DÜRFEN LÖSCHEN
                if($mybb->settings['residences_streets_user_delete'] == 1) {
                    $edit = "<a href=\"misc.php?action=residences_street_edit&sid={$sid}\"><i class=\"fas fa-edit\" original-title=\"Straße bearbeiten\"></i></a>";
                    $delete = "<a href=\"misc.php?action=residences&delete_street={$sid}\"><i class=\"fas fa-trash\" original-title=\"Straße löschen\"></i></a>";     
                } else {
                    $edit = "<a href=\"misc.php?action=residences_street_edit&sid={$sid}\"><i class=\"fas fa-edit\" original-title=\"Straße bearbeiten\"></i></a>";
                    $delete = "";
                }

            } else {
                $edit = "";
                $delete = "";
            }
           
            eval("\$residences_streets .= \"" . $templates->get ("residences_streets") . "\";"); 
        }

        // EINZIEHEN IN WOHNORT   
        $join = $mybb->input['join'];
        if($join) {
            // SID durch die hid ziehen
            $sid = $db->fetch_field($db->simple_select("residences_home", "sid", "hid = '{$join}'"), "sid");

            $new_record = array(
                "sid" => $sid,
                "hid" => $join,
                "uid" => $mybb->user['uid']
            );
            $db->insert_query("residences_user", $new_record);
            redirect("misc.php?action=residences", "{$lang->residences_join}");
        }
    
        // AUSZIEHEN AUS DEM WOHNORT
        $leave = $mybb->input['leave'];
        if($leave) {
            $uid = $mybb->user['uid'];
            $db->delete_query("residences_user", "hid = '$leave' AND uid = '$uid'");
            redirect("misc.php?action=residences", "{$lang->residences_leave}");
        }
    
        // WOHNORT LÖSCHEN
        $delete = $mybb->input['delete_home'];
        if($delete) {
            // in DB Home löschen
            $db->delete_query("residences_home", "hid = '$delete'");
            // User für diesen Wohnort löschen
            $db->delete_query("residences_user", "hid = '$delete'");
            redirect("misc.php?action=residences", "{$lang->residences_delete_home}");
        }

        // STRASSE LÖSCHEN
        $delete = $mybb->input['delete_street'];
        if($delete) {
            // in DB Streets löschen
            $db->delete_query("residences_streets", "sid = '$delete'");
            // in DB Home löschen
            $db->delete_query("residences_home", "sid = '$delete'");
            // User für diesen Wohnort löschen
            $db->delete_query("residences_user", "sid = '$delete'");
            redirect("misc.php?action=residences", "{$lang->residences_delete_street}");
        }
   
        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("residences")."\";");
        output_page($page);
        die();
    }

    // STRASSE HINZUFÜGEN  
    elseif($mybb->input['action'] == "add_streets") {
    
        if($mybb->input['streetname'] == ""){
            error("Es muss ein Straßenname eingetragen werden !");
        }
        elseif($mybb->input['description'] == ""){
            error("Es muss eine Beschreibung eingetragen werden !");
        }
        elseif($mybb->input['rate'] == ""){
            error("Es muss eine Bewertung ausgewählt werden !");
        } else{

            // Wenn das Team Einträge erstellt, dann wink doch einfach durch. Sonst bitte nochmal zum Prüfung :D         
            if($mybb->usergroup['canmodcp'] == '1'){
                $accepted = 1;
            } else {
                $accepted = 0;
            }

            $new_street = array(           
                "streetname" => $db->escape_string($mybb->get_input('streetname')),
                "rate" => $db->escape_string($mybb->get_input('rate')),
                "description" => $db->escape_string($mybb->get_input('description')),
                "sendedby" => (int)$mybb->user['uid'],
                "accepted" => $accepted
            );
            $db->insert_query("residences_streets", $new_street);

            redirect("misc.php?action=residences", "{$lang->residences_add_street}");
        }
    
    }

    // WOHNART HINZUFÜGEN
    elseif($mybb->input['action'] == "add_home") {
        
        if($mybb->input['street'] == ""){
            error("Es muss eine Straße ausgewählt werden !");
        }
        elseif($mybb->input['number'] == ""){
            error("Es muss ein Hausnummer eingetragen werden !");
        }
        elseif($mybb->input['type'] == ""){
            error("Es muss eine Wohnart eingetragen werden !");
        }
        elseif($mybb->input['personcount'] == ""){
            error("Es muss eine Personenanzahl eingetragen werden !");
        }
        else{

            // Wenn das Team Einträge erstellt, dann wink doch einfach durch. Sonst bitte nochmal zum Prüfung :D
            if($mybb->usergroup['canmodcp'] == '1'){
                $accepted = 1;
            } else {
                $accepted = 0;
            }
         
            // SID durch die Straße ziehen
            $streetname = $db->escape_string($mybb->get_input('street'));         
            $sid = $db->fetch_field($db->simple_select("residences_streets", "sid", "streetname = '{$streetname}'"), "sid");

            $new_home = array(
                "sid" => $sid,
                "street" => $db->escape_string($mybb->get_input('street')),
                "number" => $db->escape_string($mybb->get_input('number')),
                "type" => $db->escape_string($mybb->get_input('type')),
                "personcount" => $db->escape_string($mybb->get_input('personcount')),
                "sendedby" => (int)$mybb->user['uid'],
                "accepted" => $accepted
            );
            $db->insert_query("residences_home", $new_home);
            redirect("misc.php?action=residences", "{$lang->residences_add_home}");
        }
    }

    // STRASSE BEARBEITEN
    elseif($mybb->input['action'] == "residences_street_edit") {

        // NAVIGATION
        add_breadcrumb ("Listen", "listen.php");
        add_breadcrumb ($lang->residences, "misc.php?action=residences");      
        add_breadcrumb ($lang->residences_streets_edit, "misc.php?action=residences_street_edit");
      
        $sid =  $mybb->get_input('sid', MyBB::INPUT_INT);

        $edit_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_streets
        WHERE sid = '".$sid."'     
        ");
     
        $edit = $db->fetch_array($edit_query);

        // Alles leer laufen lassen
        $sid = "";
        $streetname = "";
        $rate = "";
        $description = "";

        // Füllen wir mal alles mit Informationen
        $sid = $edit['sid'];
        $streetname = $edit['streetname'];
        $rate = $edit['rate'];
        $description = $edit['description'];

        // Der neue Inhalt wird nun in die Datenbank eingefügt bzw. die alten Daten überschrieben.
        if($_POST['edit_streets']){
            
            $sid = $mybb->input['sid'];

            $edit_street = array(
                "streetname" => $db->escape_string($mybb->get_input('streetname')),
                "rate" => $db->escape_string($mybb->get_input('rate')),
                "description" => $db->escape_string($mybb->get_input('description')),
            );

            $db->update_query("residences_streets", $edit_street, "sid = '".$sid."'");
            redirect("misc.php?action=residences", "{$lang->residences_edit_street}");
        }

        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("residences_streets_edit")."\";");
        output_page($page);
        die();
    }

    // WOHNORT BEARBEITEN
    elseif($mybb->input['action'] == "residences_home_edit") {

        // NAVIGATION
        add_breadcrumb ("Listen", "listen.php");
        add_breadcrumb ($lang->residences, "misc.php?action=residences");
        add_breadcrumb ($lang->residences_home_edit, "misc.php?action=residences_home_edit");
  
        $hid =  $mybb->get_input('hid', MyBB::INPUT_INT);

        // DROPBOX STRASSEN AUS DER DB
        $street_query2 = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_streets
        WHERE accepted = 1
        ORDER BY streetname ASC
        ");

        $streetname_drop = "";
        while($street_drop2 = $db->fetch_array($street_query2)){

            $streetname = $street_drop2['streetname'];

            $streetname_drop .= "<option value='{$streetname}'>{$streetname}</option>";
        }
        
        $edit_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_home
        WHERE hid = '".$hid."'
        ");
        
        $edit = $db->fetch_array($edit_query);
  
        // Alles leer laufen lassen
        $hid = "";
        $sid = "";
        $street = "";
        $number = "";
        $type = "";
        $personcount = "";
  
        // Füllen wir mal alles mit Informationen
        $hid = $edit['hid'];
        $sid = $edit['sid'];
        $street = $edit['street'];
        $number = $edit['number'];
        $type = $edit['type'];
        $personcount = $edit['personcount'];
  
        //Der neue Inhalt wird nun in die Datenbank eingefügt bzw. die alten Daten überschrieben.
        if($_POST['edit_homes']){
         
            $hid = $mybb->input['hid'];

            // Neue SID durch die Straße ziehen
            $streetname = $db->escape_string($mybb->get_input('street'));
            $sid = $db->fetch_field($db->simple_select("residences_streets", "sid", "streetname = '{$streetname}'"), "sid");

            $edit_home = array(
              "sid" => $sid,
              "street" => $db->escape_string($mybb->get_input('street')),
              "number" => $db->escape_string($mybb->get_input('number')),
              "type" => $db->escape_string($mybb->get_input('type')),
              "personcount" => $db->escape_string($mybb->get_input('personcount')),
            );
  
            $db->update_query("residences_home", $edit_home, "hid = '".$hid."'");

            $db->query("UPDATE ".TABLE_PREFIX."residences_user
            SET sid = $sid
            WHERE hid = $hid
            ");

            redirect("misc.php?action=residences", "{$lang->residences_edit_home}");
        }
  
        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("residences_home_edit")."\";");
        output_page($page);
        die();  
    }
 
}

// MOD-CP
function residences_modcp_nav(){
    global $db, $mybb, $templates, $theme, $header, $headerinclude, $footer, $lang, $modcp_nav, $nav_residences;
    
    $lang->load('residences');

    eval("\$nav_residences = \"".$templates->get ("residences_modcp_nav")."\";");
}

function residences_modcp() {
    
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $db, $page, $modcp_nav, $residences_modcp_streets_bit;

    
    if($mybb->get_input('action') == 'residences_streets') {

        // SPRACHDATEI
        $lang->load('residences');

        // Add a breadcrumb
        add_breadcrumb($lang->nav_modcp, "modcp.php");    
        add_breadcrumb($lang->residences_modcp_streets, "modcp.php?action=residences_streets");

        // STRASSEN ABFRAGEN
        $modstreet = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_streets
        WHERE accepted = '0'
        ORDER BY streetname ASC
        ");

        // STRASSE AUSLESEN 
        while($modcp = $db->fetch_array($modstreet)) {
   
            // Alles leer laufen lassen
            $sid = "";
            $streetname = "";
            $rate = "";
            $title = "";
            $description = "";
            $accepted = "";
            $sendedby = "";
        
            // Füllen wir mal alles mit Informationen
            $sid = $modcp['sid'];
            $streetname = $modcp['streetname'];
            $description = $modcp['description'];
            $accepted = $modcp['accepted'];
   
            // User der das eingesendet hat
            $modcp['sendedby'] = htmlspecialchars_uni($modcp['sendedby']);
            $user = get_user($modcp['sendedby']);
            $user['username'] = htmlspecialchars_uni($user['username']);
            $sendedby = build_profile_link($user['username'], $modcp['sendedby']);
   
            // BEWERTUNGS-ANZEIGE
            if($modcp['rate'] == 0) {
                $rate = "<i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>			
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($modcp['rate'] == 1) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>			
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($modcp['rate'] == 2) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($modcp['rate'] == 3) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($modcp['rate'] == 4) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\"></i>";
            } elseif ($modcp['rate'] == 5) {
                $rate = "<i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>			
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>
                <i class=\"fas fa-money-bill-wave\" style=\"color:var(--street-rate);\"></i>";
            }
   
            eval("\$residences_modcp_streets_bit .= \"".$templates->get("residences_modcp_streets_bit")."\";");
        }

        // TEAM-UID
        $team_uid = $mybb->user['uid'];

        // Die Strasse wirde vom Team abgelehnt 
        $del = $mybb->input['delete'];
        if($del){
            // MyALERTS STUFF
            $query_alert = $db->simple_select("residences_streets", "*", "sid = '{$del}'");
            while ($alert_del = $db->fetch_array ($query_alert)) {
                if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                    $user = get_user($alert['sendedby']);
                    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('residences_street_delete');
                    if ($alertType != NULL && $alertType->getEnabled()) {
                        $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$alert_del['sendedby'], $alertType, (int)$del);
                        $alert->setExtraDetails([
                            'username' => $user['username'],
                            'streetname' => $alert_del['streetname']
                        ]);
                        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                    }
                }
            }
            $db->delete_query("residences_streets", "sid = '$del'");
            redirect("modcp.php?action=residences_streets", "{$lang->residences_modcp_delete_street}");    
        }
    
        // Die Straße wurde vom Team angenommen 
        if($acc = $mybb->input['accept']){
            // MyALERTS STUFF
            $query_alert = $db->simple_select("residences_streets", "*", "sid = '{$acc}'");
            while ($alert_del = $db->fetch_array ($query_alert)) {
                if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                    $user = get_user($alert['sendedby']);
                    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('residences_street_accept');
                    if ($alertType != NULL && $alertType->getEnabled()) {
                        $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$alert_del['sendedby'], $alertType, (int)$acc);
                        $alert->setExtraDetails([
                            'username' => $user['username'],
                            'streetname' => $alert_del['streetname']
                        ]);
                        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                    }
                }
            }
            $db->query("UPDATE ".TABLE_PREFIX."residences_streets SET accepted = 1 WHERE sid = '".$acc."'");
            redirect("modcp.php?action=residences_streets", "{$lang->residences_modcp_accept_street}");        
        }

        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("residences_modcp_streets")."\";");
        output_page($page);
        die();
    }

    
    if($mybb->get_input('action') == 'residences_home') {
        
        // SPRACHDATEI        
        $lang->load('residences');

        // Add a breadcrumb
        add_breadcrumb($lang->nav_modcp, "modcp.php");        
        add_breadcrumb($lang->residences_modcp_home, "modcp.php?action=residences_home");

        // WOHNORT ABFRAGEN
        $modhome = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_home
        WHERE accepted = '0'
        ORDER BY street ASC
        ");
       
        // WOHNORT AUSLESEN  
        while($modcp = $db->fetch_array($modhome)) {
   
            // Alles leer laufen lassen
            $hid = "";
            $sid = "";
            $street= "";
            $number = "";
            $type = "";
            $personcount = "";
            $accepted = "";  
            $sendedby = "";
   
            // Füllen wir mal alles mit Informationen
            $hid = $modcp['hid'];
            $sid = $modcp['sid'];
            $street = $modcp['street'];
            $number = $modcp['number'];
            $type = $modcp['type'];
            $personcount = $modcp['personcount'];  
            $accepted = $modcp['accepted'];
   
            // User der das eingesendet hat
            $modcp['sendedby'] = htmlspecialchars_uni($modcp['sendedby']);
            $user = get_user($modcp['sendedby']);
            $user['username'] = htmlspecialchars_uni($user['username']);
            $sendedby = build_profile_link($user['username'], $modcp['sendedby']);

            eval("\$residences_modcp_home_bit .= \"".$templates->get("residences_modcp_home_bit")."\";");     
        }

        // Der Wohnort wirde vom Team abgelehnt 
        $del = $mybb->input['delete'];
        if($del){
            // MyALERTS STUFF
            $query_alert = $db->simple_select("residences_home", "*", "hid = '{$del}'");
            while ($alert_del = $db->fetch_array ($query_alert)) {
                if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                    $user = get_user($alert['sendedby']);
                    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('residences_home_delete');
                    if ($alertType != NULL && $alertType->getEnabled()) {
                        $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$alert_del['sendedby'], $alertType, (int)$del);
                        $alert->setExtraDetails([
                            'username' => $user['username'],
                            'street' => $alert_del['street'],
                            'number' => $alert_del['number']
                        ]);
                        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                    }
                }
            }
            $db->delete_query("residences_home", "hid = '$del'");
            redirect("modcp.php?action=residences_home", "{$lang->residences_modcp_delete_home}");         
        }

        // Der Wohnort wurde vom Team angenommen 
        if($acc = $mybb->input['accept']){
            // MyALERTS STUFF
            $query_alert = $db->simple_select("residences_home", "*", "hid = '{$acc}'");
            while ($alert_del = $db->fetch_array ($query_alert)) {
                if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                    $user = get_user($alert['sendedby']);
                    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('residences_home_accept');
                    if ($alertType != NULL && $alertType->getEnabled()) {
                        $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$alert_del['sendedby'], $alertType, (int)$acc);
                        $alert->setExtraDetails([
                            'username' => $user['username'],
                            'street' => $alert_del['street'],
                            'number' => $alert_del['number']
                        ]);
                        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                    }
                }     
            }

            $db->query("UPDATE ".TABLE_PREFIX."residences_home SET accepted = 1 WHERE hid = '".$acc."'");
            redirect("modcp.php?action=residences_home", "{$lang->residences_modcp_accept_home}");         
        }

        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("residences_modcp_home")."\";");
        output_page($page);
        die();
    }

}

// ANZEIGE IM PORFIL
function residences_memberprofile() {

    global $db, $mybb, $lang, $templates, $theme, $memprofile, $residences_roommate, $residences_address;

	// SPRACHDATEI LADEN
    $lang->load("residences");

    $uid = $mybb->get_input('uid', MyBB::INPUT_INT);

    $profile_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_user u
    LEFT JOIN ".TABLE_PREFIX."residences_home h
    ON (u.hid = h.hid) 
    LEFT JOIN ".TABLE_PREFIX."residences_streets s
    ON (u.sid = s.sid) 
    WHERE u.uid = '".$uid."'
    ");
        
    while($prof = $db->fetch_array($profile_query)){

        // Alles leer laufen lassen
        $hid = "";
        $sid = "";
        $street= "";
        $number = "";
        $type = "";
        $personcount = "";
        $accepted = ""; 
        $sendedby = "";
   
        // Füllen wir mal alles mit Informationen
        $hid = $prof['hid'];
        $sid = $prof['sid'];
        $street = $prof['street'];
        $number = $prof['number'];
        $type = $prof['type'];
        $personcount = $prof['personcount'];
        $accepted = $prof['accepted'];

        $roommate_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_user ru
        LEFT JOIN ".TABLE_PREFIX."users u
        ON (ru.uid = u.uid)
        WHERE ru.hid = '".$hid."'
        AND NOT ru.uid = '".$uid."'
        ");

        while($room = $db->fetch_array($roommate_query)){

            // Alles leer laufen lassen   
            $uid = "";

            // Füllen wir mal alles mit Informationen
            $username = format_name($room['username'], $room['usergroup'], $room['displaygroup']);
            $user = build_profile_link($username, $room['uid']);

            eval("\$residences_roommate .= \"".$templates->get("residences_memberprofile_roomates")."\";");
        }
        eval("\$residences_address .= \"".$templates->get("residences_memberprofile_address")."\";");
    }        
}

// MyALERTS STUFF
function residences_myalert_alerts() {
	global $mybb, $lang;
	$lang->load('residences');

    // STRASSEN ANNEHMEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_StreetAcceptFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->residences_street_accept,
				$outputAlert['from_user'],
				$alertContent['username'],
	            $outputAlert['dateline'],
				$alertContent['streetname']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->residences) {
	            $this->lang->load('residences');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/misc.php?action=residences';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_StreetAcceptFormatter($mybb, $lang, 'residences_street_accept')
		);
	}


	// STRASSEN ABLEHNEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_StreetDeleteFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->residences_street_delete,
				$outputAlert['from_user'],
				$alertContent['username'],
	            $outputAlert['dateline'],
				$alertContent['streetname']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->residences) {
	            $this->lang->load('residences');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/misc.php?action=residences';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_StreetDeleteFormatter($mybb, $lang, 'residences_street_delete')
		);
	}

    // WOHNORT ANNEHMEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_HomeAcceptFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->residences_home_accept,
				$outputAlert['from_user'],
				$alertContent['username'],
	            $outputAlert['dateline'],
				$alertContent['street'],
				$alertContent['number']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->residences) {
	            $this->lang->load('residences');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/misc.php?action=residences';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_HomeAcceptFormatter($mybb, $lang, 'residences_home_accept')
		);
	}

    // WOHNORT ABLEHNEN
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_HomeDeleteFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;
			$alertContent = $alert->getExtraDetails();
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$alertContent['username']}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->residences_home_delete,
				$outputAlert['from_user'],
				$alertContent['username'],
	            $outputAlert['dateline'],
				$alertContent['street'],
				$alertContent['number']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        if (!$this->lang->residences) {
	            $this->lang->load('residences');
	        }
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
	        return $this->mybb->settings['bburl'] . '/misc.php?action=residences';
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_HomeDeleteFormatter($mybb, $lang, 'residences_home_delete')
		);
	}
    
}

$plugins->add_hook("postbit", "residences_postbit");
// ANZEIGE IM POSTBIT
function residences_postbit(&$post){

    global $templates, $db, $mybb, $lang, $theme, $residences_address;

    $uid = $post['uid'];

    $postbit_query = $db->query("SELECT * FROM ".TABLE_PREFIX."residences_user u
    LEFT JOIN ".TABLE_PREFIX."residences_home h
    ON (u.hid = h.hid) 
    LEFT JOIN ".TABLE_PREFIX."residences_streets s
    ON (u.sid = s.sid) 
    WHERE u.uid = '".$uid."'
    ");
        
    while($postbit = $db->fetch_array($postbit_query)){

        // Alles leer laufen lassen
        $hid = "";
        $sid = "";
        $street= "";
        $number = "";
        $type = "";
        $personcount = "";
        $accepted = ""; 
        $sendedby = "";
   
        // Füllen wir mal alles mit Informationen
        $hid = $postbit['hid'];
        $sid = $postbit['sid'];
        $street = $postbit['street'];
        $number = $postbit['number'];
        $type = $postbit['type'];
        $personcount = $postbit['personcount'];
        $accepted = $postbit['accepted'];

        eval("\$post['residences_address'] .= \"".$templates->get("residences_postbit_address")."\";");
    }        
}
