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

		$amount      = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$receiptJson = $this->model_extension_payment_modulbank->getReceiptJson($order_id);

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
				'preauth'         => $this->config->get('payment_modulbank_preauth'),
				'description'     => 'Оплата заказа №' . $order_id,
				'success_url'     => $this->config->get('payment_modulbank_success_url'),
				'fail_url'        => $this->config->get('payment_modulbank_fail_url'),
				'cancel_url'      => $this->config->get('payment_modulbank_back_url'),
				'callback_url'    => $this->url->link('extension/payment/modulbank/callback', '', true),
				'client_name'     => $order_info['payment_firstname'],
				'client_email'    => $order_info['email'],
				'receipt_contact' => $order_info['email'],
				'receipt_items'   => $receiptJson,
				'unix_timestamp'  => time(),
				'sysinfo'         => json_encode($sysinfo),
				'salt'            => ModulbankHelper::getSalt(),
			],
		];

		$key = $this->model_extension_payment_modulbank->getKey();

		$signature = ModulbankHelper::calcSignature($key, $data['form']);

		$data['form']['signature'] = $signature;
		$this->log($data, 'paymentform');

		return $this->load->view('extension/payment/modulbank', $data);
	}

	public function callback()
	{
		$this->load->model('extension/payment/modulbank');
		$this->load->model('checkout/order');
		$this->log($this->request->post, 'callback');
		$key = $this->model_extension_payment_modulbank->getKey();
		$signature = ModulbankHelper::calcSignature($key, $this->request->post);

		$order_id = $this->request->post['order_id'];

		if (strcmp($this->request->post['merchant'], $this->config->get('payment_modulbank_merchant')) !== 0) {
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
			if (
				(
					$this->request->post['state'] === 'COMPLETE' ||
					$this->request->post['state'] === 'AUTHORIZED'
				) &&
				$order_info['order_status_id'] != $this->config->get('payment_modulbank_confirm_order_status_id')
			) {
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
