<?php

include_once __DIR__ . '/modulbanklib/ModulbankReceipt.php';
include_once __DIR__ . '/modulbanklib/ModulbankHelper.php';

class ControllerExtensionPaymentModulbank extends Controller
{
	public function index()
	{

		$this->load->model('checkout/order');
		$this->load->model('extension/payment/modulbank');

		$order_id   = (int) $this->session->data['order_id'];
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
			$receipt->addItem($product['name'], $product['price'], $item_vat, $payment_object, $product['quantity']);
		}
		$query = $this->db->query("SELECT value FROM " . DB_PREFIX . "order_total WHERE order_id = '" . $order_id . "' and code='shipping'");
		if (isset($query->row['value']) && $query->row['value']) {
			$receipt->addItem('Доставка', $query->row['value'], $delivery_vat, $payment_object_delivery);
		}

		$sysinfo = [
			'language' => 'PHP ' . phpversion(),
			'plugin'   => $this->model_extension_payment_modulbank->getVersion(),
			'cms'      => 'Opencart ' . VERSION,
		];

		$data = [
			'action'         => 'https://pay.modulbank.ru/pay',
			'button_confirm' => $this->language->get('button_confirm'),
			'form'           => [
				'merchant'        => $this->config->get('payment_modulbank_merchant'),
				'amount'          => $amount,
				'order_id'        => $order_id,
				'testing'         => $this->config->get('payment_modulbank_mode') == 'test' ? 1 : 0,
				'description'     => 'Оплата заказа №' . $order_id,
				'success_url'     => $this->config->get('payment_modulbank_success_url'),
				'fail_url'        => $this->config->get('payment_modulbank_fail_url'),
				'cancel_url'      => $this->config->get('payment_modulbank_back_url'),
				'callback_url'    => $this->url->link('extension/payment/modulbank/callback', '', true),
				'client_name'     => $order_info['payment_firstname'],
				'client_email'    => $order_info['email'],
				'receipt_contact' => $order_info['email'],
				'receipt_items'   => $receipt->getJson(),
				'unix_timestamp'  => time(),
				'sysinfo'         => json_encode($sysinfo),
				'salt'            => ModulbankHelper::getSalt(),
			],
		];

		$key = $this->config->get('payment_modulbank_mode') == 'test' ?
		$this->config->get('payment_modulbank_test_secret_key') :
		$this->config->get('payment_modulbank_secret_key');

		$signature                 = ModulbankHelper::calcSignature($key, $data['form']);
		$data['form']['signature'] = $signature;
		$this->log($data, 'paymentform');

		return $this->load->view('extension/payment/modulbank', $data);
	}

	public function callback()
	{
		$this->load->model('extension/payment/modulbank');
		$this->load->model('checkout/order');
		$this->log($this->request->post, 'callback');
		if ($this->request->post['testing'] == '0') {
			$key = $this->config->get('payment_modulbank_secret_key');
		} else {
			$key = $this->config->get('payment_modulbank_test_secret_key');
		}
		$signature = ModulbankHelper::calcSignature($key, $this->request->post);

		$order_id = $this->request->post['order_id'];

		if (strcmp($this->request->post['merchant'],$this->config->get('payment_modulbank_merchant')) !== 0) {
			$this->error('Wrong merchant');
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			$this->error('Incorrect order id');
		}

		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		if ($amount != $this->request->post['amount']) {
			$this->error('Incorrect payment amount');
		}

		if ($signature === $this->request->post['signature']) {
			$this->load->model('checkout/order');
			if ($this->request->post['state'] === 'COMPLETE') {
				$this->db->query("REPLACE " . DB_PREFIX . "modulbank (order_id, amount, transaction) VALUES ($order_id,'"
					. $this->db->escape($this->request->post['amount']) . "','"
					. $this->db->escape($this->request->post['transaction_id']) . "')");
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_modulbank_order_status_id'));
			}
		} else {
			$this->error('Wrong signature');
		}
	}

	private function error($msg)
	{
		$this->log($msg, 'error');
		throw new Exception($msg, 1);
	}

	private function log($data, $category)
	{
		$this->load->model('extension/payment/modulbank');
		$this->model_extension_payment_modulbank->log($data, $category);
	}

}
