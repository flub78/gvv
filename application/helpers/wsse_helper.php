<?php

/**
 *    Project {$PROJECT}
 *    Copyright (C) 2015 {$AUTHOR}
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *    Routines de support Web Services Security (WS-Security, WSS)
 */
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');


if (!function_exists('wsse_header_short')) {

    /**
     * Crée un header d'authentification FFVV
     * 
     * @param unknown $username
     * @param unknown $password
     * @return string
     */
    function wsse_header_short($username, $password) {
        $nonce = hash_hmac('sha1', uniqid(null, true), uniqid(), false);
        $created = new DateTime('now', new DateTimezone('UTC'));
        $created = $created->format(DateTime::ISO8601);
        $digest = sha1($nonce . $created . $password, true);
        return sprintf(' UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"', $username, base64_encode($digest), $nonce, $created);
    }
}

/**
 * Envoie une requête à HEVA
 */
if (!function_exists('heva_request')) {

function heva_request($req_uri = "", $params = array())
{
	$CI = & get_instance();
	// $FFVV_Heva_Host="api.licences.ffvv.stadline.com";
	$FFVV_Heva_Host="api.heva.ffvp.fr";
	
	$ffvv_id = $CI->config->item('ffvv_id');
	$ffvv_pwd = $CI->config->item('ffvv_pwd');
// 	var_dump($ffvv_id);
// 	var_dump($ffvv_pwd);
	$head = wsse_header_short($ffvv_id, $ffvv_pwd);
	$url = "http://" . $FFVV_Heva_Host . $req_uri;
	$result = Requests::get($url, array('X-WSSE' => $head), $params);
	return $result;
}
}

if (!function_exists('decode_chunked')) {
function decode_chunked($str) {		// décode les paquets HTTP 1.1 'chunked'
    for ($res = ''; !empty($str); $str = trim($str)) {
        $pos = strpos($str, "\r\n");
        $len = hexdec(substr($str, 0, $pos));
        $res.= substr($str, $pos + 2, $len);
        $str = substr($str, $pos + 2 + $len);
    }
    return $res;
}
}
