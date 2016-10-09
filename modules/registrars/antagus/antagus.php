<?php

//API Documents: http://xmlapi.antagus.de/

 /* **********************************************************************
 * Customization Development Services by QuYu.Net                        *
 * Copyright (c) Yunfu QuYu Tech Co.,Ltd, All Rights Reserved         	 *
 * (2013-09-23, 12:16:25)                                                *
 *                                                                       *
 *                                                                       *
 *  CREATED BY QUYU,INC.           ->       http://www.quyu.net          *
 *  CONTACT                        ->       support@quyu.net             *
 *                                                                       *
 *                                                                       *
 *                                                                       *
 *                                                                       *
 * This software is furnished under a license and may be used and copied *
 * only  in  accordance  with  the  terms  of such  license and with the *
 * inclusion of the above copyright notice.  This software  or any other *
 * copies thereof may not be provided or otherwise made available to any *
 * other person.  No title to and  ownership of the  software is  hereby *
 * transferred.                                                          *
 *                                                                       *
 *                                                                       *
 * ******************************************************************** */

$antagus_domain_cache = array();
$antagus_contact_cache = array();
$antagus_last_schedule_error = '';

function antagus_getDomainInfo($domain) {
	global $antagus_domain_cach;
	return $antagus_domain_cach[$domain]?$antagus_domain_cach[$domain]:NULL;
}

function antagus_getContactInfo() {
	global $antagus_contact_cache;
}

function antagus_getConfigArray() {
	$configarray = array (
			"Antagususerid" => array (
					"Type" => "text",
					"Size" => "30",
					"Description" => "Antagususerid" 
			) 
	);
	return $configarray;
}

function antagus_GetRegistrarLock($params) {
	return "unlocked";
}
function antagus_SaveRegistrarLock($params) {
	$values ["error"] = "";
	return $values;
}

function antagus_GetEPPCode($params) {
	//print_r($params);exit;
	if ($params['tld'] == 'de'  ||  $params['tld'] == 'eu') return antagus_GetEPPCode_de($params);
	$domain = $params['sld'].'.'.$params['tld'];
	global $antagus_domain_cache;
	if ($antagus_domain_cache[$domain]){
		$resp = $antagus_domain_cache[$domain];
	}else{
		$http = new antagusHttpRequest ();
	
		$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$resp_obj = new antagusHttpResponse ($http->get($uri));
		$resp = $resp_obj->parseResponse();
		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$antagus_domain_cache[$domain] = $resp;
	}
	$values ["error"] = "";
	$values ["eppcode"] = $resp->password;
	//print_r($values);exit;
	return $values;
}

function antagus_GetEPPCode_de($params) {
	//print_r($params);exit;
	$domain = $params['sld'].'.'.$params['tld'];
	global $antagus_domain_cache;
	if ($antagus_domain_cache[$domain]){
		$resp = $antagus_domain_cache[$domain];
	}else{
		$http = new antagusHttpRequest ();
	
		//http://backend.antagus.de/bdom/domain/set-auth/de/example-nic-domain/13048/
		$uri = '/bdom/domain/set-auth/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$p = array(
			'sld' => $params['sld'],
		);
		
		$body = $http->prepareRequest($p);
		$xml = $http->post($uri, $body);
		
		$resp_obj = new antagusHttpResponse ($xml);
		$resp = $resp_obj->parseResponse();
		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$antagus_domain_cache[$domain] = $resp;
	}
	$values ["error"] = "";
	$values ["eppcode"] = $resp.'';
	//print_r($values);exit;
	return $values;
}

function antagus_GetNameservers($params) {
	//print_r($params);exit;
	$domain = $params['sld'].'.'.$params['tld'];
	global $antagus_domain_cache;
	if ($antagus_domain_cache[$domain]){
		$resp = $antagus_domain_cache[$domain];
	}else{
		$http = new antagusHttpRequest ();
		
		$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$resp_obj = new antagusHttpResponse ($http->get($uri));
		//print_r($resp_obj);
		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$resp = $resp_obj->parseResponse();
		$antagus_domain_cache[$domain] = $resp;
	}
		
	//print_r($resp);exit;
	
	$ns = $resp->nameservers;
	$i = 0;
	while (isset($ns->nameserver[$i])) {
		$return['nameservers'][] = (string) $ns->nameserver[$i];
		$values['ns'.($i+1)] = (string) $ns->nameserver[$i];
		$i++;
	}
	//print_r($values);exit;
	return $values;
}
function antagus_SaveNameservers($params) {
	$domain = $params['sld'].'.'.$params['tld'];
	global $antagus_domain_cache;
	if ($antagus_domain_cache[$domain]){
		$resp = $antagus_domain_cache[$domain];
	}else{
		$http = new antagusHttpRequest ();
	
		$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$resp_obj = new antagusHttpResponse ($http->get($uri));
		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$resp = $resp_obj->parseResponse();
		$antagus_domain_cache[$domain] = $resp;
	}
	
	$nameservers = array();
	
	$ns_index = 0;
	for ($i=1; $i<=5; $i++){
		$key = 'ns'.$i;
		if ($params[$key]) {
			$nameservers['ns__' . $ns_index]['hostname'] = $params[$key];
			$ns_index++;
		}
	}
	
	
	$p = array(
		'sld' => $params['sld'],
		'contact-ids' => array(
			'owner' => $resp->owner,
			'admin' => $resp->admin,
			'tech' => $resp->tech,
			'zone' => $resp->zone,
		),
		'nameservers' => $nameservers
	);
	
	$uri = '/bdom/domain/update/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	//echo $uri;
	$http = new antagusHttpRequest ();
	$body = $http->prepareRequest($p);
		
	$xml = $http->post($uri, $body);
	//die($xml);
	$resp_obj = new antagusHttpResponse ($xml);
	//print_r($resp_obj);exit;
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	$values ["error"] = '';
	return $values;
}

function antagus_GetDNS($params, $try_create = true) {
	//print_r($params);exit;
	$domain = $params['sld'].'.'.$params['tld'];
	$uri = '/bdom/dns/domain/'.$domain.'/'.$params['Antagususerid'].'/';
	
	$hostrecords = array ();
	
	$http = new antagusHttpRequest ();
	$xml = $http->get($uri);
	//die($xml);
	$resp_obj = new antagusHttpResponse ($xml);
	if (!$resp_obj->error){
		//delete for test
		//$uri = '/bdom/dns/domain/'.$domain.'/'.$params['Antagususerid'].'/';
		//$http->delete($uri);
		
		$resp = $resp_obj->parseResponse();
		//print_r($resp);exit;
		foreach ( $resp->record_list->record_item as $rec )
		{
			if ($rec->type == 'NS') continue;
			$hostrecords[] = array( "hostname" => str_replace('.'.$domain, '', $rec->name), "type" => $rec->type, "address" => $rec->content,
			"priority" => $rec->priority,
			 );
		}
		
	}else{
		if ($try_create){
			if ($resp_obj->code == 404){
				$ns = antagus_GetNameservers($params);
				//print_r($ns);exit;
				/*
				$ns_index = 0;
				for ($i=1; $i<=5; $i++){
					$key = 'ns'.$i;
					if ($params[$key]) {
						$nameservers['ns__' . $ns_index]['hostname'] = $params[$key];
						$ns_index++;
					}
				}
				$record_list = array();
				$record_item_index = 0;
				$record_list['record_item__' . $record_item_index] = 
				array(
					'content' => $ns['ns1'],
					'name' => $domain,
					'ttl' => 14400,
					'type' => 'NS',
				);
				$record_item_index = 1;
				$record_list['record_item__' . $record_item_index] = 
				array(
					'content' => $ns['ns2'],
					'name' => $domain,
					'ttl' => 14400,
					'type' => 'NS',
				);
				
				$soa = array(
					'mnane' => $ns['ns1'],
					'rname' => 'root@'.$ns['ns1'],
					'serial' => time(),
					'ttl' => 7200,
				);
				
				$p = array(
					'name' => $domain,
					'user_id' => $params['Antagususerid'],
					'record_list' => $record_list,
					'soa' => $soa,
				);
				//print_r($p);exit;
				$body = $http->prepareRequest($p);
				*/
				$uri = '/bdom/dns/domain/-/'.$params['Antagususerid'].'/';
				$http = new antagusHttpRequest();
				
				
				$body = '<?xml version="1.0" encoding="UTF-8"?>
<zone xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNameSpaceSchemaLocation="DnsZone.xsd">
  <name>'.$domain.'</name>
  <user_id>'.$params['Antagususerid'].'</user_id>
  <record_list>
    <record_item>
      <content>'.$ns['ns1'].'</content>
      <name>'.$domain.'</name>
      <ttl>14400</ttl>
      <type>NS</type>
    </record_item>
    <record_item>
      <content>'.$ns['ns2'].'</content>
      <name>'.$domain.'</name>
      <ttl>14400</ttl>
      <type>NS</type>
    </record_item>
  </record_list>
  <soa>
    <mname>'.$ns['ns1'].'</mname>
    <rname>root@'.$ns['ns1'].'</rname>
    <serial>'.time().'</serial>
    <ttl>14400</ttl>
  </soa>
</zone>';
				
				//die($body);
				//$xml = $http->get($uri);
				$xml = $http->put($uri, $body);
				//die($xml);
			}
			return antagus_GetDNS($params, false);
		}
	}
	
	return $hostrecords;

}
function antagus_SaveDNS($params) {
	//print_r($params);exit;
	
	$domain = $params['sld'].'.'.$params['tld'];
	$dnszone = $params ["sld"] . "." . $params ["tld"] . ".";
	
	$ns = antagus_GetNameservers($params);
	if (count($ns) < 1){
		$values ["error"] = "get ns failed.";
		return $values;
	}
	
	$items = '';
	foreach ($params['dnsrecords'] as $d){
		if ( !is_null( $d['address'] ) && $d['address'] != "" ){
			$items .= '<record_item>';
			$items .= '<content>'.$d['address'].'</content>';
			$items .= '<name>'.$d['hostname'].'.'.$domain.'</name>';
			$items .= '<ttl>14400</ttl>';
			$items .= '<type>'.$d['type'].'</type>';
			if ($d['type'] == 'MX') $items .= '<priority>'.$d['priority'].'</priority>';
			$items .= '</record_item>';
		}
	}
	//die($items);
	
	$http = new antagusHttpRequest();
	$uri = '/bdom/dns/domain/'.$domain.'/'.$params['Antagususerid'].'/';
	$http->delete($uri);
	
	//$uri = '/bdom/dns/domain/'.$domain.'/'.$params['Antagususerid'].'/';
	$uri = '/bdom/dns/domain/-/'.$params['Antagususerid'].'/';
	$http = new antagusHttpRequest();
	$body = '<?xml version="1.0" encoding="UTF-8"?>
<zone xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNameSpaceSchemaLocation="DnsZone.xsd">
  <name>'.$domain.'</name>
  <user_id>'.$params['Antagususerid'].'</user_id>
  <record_list>
    <record_item>
      <content>'.$ns['ns1'].'</content>
      <name>'.$domain.'</name>
      <ttl>14400</ttl>
      <type>NS</type>
    </record_item>
    <record_item>
      <content>'.$ns['ns2'].'</content>
      <name>'.$domain.'</name>
      <ttl>14400</ttl>
      <type>NS</type>
    </record_item>
	'.$items.'
  </record_list>
  <soa>
    <mname>'.$ns['ns1'].'</mname>
    <rname>root@'.$ns['ns1'].'</rname>
    <serial>'.time().'</serial>
    <ttl>14400</ttl>
  </soa>
</zone>';
	//die($body);
	$xml = $http->put($uri, $body);
	//die($xml);
	$resp_obj = new antagusHttpResponse ($xml);
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	
	$values ["error"] = "";
	return $values;
}
function antagus_GetEmailForwarding($params) {
	$dnszone = $params ["sld"] . "." . $params ["tld"] . ".";
	$values ["error"] = "";
	$command = array (
			"COMMAND" => "QueryDNSZoneRRList",
			"DNSZONE" => $dnszone,
			"SHORT" => 1,
			"EXTENDED" => 1 
	);
	$response = antagus_call ( $command, antagus_config ( $params ) );
	
	$result = array ();
	
	if ($response ["CODE"] == 200) {
		foreach ( $response ["PROPERTY"] ["RR"] as $rr ) {
			$fields = explode ( " ", $rr );
			$domain = array_shift ( $fields );
			$ttl = array_shift ( $fields );
			$class = array_shift ( $fields );
			$rrtype = array_shift ( $fields );
			
			if (($rrtype == "X-SMTP") && ($fields [1] == "MAILFORWARD")) {
				if (preg_match ( '/^(.*)\@$/', $fields [0], $m )) {
					$address = $m [1];
					if (! strlen ( $address )) {
						$address = "*";
					}
				}
				$result [] = array (
						"prefix" => $address,
						"forwardto" => $fields [2] 
				);
			}
		}
	} else {
		$values ["error"] = $response ["DESCRIPTION"];
	}
	
	return $result;
}
function antagus_SaveEmailForwarding($params) {
	
	// Bug fix - Issue WHMCS
	// ###########
	if (is_array ( $params ["prefix"] [0] ))
		$params ["prefix"] [0] = $params ["prefix"] [0] [0];
	if (is_array ( $params ["forwardto"] [0] ))
		$params ["forwardto"] [0] = $params ["forwardto"] [0] [0];
		// ###########
	
	$username = $params ["Username"];
	$password = $params ["Password"];
	$testmode = $params ["TestMode"];
	$tld = $params ["tld"];
	$sld = $params ["sld"];
	foreach ( $params ["prefix"] as $key => $value ) {
		$forwardarray [$key] ["prefix"] = $params ["prefix"] [$key];
		$forwardarray [$key] ["forwardto"] = $params ["forwardto"] [$key];
	}
	// Put your code to save email forwarders here
	
	$dnszone = $params ["sld"] . "." . $params ["tld"] . ".";
	$values ["error"] = "";
	$command = array (
			"COMMAND" => "UpdateDNSZone",
			"DNSZONE" => $dnszone,
			"INCSERIAL" => 1,
			"EXTENDED" => 1,
			"DELRR" => array (
					"@ X-SMTP" 
			),
			"ADDRR" => array () 
	);
	
	foreach ( $params ["prefix"] as $key => $value ) {
		$prefix = $params ["prefix"] [$key];
		$target = $params ["forwardto"] [$key];
		if (strlen ( $prefix ) && strlen ( $target )) {
			$redirect = "MAILFORWARD";
			if ($prefix == "*") {
				$prefix = "";
			}
			$redirect = $prefix . "@ " . $redirect;
			$command ["ADDRR"] [] = "@ X-SMTP $redirect $target";
		}
	}
	
	$response = antagus_call ( $command, antagus_config ( $params ) );
	
	if ($response ["CODE"] != 200) {
		$values ["error"] = $response ["DESCRIPTION"];
	}
	return $values;
}

function antagus_get_contact_info($handle, $params){
	global $antagus_contact_cache;
	if ($antagus_contact_cache[$handle]){
		return $antagus_contact_cache[$handle];
	}else{
		$http = new antagusHttpRequest ();
		
		$uri = '/bdom/contact/status/'.$handle.'/'.$params['Antagususerid'].'/';
		//echo $uri;
		$xml = $http->get($uri);
		//echo $xml;exit;
		$resp_obj = new antagusHttpResponse ($xml);
		$resp = $resp_obj->parseResponse();
		if ($resp_obj->error){
		}else{
			$antagus_contact_cache[$handle] = $resp;
		}
		return  $resp;
	}
}

function antagus_format_contact($obj){
	//print_r($obj);exit;
	//$obj->type ORG or PERS indicates a company or individual
	$arr = (array)$obj;
	$values["First Name"] = htmlspecialchars( $arr['first-name'] );
	$values["Last Name"] = htmlspecialchars( $arr['last-name'] );
	if ($obj->type == 'ORG') $values["Company Name"] = htmlspecialchars( $obj->organisation );
	else $values["Company Name"] = '';
	$values["Address"] = htmlspecialchars( $obj->street );
	$values["City"] = htmlspecialchars( $obj->city );
	$values["State"] = htmlspecialchars( $obj->region );
	$values["Postcode"] = htmlspecialchars( $obj->postcode );
	$values["Country"] = htmlspecialchars( $obj->country );
	$values["Phone"] = htmlspecialchars( $obj->phone );
	$values["Fax"] = htmlspecialchars( $obj->fax );
	$values["Email"] = htmlspecialchars( $obj->email );
	//print_r($values);exit;
	return $values;
}

function antagus_GetContactDetails($params) {
	global $antagus_domain_cache;
	
	$domain = $params['sld'].'.'.$params['tld'];
	
	if ($antagus_domain_cache[$domain]){
		$resp = $antagus_domain_cache[$domain];
	}else{
		$http = new antagusHttpRequest ();
	
		$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$resp_obj = new antagusHttpResponse ($http->get($uri));
		$resp = $resp_obj->parseResponse();
		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$antagus_domain_cache[$domain] = $resp;
	}
	//$values["error"] = "";
	
	$contact_list = array();//Record duplicate
	
	$contact_type = array('Registrant', 'Admin', 'Technical', 'Billing');
	$contact_handle = array($resp->owner, $resp->admin, $resp->tech, $resp->zone);
	
	for ($i=0; $i < count($contact_type); $i++){
		$type = $contact_type[$i];
		$handle = $contact_handle[$i];
		if (in_array($handle, $contact_list)) continue;
		$contact_resp = antagus_get_contact_info($handle, $params);
		if ($contact_resp->errer){
			$values["error"] = $contact_resp->errer;
			return $values;
		}
		$values [$type] = antagus_format_contact($contact_resp);
	}
	/*
	$owner_handle = $resp->owner;
	if (!in_array($owner_handle, $contact_list)){
		$contact_list[] = $owner_handle;
		$owner_resp = antagus_get_contact_info($owner_handle, $params);
		if ($resp->errer){
			$values["error"] = $resp->errer;
			return $values;
		}
		$values ["Registrant"] = antagus_format_contact($owner_resp);
	}
	*/
	//print_r($values);exit;
	return $values;
}
function antagus_SaveContactDetails($params) {
	//print_r($params);exit;
	
	global $antagus_domain_cache;
	
	$domain = $params['sld'].'.'.$params['tld'];
	
	if ($antagus_domain_cache[$domain]){
		$resp = $antagus_domain_cache[$domain];
	}else{
		$http = new antagusHttpRequest ();
	
		$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$resp_obj = new antagusHttpResponse ($http->get($uri));
		print_r($resp_obj);
		$resp = $resp_obj->parseResponse();

		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$antagus_domain_cache[$domain] = $resp;
	}
	
	$domain = $params['sld'].'.'.$params['tld'];
	
	$contact_handle = array($resp->owner, $resp->admin, $resp->tech, $resp->zone);
	$contact_type = array('Registrant', 'Admin', 'Technical', 'Billing');
	$contact_list = array();
	
	for ($i=0; $i < count($contact_type); $i++){
		$type = $contact_type[$i];
		$handle = $contact_handle[$i];
		if ($params['contactdetails'][$type]){
			/*
			$contact_resp = antagus_get_contact_info($handle, $params);
			//print_r($contact_resp);
			if ($contact_resp->errer){
				$values["error"] = $contact_resp->errer;
				return $values;
			}
			*/
			
			$c = $params['contactdetails'][$type];
			$p = array(
					'first-name' => $c['First Name'],
					'last-name' => $c['Last Name'],
					'street' => $c['Address'],
					'postcode' => $c['Postcode'],
					'city' => $c['City'],
					'country' => $c['Country'],
					'phone' => $c['Phone'],
					'fax' => $c['Fax'],
					'email' => $c['Email'],
					'region' => $c['State'],
			);
			if ($c["State"]) $p['region'] = $c['State'];
			if ($c['Company Name']) $p['organisation'] = $c['Company Name'];
			//$p['type'] = $contact_resp->type.'';
			if ($p['organisation']) {
				$p['type'] = 'ORG';
			}else{
				$p['type'] = 'PERS';
			}
			
			$contact_list[$type] = $p;
		}
	}

	$contact_id = array();
	foreach($contact_list as $key=>$val){
		$val['sex'] = 'NA';
		//$val['number'] = md5($domain.$key.time());
		$val['number'] = '';
		$handle = antagus_create_contact($val, $params);
		$contact_id[$key] = $handle['handle'].'';
		if ($handle['error']){
			$values ["error"] = $handle['error'];
			return $values;
		}
	}
	
	//print_r($contact_id);//exit;
	//print_r($resp);exit;
	
	$org_ns = $resp->nameservers->nameserver;
	
	$nameservers = array();
	$ns_index = 0;
	for ($i=0; $i<=count($org_ns); $i++){
		$nameservers['ns__' . $i]['hostname'] = $org_ns[$i].'';
	}
	
	$p = array(
		'sld' => $params['sld'],
		'contact-ids' => array(
			'owner' => $contact_id['Registrant'],
			'admin' => $contact_id['Admin'],
			'tech' => $contact_id['Technical'],
			'zone' => $contact_id['Billing'],
		),
		'nameservers' => $nameservers
	);
	
	//print_r($p);exit;
	
	$uri = '/bdom/domain/update/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	//echo $uri;
	$http = new antagusHttpRequest ();
	$body = $http->prepareRequest($p);
		
	$xml = $http->post($uri, $body);
	//die($xml);
	$resp_obj = new antagusHttpResponse ($xml);
	//print_r($resp_obj);exit;
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	$values ["error"] = '';
	return $values;
}
function antagus_SaveContactDetails_old($params) {
	//print_r($params);exit;
	$params['contactdetails'];
	
	global $antagus_domain_cache;
	
	$domain = $params['sld'].'.'.$params['tld'];
	
	if ($antagus_domain_cache[$domain]){
		$resp = $antagus_domain_cache[$domain];
	}else{
		$http = new antagusHttpRequest ();
	
		$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$resp_obj = new antagusHttpResponse ($http->get($uri));
		$resp = $resp_obj->parseResponse();
		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$antagus_domain_cache[$domain] = $resp;
	}
	
	$contact_type = array('Registrant', 'Admin', 'Technical', 'Billing');
	$contact_handle = array($resp->owner, $resp->admin, $resp->tech, $resp->zone);
	
	for ($i=0; $i<count($contact_type); $i++){
		$type = $contact_type[$i];
		$handle = $contact_handle[$i];
		if ($params['contactdetails'][$type]){
			$contact_resp = antagus_get_contact_info($handle, $params);
			if ($contact_resp->errer){
				$values["error"] = $contact_resp->errer;
				return $values;
			}
			
			$c = $params['contactdetails'][$type];
			$p = array(
					'first-name' => $c['First Name'],
					'last-name' => $c['Last Name'],
					'street' => $c['Address'],
					'postcode' => $c['Postcode'],
					'city' => $c['City'],
					'country' => $c['Country'],
					'phone' => $c['Phone'],
					'fax' => $c['Fax'],
					'email' => $c['Email']
			);
			if ($c["State"]) $p['region'] = $c['State'];
			if ($c['Company Name']) $p['organisation'] = $c['Company Name'];
			$p['type'] = $contact_resp->type;
			$uri = '/bdom/contact/update/'.$handle.'/'.$params['Antagususerid'].'/';
			//echo $uri;
			//print_r($p);exit;
			$body = $http->prepareRequest($p);
			
			$xml = $http->post($uri, $body);
			//die($xml);
			$resp_obj = new antagusHttpResponse ($xml);
			//print_r($resp_obj);exit;
			if ($resp_obj->error){
				$values ["error"] = $resp_obj->error;
				return $values;
			}
			//$resp = $resp_obj->parseResponse();
		}
	}
	
	$values ["error"] = '';
	return $values;
}
function antagus_RegisterNameserver($params) {
	$domain = $params ["sld"] . "." . $params ["tld"];
	$values ["error"] = "";
	
	$regperiod = intval($params["regperiod"]);
	
	$http = new antagusHttpRequest ();
			
	$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';

	$resp_obj = new antagusHttpResponse ($http->get($uri));
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	$resp = $resp_obj->parseResponse();
	
	//Above you need to get the owner ID, is a strange settings
	//$uri = '/bdom/nameserver/create/'.$params['nameserver'].'/'.$params['Antagususerid'].'/';
	$uri = '/bdom/nameserver/create/-/'.$params['Antagususerid'].'/';
	$p = array(
		'hostname' => $params['nameserver'],
		'IP' => $params['ipaddress'],
		'hostmaster' => $resp->owner,
	);
	$body = $http->prepareRequest($p);
	$xml = $http->put($uri, $body);
	$resp_obj = new antagusHttpResponse ($xml);
	//print_r($resp_obj);exit;
	//echo $xml;exit;
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	return $values;
}
function antagus_ModifyNameserver($params) {
	$uri = '/bdom/nameserver/update/'.$params['nameserver'].'/'.$params['Antagususerid'].'/';
	$p = array(
		'hostname' => $params['nameserver'],
		'IP' => $params['newipaddress'],
	);
	$http = new antagusHttpRequest ();
	$body = $http->prepareRequest($p);
	//echo $body;
	$xml = $http->post($uri, $body);
	$resp_obj = new antagusHttpResponse ($xml);
	//print_r($resp_obj);exit;
	//echo $xml;exit;
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	return $values;
}
function antagus_DeleteNameserver($params) {
	$values ["error"] = "";
	return $values;
}
function antagus_IDProtectToggle($params) {
	$domain = $params ["sld"] . "." . $params ["tld"];
	$values ["error"] = "";
	$command = array (
			"COMMAND" => "ModifyDomain",
			"DOMAIN" => $domain,
			"X-ACCEPT-WHOISTRUSTEE-TAC" => ($params ["protectenable"]) ? "1" : "0" 
	);
	$response = antagus_call ( $command, antagus_config ( $params ) );
	if ($response ["CODE"] != 200) {
		$values ["error"] = $response ["DESCRIPTION"];
	}
	return $values;
}
function antagus_RegisterDomain($params) {
	//print_r($params);exit;
	
	$domain = $params['sld'].'.'.$params['tld'];
	$regperiod = intval($params["regperiod"]);
	
	$owner = array(
			'sex' => 'NA',
			'number' => '',
			'first-name' => $params['firstname'],
			'last-name' => $params['lastname'],
			'street' => $params['address1'] . ' ' . $params["address2"],
			'postcode' => $params['postcode'],
			'city' => $params['city'],
			'country' => $params['country'],
			'phone' => $params['fullphonenumber'],
			'fax' => $params['fullphonenumber'],
			'email' => $params['email'],
			'password' => md5(time())
	);
	if ($params['companyname']) {
		$owner['organisation'] = $params['companyname'];
		$owner['type'] = 'ORG';
	}else{
		$owner['organisation'] = '';
		$owner['type'] = 'PERS';
	}
	//$owner['number'] = md5($domain.'owner'.time());
	$owner['number'] = '';
	//$owner['remarks'] = md5($domain.'owner'.time());
	
	$admin = array(
			'sex' => 'NA',
			'number' => '',
			'first-name' => $params['adminfirstname'],
			'last-name' => $params['adminlastname'],
			'street' => $params['adminaddress1'] . ' ' . $params["adminaddress2"],
			'postcode' => $params['adminpostcode'],
			'city' => $params['admincity'],
			'country' => $params['admincountry'],
			'phone' => $params['adminfullphonenumber'],
			'fax' => $params['adminfullphonenumber'],
			'email' => $params['adminemail'],
			'password' => md5(time())
	);
	if ($params['admincompanyname']) {
		$admin['organisation'] = $params['admincompanyname'];
		$admin['type'] = 'ORG';
	}else{
		$owner['organisation'] = '';
		$admin['type'] = 'PERS';
	}
	//$admin['number'] = md5($domain.'admin'.time());
	$admin['number'] = '';
	//$admin['remarks'] = md5($domain.'owner'.time());
	
	$handle = antagus_create_contact($owner, $params);
	$onwer_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}
	$handle = antagus_create_contact($admin, $params);
	$admin_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}
	//$admin['number'] = md5($domain.'tech'.time());
	//$admin['remarks'] = md5($domain.'tech'.time());
	$handle = antagus_create_contact($admin, $params);
	$tech_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}
	//$admin['number'] = md5($domain.'bill'.time());
	//$admin['remarks'] = md5($domain.'bill'.time());
	$handle = antagus_create_contact($admin, $params);
	$bill_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}
	
	$nameservers = array();
	$ns_index = 0;
	for ($i=1; $i<=5; $i++){
		$key = 'ns'.$i;
		if ($params[$key]) {
			$nameservers['ns__' . $ns_index]['hostname'] = $params[$key];
			$ns_index++;
		}
	}
	
	$p = array(
		'sld' => $params['sld'],
		'contact-ids' => array(
			'owner' => $onwer_handle,
			'admin' => $admin_handle,
			'tech' => $tech_handle,
			'zone' => $bill_handle,
		),
		'nameservers' => $nameservers
	);
	
	$uri = '/bdom/domain/create/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	//echo $uri;
	$http = new antagusHttpRequest ();
	$body = $http->prepareRequest($p);
		
	$xml = $http->put($uri, $body);
	//die($xml);
	$resp_obj = new antagusHttpResponse ($xml);
	//print_r($resp_obj);exit;
	if ($resp_obj->error){
		//try get info
		$http = new antagusHttpRequest ();
			
		$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	
		$resp_obj = new antagusHttpResponse ($http->get($uri));
		if ($resp_obj->error){
			$values ["error"] = $resp_obj->error;
			return $values;
		}
		$resp = $resp_obj->parseResponse();
		$expiration = (isset($resp->expiration)) ? date('Y-m-d', strtotime((string) $resp->expiration)) : date('Y-m-d', strtotime(date('Y-m-d') . ' +' . $regperiod . ' years')-86400);
		antagus_scheduleExpiration($expiration, $params);
		
		$values ["error"] = '';
		return $values;
	}
	
	$resp = $resp_obj->parseResponse();
	//$expiration = (isset($resp->expiration)) ? date('Y-m-d', strtotime((string) $resp->expiration)) : date('Y-m-d', strtotime(date('Y-m-d') . ' +' . $regperiod . ' years'));
	$expiration = date('Y-m-d', strtotime(date('Y-m-d') . ' +' . $regperiod . ' years'));
    antagus_scheduleExpiration($expiration, $params);

	$values ["error"] = '';
	return $values;
}

function antagus_scheduleExpiration($expiration, $params, $checkschedules = false, $exec_on_expire=false){
	//echo 'antagus_scheduleExpiration';
	global $antagus_last_schedule_error;
	if ($checkschedules) antagus_checkSchedules($params);
	
	$p = array(
		'sld' => $params['sld'],
		'exec-date' => $expiration,
	);
	if ($exec_on_expire) {
		$p['exec-on-expire'] = 'yes';
		unset($p['exec-date']);
	}
	$uri = '/bdom/domain/delete/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	$http = new antagusHttpRequest ();
	$body = $http->prepareRequest($p);
	$xml = $http->post($uri, $body);
	//echo $xml;
	$resp_obj = new antagusHttpResponse ($xml);
	//print_r($resp_obj);//exit;
	if (!$resp_obj->error){
		//echo '+++++++++++++++++';
		return true;
	}else{
		//echo '-----------------';
		//echo 'antagusHttpResponse error:'.$resp_obj->error;
		$antagus_last_schedule_error = $resp_obj->error;
		return false;
	}
}

function antagus_checkSchedules($params) {
        $domain = $params['sld'].'.'.$params['tld'];
		
		$http = new antagusHttpRequest ();
		$xml = $http->get("/bdom/task/scheduled/-/-/-/-/".$params['Antagususerid']);
		//echo $xml;
        $resp_obj = new antagusHttpResponse ($xml);
		$resp = $resp_obj->parseResponse();
		//print_r($resp);exit;
		if (!$resp_obj->error){
			$resp = $resp_obj->parseResponse();
			if (isset($resp)) {
				$i = 0;
				while ($resp->response[$i]) {
					if ((string) $resp->response[$i]->name == $domain && (string) $resp->response[$i]->opcode == 'delete')
						$task_id = (string) $resp->response[$i]->task_id;
					$i++;
				}
			}
			if ($task_id) {
				$body = $http->prepareRequest(array('delete' => array('task_id' => $task_id)));
				$http->post("/bdom/task/delete/-/-/-/-/".$params['Antagususerid'], $body);
			}
		}
    }

function antagus_query_additionalfields(&$params) {
	$result = mysql_query ( "SELECT name,value FROM tbldomainsadditionalfields
		WHERE domainid='" . mysql_real_escape_string ( $params ["domainid"] ) . "'" );
	while ( $row = mysql_fetch_array ( $result, MYSQL_ASSOC ) ) {
		$params ['additionalfields'] [$row ['name']] = $row ['value'];
	}
}
function antagus_use_additionalfields($params, &$command) {
	include dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "additionaldomainfields.php";
	
	$myadditionalfields = array ();
	if (is_array ( $additionaldomainfields ) && isset ( $additionaldomainfields ["." . $params ["tld"]] )) {
		$myadditionalfields = $additionaldomainfields ["." . $params ["tld"]];
	}
	
	$found_additionalfield_mapping = 0;
	foreach ( $myadditionalfields as $field_index => $field ) {
		if (isset ( $field ["Ispapi-Name"] ) || isset ( $field ["Ispapi-Eval"] )) {
			$found_additionalfield_mapping = 1;
		}
	}
	
	if (! $found_additionalfield_mapping) {
		include dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . "additionaldomainfields.php";
		if (is_array ( $additionaldomainfields ) && isset ( $additionaldomainfields ["." . $params ["tld"]] )) {
			$myadditionalfields = $additionaldomainfields ["." . $params ["tld"]];
		}
	}
	
	foreach ( $myadditionalfields as $field_index => $field ) {
		if (! is_array ( $field ["Ispapi-Replacements"] )) {
			$field ["Ispapi-Replacements"] = array ();
		}
		
		if (isset ( $field ["Ispapi-Options"] ) && isset ( $field ["Options"] )) {
			$options = explode ( ",", $field ["Options"] );
			foreach ( explode ( ",", $field ["Ispapi-Options"] ) as $index => $new_option ) {
				$option = $options [$index];
				if (! isset ( $field ["Ispapi-Replacements"] [$option] )) {
					$field ["Ispapi-Replacements"] [$option] = $new_option;
				}
			}
		}
		
		$myadditionalfields [$field_index] = $field;
	}
	
	foreach ( $myadditionalfields as $field ) {
		
		if (isset ( $params ['additionalfields'] [$field ["Name"]] )) {
			$value = $params ['additionalfields'] [$field ["Name"]];
			
			$ignore_countries = array ();
			if (isset ( $field ["Ispapi-IgnoreForCountries"] )) {
				foreach ( explode ( ",", $field ["Ispapi-IgnoreForCountries"] ) as $country ) {
					$ignore_countries [strtoupper ( $country )] = 1;
				}
			}
			
			if (! $ignore_countries [strtoupper ( $params ["country"] )]) {
				
				if (isset ( $field ["Ispapi-Replacements"] [$value] )) {
					$value = $field ["Ispapi-Replacements"] [$value];
				}
				
				if (isset ( $field ["Ispapi-Eval"] )) {
					eval ( $field ["Ispapi-Eval"] );
				}
				
				if (isset ( $field ["Ispapi-Name"] )) {
					if (strlen ( $value )) {
						$command [$field ["Ispapi-Name"]] = $value;
					}
				}
			}
		}
	}
}
function antagus_TransferDomain($params) {
	//print_r($params);exit;
	
	$domain = $params['sld'].'.'.$params['tld'];
	
	$owner = array(
			'sex' => 'NA',
			'number' => '',
			'first-name' => $params['firstname'],
			'last-name' => $params['lastname'],
			'street' => $params['address1'] . ' ' . $params["address2"],
			'postcode' => $params['postcode'],
			'city' => $params['city'],
			'country' => $params['country'],
			'phone' => $params['fullphonenumber'],
			'fax' => $params['fullphonenumber'],
			'email' => $params['email'],
			'password' => md5(time())
	);
	if ($c['companyname']) {
		$owner['organisation'] = $params['companyname'];
		$owner['type'] = 'ORG';
	}else{
		$owner['organisation'] = '';
		$owner['type'] = 'PERS';
	}
	//$owner['number'] = md5($domain.'owner'.time());
	$owner['number'] = '';
	
	$admin = array(
			'sex' => 'NA',
			'number' => '',
			'first-name' => $params['adminfirstname'],
			'last-name' => $params['adminlastname'],
			'street' => $params['adminaddress1'] . ' ' . $params["adminaddress2"],
			'postcode' => $params['adminpostcode'],
			'city' => $params['admincity'],
			'country' => $params['admincountry'],
			'phone' => $params['adminfullphonenumber'],
			'fax' => $params['adminfullphonenumber'],
			'email' => $params['adminemail'],
			'password' => md5(time())
	);
	if ($c['admincompanyname']) {
		$admin['organisation'] = $params['admincompanyname'];
		$admin['type'] = 'ORG';
	}else{
		$owner['organisation'] = '';
		$admin['type'] = 'PERS';
	}
	//$admin['number'] = md5($domain.'admin'.time());
	$admin['number'] = '';
	
	$handle = antagus_create_contact($owner, $params);
	$onwer_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}
	$handle = antagus_create_contact($admin, $params);
	$admin_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}
	//$admin['number'] = md5($domain.'tech'.time());
	$admin['number'] = '';
	
	$handle = antagus_create_contact($admin, $params);
	$tech_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}
	//$admin['number'] = md5($domain.'bill'.time());
	$admin['number'] = '';
	$handle = antagus_create_contact($admin, $params);
	$bill_handle = $handle['handle'].'';
	if ($handle['error']){
		$values ["error"] = $handle['error'];
		return $values;
	}

	$nameservers = array();
	$ns_index = 0;
	for ($i=1; $i<=5; $i++){
		$key = 'ns'.$i;
		if ($params[$key]) {
			$nameservers['ns__' . $ns_index]['hostname'] = $params[$key];
			$ns_index++;
		}
	}
	
	$p = array(
		'sld' => $params['sld'],
		'contact-ids' => array(
			'owner' => $onwer_handle,
			'admin' => $admin_handle,
			'tech' => $tech_handle,
			'zone' => $bill_handle,
		),
		'nameservers' => $nameservers
	);
	$p['password'] = $params['transfersecret'];
	
	$uri = '/bdom/domain/transfer-in/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';
	//echo $uri;
	$http = new antagusHttpRequest ();
	$body = $http->prepareRequest($p);
		
	$xml = $http->put($uri, $body);
	//die($xml);
	$resp_obj = new antagusHttpResponse ($xml);
	//print_r($resp_obj);exit;
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	$values ["error"] = '';
	return $values;
}

function antagus_create_contact($owner, $params){
	$uri = '/bdom/contact/create/-/'.$params['Antagususerid'];
	$http = new antagusHttpRequest ();
	$body = $http->prepareRequest($owner);
	$xml = $http->put($uri, $body);
	//print_r($params);
	//echo 'send:'.$body."<br>\r\n";
	//echo 'resp:'.$xml."<br>\r\n";exit;
	$resp_obj = new antagusHttpResponse ($xml);
	$resp = $resp_obj->parseResponse();
	$data['error'] = '';
	$data['handle'] = '';
	if ($resp_obj->error){
		if (preg_match('/Contact with the same data already exists: (.+)/', $resp_obj->error, $matches)){
			$data['handle'] = $matches[1];
		}else{
			$data['error'] = $resp_obj->error;
		}
	}else{
		$data['handle'] = $resp->handle;
	}
	return $data;
}

function antagus_RenewDomain($params) {
	$domain = $params ["sld"] . "." . $params ["tld"];
	$values ["error"] = "";
	
	$regperiod = intval($params["regperiod"]);
	
	$http = new antagusHttpRequest ();
			
	$uri = '/bdom/domain/status/'.$params['tld'].'/'.$params['sld'].'/'.$params['Antagususerid'].'/';

	$resp_obj = new antagusHttpResponse ($http->get($uri));
	if ($resp_obj->error){
		$values ["error"] = $resp_obj->error;
		return $values;
	}
	$resp = $resp_obj->parseResponse();
	if (!$resp->expiration){
		$values ["error"] = "no expiration found.";
		return $values;
	}
	$expiration = date('Y-m-d', strtotime(date('Y-m-d', strtotime((string) $resp->expiration)) . ' +' . $regperiod . ' years')-86400);
	//echo $resp->expiration . '===========' . $expiration;exit;
	
	$result = antagus_scheduleExpiration($expiration, $params, true);
	if ($result == true){
		$values ["error"] = '';
		return $values;
	}else{
		global $antagus_last_schedule_error;
		$values ["error"] = $antagus_last_schedule_error;
		return $values;
	}
}

class antagusHttpRequest {
	var $host;
	var $port;
	
	// constructor
	function antagusHttpRequest($host='backend.antagus.de', $port='80') {
		$this->host = $host;
		$this->port = $port;
	}
	
	// uri to get
	function get($uri) {
		return $this->request ( 'GET', $uri, '' );
	}
	
	// uri to put body(xml)
	function put($uri, $body) {
		return $this->request ( 'PUT', $uri, $body );
	}
	
	// uri to post body(xml)
	function post($uri, $body) {
		return $this->request ( 'POST', $uri, $body );
	}
	
	// uri to delete
	function delete($uri) {
		return $this->request ( 'DELETE', $uri, '' );
	}
	
	// private methods
	// make request to server
	function request($method, $uri, $body) {
		# $dolog = true; //Debug, set true to enable
		$dolog = true;
		if ($method == 'GET') $dolog = false;
		if ($dolog){
			$file = dirname(__FILE__).'/'.date("Y-m-d-H-i-").uniqid().'.txt';
			$content = date('Y-m-d H:i:s')."\r\n";
			$content .= $method."\r\n";
			$content .= $uri."\r\n";
			$content .= $body."\r\n";
		}
		
		// open socket
		$sd = fsockopen ( $this->host, $this->port, $errno, $errstr );
		if (! $sd) {
			$result = "Error: connection failed";
		} else {
			// send request to server
			fputs ( $sd, $this->make_string ( $method, $uri, $body ) );
			
			// read answer
			$nl = 0; // new line detector
			         
			// initialize body length on a high value
			$count = 65535;
			while ( $str = fgets ( $sd, 1024 ) ) {
				$result .= $str;
				$count = $count - strlen ( $str );
				if ($nl == 1) {
					// set count to actual body length
					$count = hexdec ( $str );
					$nl = 0;
				}
				
				// remove CR/LF
				$str = preg_replace ( '/\015\012/', '', $str );
				if ($str == '') {
					$nl = 1;
				}
				if ($count <= 0) {
					break;
				}
			}
		}
		
		// close socket
		if ($sd) {
			fclose ( $sd );
		}
		
		$this->response = $result;
		
		if ($dolog){
			$content .= "----------------------------------------------------\r\n";
			$content .= $this->response;
			file_put_contents($file, $content);
		}
		
		return $result;
	}
	
	// create request
	function make_string($method, $uri, $body) {
		// header: method + host
		$str = strtoupper ( $method ) . " " . $uri . " HTTP/1.1\nHOST: " . $this->host;
		
		// header: ...
		$str .= "\nConnection: Keep-Alive\nUser-Agent: bdomHTTP\nContent-Type: text/xml; charset=iso-8859-1";
		
		// header: body size ... if any
		if ($body) {
			$str .= "\nContent-Length: " . strlen ( $body );
		}
		
		$str .= "\n\n";
		
		// append body ... if any
		if ($body) {
			$str .= $body;
		}
		return $str;
	}
	
	function prepareRequest($params) {
		$xmldoc = new DomDocument('1.0', 'UTF-8');
		$xmldoc->formatOutput = true;
	
		$request = $xmldoc->createElement('request');
		$request->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$xmldoc->appendChild($request);
	
		if (is_array($params))
			$this->prepareArray($xmldoc, $request, $params);
		return $xmldoc->saveXML();
	}
	
	function prepareArray(&$xmldoc, &$node, $array) {
		foreach ($array as $key => $value) {
			if (strpos($key, '__') !== false)
				$key = substr($key, 0, strpos($key, '__'));
			if ($key == '_attrib') {
				$node->setAttribute($value[0], $value[1]);
			} else {
				if (is_array($value)) {
					$nd = $xmldoc->createElement($key);
					$this->prepareArray($xmldoc, $nd, $value);
				} elseif ($value != false && $value != '')
				$nd = $xmldoc->createElement($key, $value);
				else
					$nd = $xmldoc->createElement($key);
	
				$node->appendChild($nd);
			}
		}
	}
}
class antagusHttpResponse {
	var $org_resp;
	var $error;
	var $code;
	var $body;
	
	function antagusHttpResponse($resp) {
		$this->org_resp = $resp;
		$this->code = $this->code();
		
		if ($this->code == '403') {
			if (!$silence)
				$this->addError('Forbidden error. Please ensure that this IP is unblocked for provided User ID.');
			return;
		}
		
		$this->body = $this->body();
		if ($this->code != '200' && $this->code != '202'){
			$resp = $this->parseResponse();
			if (isset($resp->umsg)) {
				$this->addError((string) $resp->umsg);
			}else{
				$this->addError('Unknown error');
			}
			return;
		}
	}
	
	function parseResponse() {
		$response_string = "<?xml version='1.0' ?>" . $this->body;
		if (!$response_string){
			$this->addError('Unable to parse the response. Wrong XML format.');
			return 0;
		}
	
		@$a = simplexml_load_string($response_string);
	
		$this->last_request = $a;
		if ($a === FALSE) {
			$this->addError('Unable to parse the response. Wrong XML format.');
			return 0;
		} else {
			return $a;
		}
	}
	
	function addError($e) {
		$this->error .= $e;
	}
	
	// extract response code
	function code() {
		preg_match ( '/HTTP\/[10\.]+\s+([0-9]+)/', $this->org_resp, $code );
		return $code [1];
	}
	
	// extract response body
	function body() {
		// body is between two empty lines for "chunked" encoding
		$chunk = preg_split ( '/(?=^\r)/m', $this->org_resp );
		$body = "";
		if ($chunk [1]) {
			// extract body from \nsize(body)\n0
			$body = ereg_replace ( "\n[0-9a-fA-F]+", "", $chunk [1] );
		}
		
		return trim ( $body );
	}
}