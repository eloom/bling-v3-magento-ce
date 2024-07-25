<?php

##eloom.licenca##

class Eloom_BlingV3_Model_Config extends Mage_Core_Model_Abstract {

	const XML_PATH_ACTIVE = 'eloom_blingv3/general/active';
	const XML_PATH_CLIENT_ID = 'eloom_blingv3/general/client_id';
	const XML_PATH_SECRET_KEY = 'eloom_blingv3/general/secret_key';
	const XML_PATH_ACCESS_TOKEN = 'eloom_blingv3/general/access_token';
	const XML_PATH_REFRESH_TOKEN = 'eloom_blingv3/general/refresh_token';
	const XML_PATH_PRODUCT_ID = 'eloom_blingv3/general/product_id';
	const XML_PATH_STORE_NUMBER = 'eloom_blingv3/general/store_number';

	/**
	 * Mapeamento das Transportadoras - Loja > BlingV3
	 */
	const XML_PATH_SHIPPING = 'eloom_blingv3eloom_bling/shipping/name';


	/**
	 * Mapeamento do Pagamento
	 */
	const XML_PATH_PAYMENTS = 'eloom_blingv3/payment/name';

	/**
	 * NF-e de Saida
	 */
	const XML_PATH_NFE_OUT_SERIE = 'eloom_blingv3/nfe_out/serie';
	const XML_PATH_NFE_OUT_COMMENT = 'eloom_blingv3/nfe_out/comment';
	const XML_PATH_NFE_OUT_MAPPED = 'eloom_blingv3/nfe_out/mapped';

	/**
	 * Mapeamento do Tracking
	 */
	const XML_PATH_TRACKING = 'eloom_blingv3/tracking/name';

	const XML_PATH_TRACKING_TIMER = 'eloom_blingv3/tracking/timer';

	/**
	 * Retrieve store model instance
	 *
	 * @return Mage_Core_Model_Store
	 */
	public function getStore() {
		return Mage::app()->getStore();
	}

	public function getConfig($path) {
		return Mage::getStoreConfig($path);
	}

	public function getConfigFlag($path) {
		return Mage::getStoreConfigFlag($path);
	}

	public function isModuleActive() {
		return $this->getConfigFlag(self::XML_PATH_ACTIVE);
	}

	public function getProductId() {
		return trim($this->getConfig(self::XML_PATH_PRODUCT_ID));
	}

	public function getNfeOutSerie() {
		return trim($this->getConfig(self::XML_PATH_NFE_OUT_SERIE));
	}

	public function getNfeOutComment() {
		return trim($this->getConfig(self::XML_PATH_NFE_OUT_COMMENT));
	}

	public function getNfeOutMapped() {
		return trim($this->getConfig(self::XML_PATH_NFE_OUT_MAPPED));
	}

	public function getTimerTracking() {
		return trim($this->getConfig(self::XML_PATH_TRACKING_TIMER));
	}

	public function getStoreNumber() {
		return trim($this->getConfig(self::XML_PATH_STORE_NUMBER));
	}

	public function getShippingMapped() {
		return trim($this->getConfig(self::XML_PATH_SHIPPING));
	}

	public function getTrackingMapped() {
		return trim($this->getConfig(self::XML_PATH_TRACKING));
	}

	public function getPaymentMapped() {
		return trim($this->getConfig(self::XML_PATH_PAYMENTS));
	}

	public function getApiKey() {
		return trim($this->getConfig(self::XML_PATH_CLIENT_ID));
	}

	public function getSecretKey() {
		return trim($this->getConfig(self::XML_PATH_SECRET_KEY));
	}

	public function getAccessToken() {
		return trim($this->getConfig(self::XML_PATH_ACCESS_TOKEN));
	}

	public function getRefreshToken() {
		return trim($this->getConfig(self::XML_PATH_REFRESH_TOKEN));
	}

	public function saveRefreshToken(string $value) {
		$this->saveConfig(self::XML_PATH_REFRESH_TOKEN, $value);
	}

	public function saveAccessToken(string $value) {
		$this->saveConfig(self::XML_PATH_ACCESS_TOKEN, $value);
	}

	private function saveConfig(string $path, string $value) {
		Mage::getConfig()->saveConfig($path, trim($value), 'default', 0);
		Mage::getConfig()->reinit();
		Mage::app()->reinitStores();
	}

	public function isImmediatelyTimerTracking() {
		return ($this->getTimerTracking() == Eloom_BlingV3_Model_System_Config_Source_Timer::IMMEDIATELY);
	}

	public function isManuallyTracking() {
		return ($this->getTimerTracking() == Eloom_BlingV3_Model_System_Config_Source_Timer::MANUALLY);
	}

	public function isSaveTracking($createdAt) {
		$today = time();
		$timeZone = new \DateTimeZone('America/Sao_Paulo');
		$initialDate = new \DateTime($createdAt, $timeZone);

		$dayinpass = strtotime(date_format($initialDate, "Y-m-d H:i:s"));
		$timeInterval = round(abs($today - $dayinpass) / 60 / 60);
		$configInterval = (int)$this->getTimerTracking();

		return ($timeInterval > $configInterval);
	}

	/**
	 * Verifica se o Status atual do pedido está mapeado nas NF-e de Saída.
	 *
	 * @param type $status
	 * @return boolean
	 */
	public function isInitialStatusMappedOnNfeOut($status) {
		$isEnabled = false;

		$maps = unserialize($this->getNfeOutMapped());
		if ($maps && sizeof($maps) > 0) {
			foreach ($maps as $key => $map) {
				if ($status == $map['initial']) {
					$isEnabled = true;
					break;
				}
			}
		}

		return $isEnabled;
	}

	/**
	 * Retorna o Status Final do Pedido nas NF-e de saída
	 *
	 * @param type $status
	 * @return string
	 */
	public function getFinalStatusMappedOnNfeOut($status) {
		$value = null;

		$maps = unserialize($this->getNfeOutMapped());
		foreach ($maps as $key => $map) {
			if ($status == $map['initial']) {
				$value = $map['final'];
				break;
			}
		}

		return $value;
	}

	/**
	 * Retorna a Natureza da Operação nas NF-e de saída
	 *
	 * @param type $status
	 * @return string
	 */
	public function getNfeOutNatOpByStatus($status) {
		$value = null;

		$maps = unserialize($this->getNfeOutMapped());
		foreach ($maps as $key => $map) {
			if ($status == $map['initial']) {
				$value = $map['operation'];
				break;
			}
		}

		return $value;
	}

	/**
	 * Verifica se o Localizador é o Número da Nota Fiscal.
	 *
	 * Obs.: A Jadlog usa o Número da NF para rastrear os pedidos.
	 *
	 * @param $shippingMethod Método de Frete
	 * @return boolean
	 */
	public function isTrackingNumberIsNfNumber($shippingMethod) {
		$trackingIsNfNumber = false;
		$maps = unserialize($this->getTrackingMapped());
		foreach ($maps as $key => $map) {
			if ($shippingMethod == $map['code']) {
				if (Eloom_BlingV3_Block_Adminhtml_System_Config_Form_Field_TrackingOrig::NF_NUMBER == $map['tracking']) {
					$trackingIsNfNumber = true;
				}
				break;
			}
		}

		return $trackingIsNfNumber;
	}

	/**
	 * Retorna o código do método de entrega para preencher o localizador do pedido.
	 *
	 * @param $shippingMethod
	 * @return null
	 */
	public function getShippingMethodCode($shippingMethod) {
		$code = null;

		$maps = unserialize($this->getTrackingMapped());
		foreach ($maps as $key => $map) {
			if ($shippingMethod == $map['method']) {
				$code = $map['code'];
				break;
			}
		}

		return $code;
	}

}
