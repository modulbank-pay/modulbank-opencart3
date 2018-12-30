<?php
class ControllerExtensionPaymentModulbank extends Controller
{
	private $error = array();

	public function index()
	{
		$this->load->language('extension/payment/modulbank');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_modulbank', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant'])) {
			$data['error_merchant'] = $this->error['merchant'];
		} else {
			$data['error_merchant'] = '';
		}

		if (isset($this->error['secret_key'])) {
			$data['error_secret_key'] = $this->error['secret_key'];
		} else {
			$data['error_secret_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/modulbank', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['action'] = $this->url->link('extension/payment/modulbank', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$data['vat_list'] = array(
			'0'      => $this->language->get('text_vat_0'),
			'none'   => $this->language->get('text_vat_none'),
			'vat0'   => $this->language->get('text_vat_vat0'),
			'vat10'  => $this->language->get('text_vat_vat10'),
			'vat20'  => $this->language->get('text_vat_vat20'),
			'vat110' => $this->language->get('text_vat_vat110'),
			'vat120' => $this->language->get('text_vat_vat120'),
		);

		$data['sno_list'] = array(
			'osn'                => $this->language->get('text_sno_osn'),
			'usn_income'         => $this->language->get('text_sno_usn_income'),
			'usn_income_outcome' => $this->language->get('text_sno_usn_income_outcome'),
			'envd'               => $this->language->get('text_sno_envd'),
			'esn'                => $this->language->get('text_sno_esn'),
			'patent'             => $this->language->get('text_sno_patent'),
		);

		$data['payment_method_list'] = array(
			'full_prepayment' => $this->language->get('text_pm_full_prepayment'),
			'prepayment'      => $this->language->get('text_pm_prepayment'),
			'advance'         => $this->language->get('text_pm_advance'),
			'full_payment'    => $this->language->get('text_pm_full_payment'),
			'partial_payment' => $this->language->get('text_pm_partial_payment'),
			'credit'          => $this->language->get('text_pm_credit'),
			'credit_payment'  => $this->language->get('text_pm_credit_payment'),
		);

		$data['payment_object_list'] = array(
			'commodity'             => $this->language->get('text_po_commodity'),
			'excise'                => $this->language->get('text_po_excise'),
			'job'                   => $this->language->get('text_po_job'),
			'service'               => $this->language->get('text_po_service'),
			'gambling_bet'          => $this->language->get('text_po_gambling_bet'),
			'gambling_prize'        => $this->language->get('text_po_gambling_prize'),
			'lottery'               => $this->language->get('text_po_lottery'),
			'lottery_prize'         => $this->language->get('text_po_lottery_prize'),
			'intellectual_activity' => $this->language->get('text_po_intellectual_activity'),
			'payment'               => $this->language->get('text_po_payment'),
			'agent_commission'      => $this->language->get('text_po_agent_commission'),
			'composite'             => $this->language->get('text_po_composite'),
			'another'               => $this->language->get('text_po_another'),
		);

		$settings = array(
			'merchant',
			'secret_key',
			'mode',
			'sno',
			'product_vat',
			'delivery_vat',
			'payment_method',
			'payment_object',
			'payment_object_delivery',
			'total',
			'order_status_id',
			'refund_order_status_id',
			'geo_zone_id',
			'status',
			'sort_order',
		);
		foreach ($settings as $key) {

			if (isset($this->request->post['payment_modulbank_' . $key])) {
				$data['payment_modulbank_' . $key] = $this->request->post['payment_modulbank_' . $key];
			} else {
				$data['payment_modulbank_' . $key] = $this->config->get('payment_modulbank_' . $key);
			}
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/modulbank', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/payment/modulbank')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_modulbank_merchant']) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}

		if (!$this->request->post['payment_modulbank_secret_key']) {
			$this->error['secret_key'] = $this->language->get('error_secret_key');
		}

		return !$this->error;
	}
}
