<?php

class Util {
	static function emailAdmin($message = '', $subject = '', $from = '', $rcpt = '') {
		$message = (dev_not_null($message)) ? $message : "If you have recieved this email, then the admnistrative alert system on your website has been activated with no status message. Please check your log files.\n";
		$subject = (dev_not_null($subject)) ? $subject : "Automated Email Alert From Your Site!";
		$from = (dev_not_null($from)) ? $from : "admin@" . dev_domain();
		$rcpt = (dev_not_null($rcpt)) ? $rcpt : "admin@" . dev_domain();
		
		$status = dev_send_email($rcpt, $from, $subject, $message);
		return $status;
	}

	static function parachute(&$count, $max = '', $redirect = '', $log = false, $alert = false) {
		$max = (dev_not_null($rcpt)) ? $max : 400;
		if ($count >= $max) {
			$status = "Loop exceeded max count! Killing Process.\n";
			if ($alert) dev_email_admin_alert($status);
			if ($log) dev_create_log($status);
			if (dev_not_null($redirect)) dev_redirect($redirect, array('msg'=>$status));
			else exit("A script on this page began to loop out of control. Process has been killed. If you are viewing this message, please alert the administrator.\n");
		}
		$count++;
	}

	static function var($var) {
		$cookie = ( array_key_exists( $var, $_COOKIE) ) ? $_COOKIE[$var] : NULL;  
		$get = ( array_key_exists( $var, $_GET) ) ? $_GET[$var] : NULL;
		$post = ( array_key_exists( $var, $_POST) ) ? $_POST[$var] : NULL;
		return ( dev_not_null($cookie) ) ? $cookie : (( dev_not_null($post) ) ? $post : $get);
	}

}