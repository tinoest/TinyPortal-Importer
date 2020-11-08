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
                
			    break;
            case 'blocks':
                importSPBlocks();
                break;
            case 'shoutbox':

                break;
            case 'categories':

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

    $hooks = array (
        'tp_admin_areas'                    => array (
            '$sourcedir/TPImporter.php|TPImporterAdminAreas',
        ),
        'tp_pre_admin_subactions'           => array ( 
            '$sourcedir/TPImporter.php|TPImporterActions',
        ),
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

function importSPBlocks() {{{

    $tpBlock    = new TinyPortal\Block();
    $database   = new TinyPortal\DataBase();

    // Remove all the existing blocks
    $blocks  = $tpBlock->getBlocks();
    foreach($blocks as $block) {
        $tpBlock->deleteBlock($block['id']);
    }

    $request =  $database->db_query('', '
        SELECT spb.id_block AS id, spb.type AS type, spb.label AS title, spb.col AS bar, spb.row AS pos, ( 1 ^ spb.state ) AS off, ( CASE WHEN spp.variable = \'content\' THEN spp.value ELSE \'\' END ) AS body, \'theme\' AS frame
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

    $types  = $tpBlock->getBlockType();
    $types  = array_flip($types);

    foreach($spBlocks as $block) {
        $type           = ($spTypeMap[$block['type']]);
        $block['type']  = ($types[$type]);
        $tpBlock->insertBlock($block);
    }
    
}}}

?>
