<?php
include_once DIR_APPLICATION . 'controller/extension/payment/modulbanklib/ModulbankHelper.php';

class ModelExtensionPaymentModulbank extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/modulbank');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_liqpay_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_modulbank_total') > 0 && $this->config->get('payment_modulbank_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_modulbank_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'modulbank',
				'title'      => $this->config->get('payment_modulbank_paymentname'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_modulbank_sort_order')
			);
		}

		return $method_data;
	}

	public function onOrderUpdate($order_id, $order_status_id) {
		if ($order_status_id == $this->config->get('payment_modulbank_refund_order_status_id')) {
			$query = $this->db->query("SELECT * FROM ".DB_PREFIX."modulbank WHERE order_id=".$order_id);
			$key = $this->config->get('payment_modulbank_mode') == 'test'?
						$this->config->get('payment_modulbank_test_secret_key'):
						$this->config->get('payment_modulbank_secret_key');
			if ($query->num_rows) {
				$merchant = $this->config->get('payment_modulbank_merchant');
				$this->log([$merchant,
					$query->row['amount'],
					$query->row['transaction']], 'refund');

				$result = ModulbankHelper::refund(
					$merchant,
					$query->row['amount'],
					$query->row['transaction'],
					$key
				);
				$this->log($result, 'refund_response');
			}

		}
	}

	public function getTransactionStatus($transaction)
	{
		$merchant = $this->config->get('payment_modulbank_merchant');
		$this->log([$merchant, $transaction], 'getTransactionStatus');

		$key = $this->config->get('payment_modulbank_mode') == 'test'?
						$this->config->get('payment_modulbank_test_secret_key'):
						$this->config->get('payment_modulbank_secret_key');

		$result = ModulbankHelper::getTransactionStatus(
			$merchant,
			$transaction,
			$key
		);
		$this->log($result, 'getTransactionStatus_response');
		return json_decode($result);
	}

	public function log($data, $category)
	{
		if ($this->config->get('payment_modulbank_logging')) {
			$filename   = DIR_LOGS . '/modulbank.log';
			$size_limit = $this->config->get('payment_modulbank_log_size_limit');
			ModulbankHelper::log($filename, $data, $category, $size_limit);
		}
	}

	public function getVersion()
	{
		$query = $this->db->query("SELECT version FROM " . DB_PREFIX . "modification WHERE code = 'modulbank_payment'");
		return $query->row['version'];
	}
}