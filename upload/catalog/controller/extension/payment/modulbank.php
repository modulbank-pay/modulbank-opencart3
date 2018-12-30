<?php
class ControllerExtensionPaymentModulbank extends Controller
{
	public function index()
	{

		$this->load->model('checkout/order');

		$order_id   = (int) $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($order_id);

		$sno                     = $this->config->get('payment_modulbank_sno');
		$payment_method          = $this->config->get('payment_modulbank_payment_method');
		$payment_object          = $this->config->get('payment_modulbank_payment_object');
		$payment_object_delivery = $this->config->get('payment_modulbank_payment_object_delivery');
		$product_vat             = $this->config->get('payment_modulbank_product_vat');
		$delivery_vat            = $this->config->get('payment_modulbank_delivery_vat');
		$receipt                 = new ModulbankReceipt($sno, $payment_method, $sum);
		//проверить для class_id = 9 чтобы не было повторения товаров в выборке
		$query = $this->db->query("
			SELECT p.*, rt.rate FROM " . DB_PREFIX . "order_product as p
			LEFT JOIN " . DB_PREFIX . "tax_rule as r on r.tax_class_id=p.tax_class_id
			LEFT JOIN " . DB_PREFIX . "tax_rate as rt on rt.tax_rate_id=r.tax_rate_id and type='P'
			WHERE p.order_id = '" . $order_id . "'");

		foreach ($query->rows as $product) {
			if ($product_vat == '0') {
				switch ($product['rate']) {
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

		$data = [
			'action'         => 'https://pay.modulbank.ru/pay',
			'button_confirm' => $this->language->get('button_confirm'),
			'form'           => [
				'merchant'        => $this->config->get('payment_modulbank_merchant'),
				'amount'          => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
				'order_id'        => $this->session->data['order_id'],
				'testing'         => $this->config->get('payment_modulbank_mode') == 'test' ? 1 : 0,
				'description'     => 'Оплата заказа №' . $order_id,
				'success_url'     => $this->url->link('checkout/success', '', true),
				'fail_url'        => $this->url->link('checkout/fail', '', true),
				'fail_url'        => $this->url->link('checkout/fail', '', true),
				'cancel_url'      => $this->config->get('payment_modulbank_cancel_url'),
				'callback_url'    => $this->url->link('extension/payment/modulbank/callback', '', true),
				'client_name'     => $order_info['payment_firstname'],
				'client_email'    => $order_info['email'],
				'receipt_contact' => $order_info['email'],
				'receipt_items'   => $receipt->getJson(),
				'unix_timestamp'  => time(),
				'salt'            => ModulbankHelper::getSlat(),
			],
		];

		$signature                 = ModulbankHelper::calcSignature($this->config->get('payment_modulbank_secret_key'), $data['form']);
		$data['form']['signature'] = $signature;

		return $this->load->view('extension/payment/modulbank', $data);
	}

	public function callback()
	{
		$signature = ModulbankHelper::calcSignature($this->config->get('payment_modulbank_secret_key'), $this->request->post);

		$order_id = $this->request->post['order_id'];

		if ($this->request->post['merchant'] == $this->config->get('payment_modulbank_merchant')) {
			$this->error('Wrong merchant');
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			$this->error('Incorrect order id');
		}

		$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		if ($amount !== $this->request->post['amount']) {
			$this->error('Incorrect payment amount');
		}

		if ($signature == $this->request->post['signature']) {
			$this->load->model('checkout/order');
			if ($this->request->post['signature'] == 'COMPLETE'){
				$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));
			}
		} else {
			$this->error('Wrong signature');
		}
	}


	private function error($msg)
	{
		throw new Exception($msg, 1);
	}
}
