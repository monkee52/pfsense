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
	$pconfig['remote-addr'] = $a_vxlans[$id]['remote-addr'];
	$pconfig['remote-port'] = $a_vxlans[$id]['remote-port'];
	$pconfig['vni'] = $a_vxlans[$id]['vni'];
	$pconfig['descr'] = $a_vxlans[$id]['descr'];
}

if ($_POST['save']) {

	unset($input_errors);
	$pconfig = $_POST;

	$pconfig['remote-addr'] = addrtolower($_POST['remote-addr']);

	/* input validation */
	$reqdfields = explode(" ", "if remote-addr remote-port vni");
	$reqdfieldsn = array(gettext("Parent interface"), gettext("Remote tunnel IP address"), gettext("Remote tunnel identifier"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (!is_ipaddr($_POST['remote-addr'])) {
		$input_errors[] = gettext("The remote address field must have valid IP addresses.");
	}

	if (!is_numericint($_POST['remote-port'])) {
		$input_errors[] = gettext("The remote port field must be an integer.");
	}

	foreach ($a_vxlans as $vxlan) {
		if (isset($id) && ($a_vxlans[$id]) && ($a_vxlans[$id] === $vxlan)) {
			continue;
		}

		if (($vxlan['id'] == $_POST['id']) && ($vxlan['remote-addr'] == $_POST['remote-addr']) && ($vxlan['remote-port'] == $_POST['remote-port']) && ($vxlan['vni'] == $_POST['vni'])) {
			$input_errors[] = sprintf(gettext("A VXLAN tunnel with the %s:%s (VNI: %s) is already defined."), $vxlan['remote-addr'], $vxlan['remote-port'], $vxlan['vni']);
			break;
		}
	}

	if (!$input_errors) {
		$vxlan = array();
		$vxlan['if'] = $_POST['if'];
		$vxlan['remote-addr'] = $_POST['remote-addr'];
		$vxlan['remote-port'] = $_POST['remote-port'];
		$vxlan['vni'] = $_POST['vni'];
		$vxlan['descr'] = $_POST['descr'];
		$vxlan['vxlanif'] = $_POST['vxlanif'];

		$vxlan['vxlanif'] = interface_vxlan_configure($vxlan);
		if ($vxlan['vxlanif'] == "" || !stristr($vxlan['vxlanif'], "vxlan")) {
			$input_errors[] = gettext("Error occured creating interface, please retry.");
		} else {
			if (isset($id) && $a_vxlans[$id]) {
				$a_vxlans[$id] = $vxlan;
			} else {
				$a_vxlans[] = $vxlan;
			}

			write_config();

			$confif = convert_real_interface_to_friendly_interface_name($vxlan['vxlanif']);

			if ($confif != "") {
				interface_configure($confif);
			}

			header("Location: interfaces_vxlan.php");
			exit;
		}
	}
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
))->setHelp('The IP address of the remote tunnel endpoint, or the IP multicast group address to join.');

$section->addInput(new Form_Input(
	'remote-port',
	'VXLAN Remote Port',
	'number',
	$pconfig['remote-port'] ?: 4789,
	['min' => 0]
))->setHelp('The destination port number used in the encapsulating IPv4/IPv6 header.');

$section->addInput(new Form_Input(
	'vni',
	'VNI',
	'number',
	$pconfig['vni'],
	['min' => 0]
))->setHelp('This value is a 24-bit VXLAN Network Identifier (VNI) that identifies the virtual network segment membership of the interface.');

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
