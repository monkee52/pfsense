<?php
/*
 * interfaces_vxlan_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2020 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

##|+PRIV
##|*IDENT=page-interfaces-vxlan-edit
##|*NAME=Interfaces: VXLAN: Edit
##|*DESCR=Allow access to the 'Interfaces: VXLAN: Edit' page.
##|*MATCH=interfaces_vxlan_edit.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");

init_config_arr(array('vxlans', 'vxlan'));
$a_vxlans = &$config['vxlans']['vxlan'];
$id = $_REQUEST['id'];

if (isset($id) && $a_vxlans[$id]) {
	$pconfig['if'] = $a_vxlans[$id]['if'];
	$pconfig['vxlanif'] = $a_vxlans[$id]['vxlanif'];
	$pconfig['descr'] = $a_vxlans[$id]['descr'];
}

if ($_POST['save']) {

}

function build_parent_list() {
	$parentlist = array();
	$portlist = get_possible_listen_ips();
	foreach ($portlist as $ifn => $ifinfo) {
		$parentlist[$ifn] = $ifinfo;
	}

	return($parentlist);
}

$pgtitle = array(gettext("Interfaces"), gettext("VXLANs"), gettext("Edit"));
$pglinks = array("", "interfaces_vxlan.php", "@self");
$shortcut_section = "interfaces";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('VXLAN Configuration');

$section->addInput(new Form_Select(
	'if',
	'*Parent Interface',
	$pconfig['if'],
	build_parent_list()
))->setHelp('This interface serves as the local address to be used for the VXLAN tunnel.');

$section->addInput(new Form_IpAddress(
	'remote-addr',
	'*VXLAN Remote Address',
	$pconfig['remote-addr']
))->setHelp('Peer address where VXLAN packets will be sent.');

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for administrative reference (not parsed).');

$form->addGlobal(new Form_Input(
	'vxlanif',
	null,
	'hidden',
	$pconfig['vxlanif']
));

if (isset($id) && $a_vxlans[$id]) {
	$form->addGlobal(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$form->add($section);
print($form);

include("foot.inc");
