<?php
/**
 * @package TinyPortal Importer
 * @version 1.0.0
 * @author tinoest
 * @license BSD 3.0
 *
 * Copyright (C) 2020 - tinoest
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, 
 * OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
*/

global $hooks;

$hooks = array(
    'integrate_pre_include'                         => '$sourcedir/TPImporter.php',
    'integrate_pre_load'                            => 'TPImporterHookPreLoad',
);

$mod_name = 'TinyPortalImporter';

// ---------------------------------------------------------------------------------------------------------------------
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF')) {
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF')) {
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
}

setup_hooks();

function setup_hooks () {{{
	global $context, $hooks;
    
	$integration_function = empty($context['uninstalling']) ? 'add_integration_function' : 'remove_integration_function';

	foreach ($hooks as $hook => $function) {
		if(strpos($function, ',') === false) {
			$integration_function($hook, $function);
		}
		else {
			$tmpFunc = explode(',', $function);
			foreach($tmpFunc as $func) {
				$integration_function($hook, $func);
			}
		}
    }

}}}
