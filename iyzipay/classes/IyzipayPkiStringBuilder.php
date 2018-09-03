<?php 


class IyzipayPkiStringBuilder {

	public static function pkiStringGenerate($objectData) {
		$pki_value = "[";
		foreach ($objectData as $key => $data) {
			if(is_object($data)) {
				$name = var_export($key, true);
				$name = str_replace("'", "", $name); 
				$pki_value .= $name."=[";
				$end_key = count(get_object_vars($data));
				$count 	 = 0;
				foreach ($data as $key => $value) {
					$count++;
					$name = var_export($key, true);
					$name = str_replace("'", "", $name); 
					$pki_value .= $name."="."".$value;
					if($end_key != $count)
						$pki_value .= ",";
				}
				$pki_value .= "]";
			} else if(is_array($data)) {
				$name = var_export($key, true);
				$name = str_replace("'", "", $name); 
				$pki_value .= $name."=[";
				$end_key = count($data);
				$count 	 = 0;
				foreach ($data as $key => $result) {
					$count++;
					$pki_value .= "[";
					
					foreach ($result as $key => $item) {
						$name = var_export($key, true);
						$name = str_replace("'", "", $name); 
					
						$pki_value .= $name."="."".$item;
						if(end($result) != $item) {
							$pki_value .= ",";
						}
						if(end($result) == $item) {
							if($end_key != $count) {
								$pki_value .= "], ";
							
							} else {
								$pki_value .= "]";
							}
						}
					}
				}
				if(end($data) == $result) 
					$pki_value .= "]";
				
			} else {
				$name = var_export($key, true);
				$name = str_replace("'", "", $name); 
				  
				$pki_value .= $name."="."".$data."";
			}
			if(end($objectData) != $data)
				$pki_value .= ",";
		}
		$pki_value .= "]";
		return $pki_value;
	}

		public static function authorization($pkiString,$apiKey,$secretKey,$rand) {

			$hash_value = $apiKey.$rand.$secretKey.$pkiString;
			$hash 		= base64_encode(sha1($hash_value,true));
			
			$authorizationText 	= 'IYZWS '.$apiKey.':'.$hash;
			
			$authorization = array(
				'authorization' => $authorizationText,
				'randValue' 	=> $rand
			);
			
			return $authorization;
		}


}