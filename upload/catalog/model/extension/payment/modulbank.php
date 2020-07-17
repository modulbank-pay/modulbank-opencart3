<?php
include_once DIR_APPLICATION . 'controller/extension/payment/modulbanklib/ModulbankHelper.php';
include_once DIR_APPLICATION . 'controller/extension/payment/modulbanklib/ModulbankReceipt.php';

class ModelExtensionPaymentModulbank extends Model
{
	public function getMethod($address, $total)
	{
		$this->load->language('extension/payment/modulbank');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('payment_liqpay_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

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
				'sort_order' => $this->config->get('payment_modulbank_sort_order'),
			);
		}

		return $method_data;
	}

	public function onOrderUpdate($order_id, $order_status_id)
	{
		if ($order_status_id == $this->config->get('payment_modulbank_refund_order_status_id')) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "modulbank WHERE order_id=" . $order_id);
			$key   = $this->getKey();
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

		if (
			$order_status_id == $this->config->get('payment_modulbank_confirm_order_status_id')
			&& $this->config->get('payment_modulbank_preauth')
		) {
			$this->load->model('checkout/order');
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "modulbank WHERE order_id=" . $order_id);
			$key   = $this->getKey();
			if ($query->num_rows) {
				$order_info  = $this->model_checkout_order->getOrder($order_id);
				$amount      = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
				$receiptJson = $this->getReceiptJson($order_id);
				$data        = [
					'merchant'        => $this->config->get('payment_modulbank_merchant'),
					'amount'          => $amount,
					'transaction'     => $query->row['transaction'],
					'receipt_contact' => $order_info['email'],
					'receipt_items'   => $receiptJson,
					'unix_timestamp'  => time(),
					'salt'            => ModulbankHelper::getSalt(),
				];
				$this->log($data, 'confirm');

				$result = ModulbankHelper::capture($data, $key);
				$this->log($result, 'confirm_response');
			}

		}
	}

	public function getReceiptJson($order_id)
	{
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		$sno                     = $this->config->get('payment_modulbank_sno');
		$payment_method          = $this->config->get('payment_modulbank_payment_method');
		$payment_object          = $this->config->get('payment_modulbank_payment_object');
		$payment_object_delivery = $this->config->get('payment_modulbank_payment_object_delivery');
		$product_vat             = $this->config->get('payment_modulbank_product_vat');
		$delivery_vat            = $this->config->get('payment_modulbank_delivery_vat');

		$amount  = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$receipt = new ModulbankReceipt($sno, $payment_method, $amount);

		$query = $this->db->query("
			SELECT op.*, (
				SELECT rate FROM " . DB_PREFIX . "product as p
				LEFT JOIN " . DB_PREFIX . "tax_rule as r on r.tax_class_id=p.tax_class_id
				LEFT JOIN " . DB_PREFIX . "tax_rate as rt on rt.tax_rate_id=r.tax_rate_id and rt.type='P'
				WHERE p.product_id=op.product_id order by rate desc limit 1
			) as rate FROM " . DB_PREFIX . "order_product as op
			WHERE op.order_id = '" . $order_id . "'
			");
		$this->log($order_info, 'order');
		$this->log($query->rows, 'products');

		foreach ($query->rows as $product) {
			if ($product_vat == '0') {
				$rate = intval($product['rate']);
				switch ($rate) {
					case 0:$item_vat = 'vat0';
						break;
					case 10:$item_vat = 'vat10';
						break;
					case 20:$item_vat = 'vat20';
						break;
					default:$item_vat = 'none';
				}
			} else {
				$item_vat = $product_vat;
			}
			$name = htmlspecialchars_decode($product['name']);
			$receipt->addItem($name, $product['price'], $item_vat, $payment_object, $product['quantity']);
		}
		$query = $this->db->query("SELECT value FROM " . DB_PREFIX . "order_total WHERE order_id = '" . $order_id . "' and code='shipping'");
		if (isset($query->row['value']) && $query->row['value']) {
			$receipt->addItem('Доставка', $query->row['value'], $delivery_vat, $payment_object_delivery);
		}
		return $receipt->getJson();
	}

	public function getTransactionStatus($transaction)
	{
		$merchant = $this->config->get('payment_modulbank_merchant');
		$this->log([$merchant, $transaction], 'getTransactionStatus');

		$key = $this->getKey();

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

	public function getKey()
	{
		if ($this->config->get('payment_modulbank_mode') == 'test') {
			$key = $this->config->get('payment_modulbank_test_secret_key');
		} else {
			$key = $this->config->get('payment_modulbank_secret_key');
		}
		return $key;
	}

	public function getVersion()
	{
		$query = $this->db->query("SELECT version FROM " . DB_PREFIX . "modification WHERE code = 'modulbank_payment'");
		return $query->row['version'];
	}
}
