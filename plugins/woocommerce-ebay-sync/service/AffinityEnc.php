<?php 
class AffinityEnc {
	static public function makekeys() {
		set_time_limit(600);
		$pubKey = '';
		$privKey = '';
		
		$ok = false;
		if (function_exists('openssl_pkey_new')) {
			$config = array(
					'digest_alg' => 'sha512',
					'private_key_bits' => 2048,
					'private_key_type' => OPENSSL_KEYTYPE_RSA,
			);
			
			$res = openssl_pkey_new($config);
			if ($res === false) {
				$ok = false;
			} else {
				$ok = true;
			}
		}
		
		if ($ok && function_exists('openssl_pkey_export') && function_exists('openssl_pkey_get_details')) {
			$privKey = '';
			openssl_pkey_export($res, $privKey);

			$pubKey = openssl_pkey_get_details($res);
			$pubKey = $pubKey['key'];
			return array($privKey, $pubKey);
		} else {
			require_once(__DIR__.'/phpseclib/Crypt/RSA.php');
			require_once(__DIR__.'/phpseclib/Math/BigInteger.php');
			$rsa = new Crypt_RSA();
			
			$a = $rsa->createKey(2048);
			return array($a['privatekey'], $a['publickey']);
		}
		
		if (empty($privKey) || empty($pubKey)) {
			return false;
		} else {
			return array($privKey, $pubKey);
		}
	}
	
	static public function getToken() {
		set_time_limit(600);
		$token = get_option('affinityPushAccessToken');
		$privKey = get_option('ebayaffinity_privkey');
		$d = '';
		if (empty($token) || empty($privKey)) {
			return false;
		}
		
		$token = base64_decode($token);
		
		$ok = false;
		
		if (function_exists('openssl_get_privatekey')) {
			$res = openssl_get_privatekey($privKey);
			if ($res === false) {
				$ok = false;
			} else {
				$ok = true;
			}
		}
		
		if ($ok && function_exists('openssl_private_decrypt')) {
			openssl_private_decrypt($token, $d, $res, OPENSSL_PKCS1_OAEP_PADDING);
		} else {
			require_once(__DIR__.'/phpseclib/Crypt/RSA.php');
			require_once(__DIR__.'/phpseclib/Math/BigInteger.php');
			$rsa = new Crypt_RSA();
			$rsa->loadKey($privKey);
			$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_OAEP);
			$d = $rsa->decrypt($token);
		}
		if (empty($d)) {
			return false;
		} else {
			return $d;
		}
	}
}