<?php
/**
 * @package TinyPortal
 * @version 1.0.0
 * @author tinoest
 * @license BSD 3
 *
 * Copyright (C) 2019 - tinoest
 *
 */

if (!defined('SMF'))
	die('Hacking attempt...');

global $txt;

$txt['tp-import-sp-intro']      = 'This screen allows for Importing Simple Portal Data.';
$txt['tp-import-sp-settings']   = 'TinyPortal Import Simple Portal Data';
$txt['tp-import-sp-list']       = 'Import';

function template_import_sp()
{
    global $context;

    if(isset($_GET['import_sp'])) {

        if(!isset($context['tp_panels']))
            $context['tp_panels'] = array();

        $context['template_layers'][]   = 'tpadm';
        $context['template_layers'][]   = 'subtab';
        TPadminIndex();
        $context['current_action']      = 'admin';
        $context['sub_template']        = 'tp_importer_admin';

        if($context['TPortal']['hidebars_admin_only'] == '1') {
            tp_hidebars();
        }
    }
}

function template_tp_importer_admin()
{

	global $txt, $context, $boarddir, $scripturl;

	isAllowedTo('admin');

    $ret = '';
    if(array_key_exists('import_option', $_POST)) {
	    switch($_POST['import_option']) {
		    case 'articles':
                importSPArticles();                
			    break;
            case 'blocks':
                importSPBlocks();
                break;
            case 'shoutbox':
                importSPShouts();
                break;
            case 'categories':
                importSPCategories();
                break;
            default:

                break;
	    }
    }

    $options = array ( 'articles', 'blocks', 'shoutbox', 'categories' );

    echo '
		<form class="tborder" accept-charset="', $context['character_set'], '" name="TPadmin" action="' . $scripturl . '?action=tpadmin;import_sp"  method="post" style="margin: 0px;">
		<div class="cat_bar"><h3 class="catbg">'.$txt['tp-import-sp-settings'].'</h3></div>
		<div id="tpimport_sps" class="admintable admin-area">
			<div class="information smalltext">' , $txt['tp-import-sp-intro'] , '</div><div></div>
			<div class="windowbg noup">
				<div class="padding-div">
					<input type="hidden" name="sc" value="', $context['session_id'], '" />
					<select name="import_option">';

            foreach ( $options as $name )
                echo '<option value="'.$name.'">'.$name.'</option>';

            echo '
				</select>
				<input type="submit" value="'.$txt['tp-import-sp-list'].'" name="'.$txt['tp-import-sp-list'].'">
				</div>
			</div>
		</div>
		</form>
		';
    
    echo $ret;

}

function TPImporterHookPreLoad() {{{

    if(class_exists('TinyPortal\Integrate')) {
        $hooks = array (
            'tp_admin_areas'                    => array (
                '$sourcedir/TPImporter.php|TPImporterAdminAreas',
            ),
            'tp_pre_admin_subactions'           => array ( 
                '$sourcedir/TPImporter.php|TPImporterActions',
            ),
            'buffer'                            => 'TPImporterHookBuffer',
        );

        foreach ($hooks as $hook => $callable) {
            if(is_array($callable)) {
                foreach($callable as $call ) {
                    TinyPortal\Integrate::TPAddIntegrationFunction('integrate_' . $hook, $call, false);
                }
            }
            else {
                TinyPortal\Integrate::TPAddIntegrationFunction('integrate_' . $hook, $callable, false);
            }
        }
    }

    return;
}}}

function TPImporterActions() {{{

    template_import_sp();

}}}

function TPImporterAdminAreas() {{{

    global $context, $scripturl;

	if (allowedTo('admin')) {
		$context['admin_tabs']['custom_modules']['import_sp'] = array(
			'title' => 'TPImporter',
			'description' => '',
			'href' => $scripturl . '?action=tpadmin;import_sp=list',
			'is_selected' => isset($_GET['import_sp']),
		);
		$admin_set = true;
	}

}}}

function importSPArticles() {{{

    $database   = TinyPortal\DataBase::getInstance();

    // Remove all the existing articles
    $request =  $database->db_query('', '
        DELETE FROM {db_prefix}tp_articles
        WHERE 1=1
        ',
        array()
    );

   $request =  $database->db_query('', '
        SELECT id_page AS id, namespace AS shortname, body, type, status AS off, title AS subject, views
        FROM {db_prefix}sp_pages
        WHERE 1=1
        ',
        array()
    );

    $spArticles = array();

    if($database->db_num_rows($request) > 0) {
        while ( $article = $database->db_fetch_assoc($request) ) {
            $spArticles[] = $article;
        }
    }

    $database->db_free_result($request);

    foreach($spArticles as $article) {
        if($article['type'] == 'html' ) {
            $article['body'] =  html_entity_decode($article['body']);
        }
        TinyPortal\Article::getInstance()->insertArticle($article);
    }
    
}}}

function importSPCategories() {{{

    $database   = TinyPortal\DataBase::getInstance();

    // Remove all the existing categories
     $request =  $database->db_query('', '
		DELETE FROM {db_prefix}tp_variables
        WHERE 1=1
        ',
        array()
    );

    $request =  $database->db_query('', '
        SELECT id_category AS id, name
        FROM {db_prefix}sp_categories
        WHERE 1=1
        ',
        array()
    );

    $spCategories = array();

    if($database->db_num_rows($request) > 0) {
        while ( $block = $database->db_fetch_assoc($request) ) {
            $spCategories[] = $block;
        }
    }

    $database->db_free_result($request);

    foreach($spCategories as $category) {
        $id     = ($category['id']);
        $name   = ($category['name']);
        $database->db_query('', "INSERT INTO {db_prefix}tp_variables ( id, value1, value2, value3, type, value4, value5 ) VALUES ( '$id' , '$name' , 0 , '' , 'category' , '' , 0 )", array() );
    }
    
}}}

function importSPBlocks() {{{

    $tpBlock    = TinyPortal\Block::getInstance();
    $database   = TinyPortal\DataBase::getInstance();

    // Remove all the existing blocks
    $blocks  = $tpBlock->getBlocks();
    foreach($blocks as $block) {
        $tpBlock->deleteBlock($block['id']);
    }

    $request =  $database->db_query('', '
        SELECT spb.id_block AS id, spb.type AS type, spb.label AS title, spb.col AS bar, spb.row AS pos, ( 1 ^ spb.state ) AS off, ( CASE WHEN spp.variable = \'content\' THEN spp.value ELSE \'\' END ) AS body, \'theme\' AS frame, groups_allowed AS access, display, 1 AS visible
        FROM {db_prefix}sp_blocks AS spb
        INNER JOIN {db_prefix}sp_parameters AS spp ON spb.id_block = spp.id_block 
        WHERE spp.variable = \'content\'
        AND spb.type IN ( \'sp_html\' , \'sp_bbc\', \'sp_php\' )
        ',
        array()
    );

    $spBlocks = array();

    if($database->db_num_rows($request) > 0) {
        while ( $block = $database->db_fetch_assoc($request) ) {
            $spBlocks[] = $block;
        }
    }

    $database->db_free_result($request);

    $spTypeMap = array (
        'sp_html'   => 'scriptbox',
        'sp_php'    => 'phpbox',
        'sp_bbc'    => 'html',
    );

    $spSideMap = array (
        1 => 1,
        2 => 6,
        3 => 5,
        4 => 2,
        5 => 3,
        6 => 7,
    );

    $types  = $tpBlock->getBlockType();
    $types  = array_flip($types);

    foreach($spBlocks as $block) {
        switch($block['type']) {
            case 'sp_html':
                $block['body'] = html_entity_decode($block['body']);
            case 'sp_php':
            case 'sp_bbc';
            default:
                break;
        }
        $block['display']       = str_replace('forum', 'forumall', $block['display']);
        $block['display']       = str_replace('portal', 'frontpage', $block['display']);
        $block['editgroups']    = '';

        // Convert to TinyPortal Format
        $type           = ($spTypeMap[$block['type']]);
        $block['type']  = ($types[$type]);
        $block['bar']   = ($spSideMap[$block['bar']]);
        $tpBlock->insertBlock($block);
    }
    
}}}

function importSPShouts() {{{

    $database   = TinyPortal\DataBase::getInstance();

    // Remove all the existing shouts
     $request =  $database->db_query('', '
		DELETE FROM {db_prefix}tp_shoutbox
        WHERE 1=1
        ',
        array()
    );

    $request =  $database->db_query('', '
        SELECT id_shout AS id, id_shoutbox AS shoutbox_id, id_member AS member_id, log_time AS time, body AS content
        FROM {db_prefix}sp_shouts
        WHERE 1=1
        ',
        array()
    );

    $spShouts = array();

    if($database->db_num_rows($request) > 0) {
        while ( $shout = $database->db_fetch_assoc($request) ) {
            $spShouts[] = $shout;
        }
    }

    $database->db_free_result($request);

    foreach($spShouts as $shout) {
        TinyPortal\Shout::getInstance()->insertShout($shout);
    }
    
}}}

function TPImporterHookBuffer($buffer) {{{
    global $settings;

    // This should be updated to a better image. Also not the best way to do it but it works. 
    $string     = '<img style="margin-bottom: 8px;" src="' . $settings['tp_images_url'] . '/TPov_import_sp.png" alt="TPov_import_sp" />';
    $replace    = '<img src="data:image/png;base64,
        iVBORw0KGgoAAAANSUhEUgAAAAUA
        AAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO
        9TXL0Y4OHwAAAABJRU5ErkJggg==" alt="Red dot" />';

    if(strpos($buffer, $string) !== FALSE) {
        $buffer = str_replace($string, $replace, $buffer);
    }

    return $buffer;
}}}

?>
