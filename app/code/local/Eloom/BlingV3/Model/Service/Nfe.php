<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Service_Nfe extends Mage_Core_Model_Abstract {

	protected $order;
	protected $nfeToXmlService;
	protected $shipmentModel;
	protected $configModel;
	protected $apikey;
	protected $commentNfe;
	protected $serieNfe;

	protected function sendNfe($data) {
		//ob_start();// debug
		//$out = fopen('/tmp/output.txt', 'w');// debug

		$curl_handle = curl_init();
		//curl_setopt($curl_handle, CURLOPT_VERBOSE, true); // debug
		//curl_setopt($curl_handle, CURLOPT_STDERR, $out); // debug

		curl_setopt($curl_handle, CURLOPT_URL, 'https://bling.com.br/Api/v2/notafiscal/json/');
		curl_setopt($curl_handle, CURLOPT_POST, count($data));
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($curl_handle);

		//fclose($out);// debug
		//$debug = ob_get_clean();// debug

		curl_close($curl_handle);

		return json_decode($response);
	}

	public function getNfe($apikey, $documentNumber, $serie) {
		$url = 'https://bling_v3.com.br/Api/v2/notafiscal/' . $documentNumber . '/' . $serie . '/json';
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($curl_handle);
		curl_close($curl_handle);

		return json_decode($response);
	}

}
