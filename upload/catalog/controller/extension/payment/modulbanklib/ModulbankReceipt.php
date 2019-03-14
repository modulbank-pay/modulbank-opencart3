<?php
class ModulbankReceipt
{
	private $items         = array();
	private $resultTotal   = 0;
	private $currentSum    = 0;
	private $sno           = '';
	private $paymentMethod = '';

	public function __construct($sno, $payment_method, $total = 0.0)
	{
		$this->resultTotal   = intval(round($total * 100));
		$this->sno           = $sno;
		$this->paymentMethod = $payment_method;
	}

	public function addItem($name, $amount, $taxId, $payment_object, $quantity = 1.0)
	{
		if ($amount == 0) {
			return;
		}

		$this->items[] = array(
			"quantity"       => round($quantity * 1000),
			"price"          => round($amount * 100),
			"vat"            => $taxId,
			"name"           => $name,
			"payment_object" => $payment_object,
			"payment_method" => $this->paymentMethod,
			"sno"            => $this->sno,
		);
		$this->currentSum += round($amount * 100 * $quantity);
	}

	private function normalize()
	{
		if ($this->resultTotal != 0 && $this->resultTotal != $this->currentSum) {
			$coefficient = $this->resultTotal / $this->currentSum;
			$realprice   = 0;
			$aloneId     = null;
			foreach ($this->items as $index => &$item) {
				$item['price'] = round($coefficient * $item['price']);
				$realprice += round($item['price'] * $item['quantity'] / 1000);
				if ($aloneId === null && $item['quantity'] === 1000) {
					$aloneId = $index;
				}

			}
			unset($item);
			if ($aloneId === null) {
				foreach ($this->items as $index => $item) {
					if ($aloneId === null && $item['quantity'] > 1000) {
						$aloneId = $index;
						break;
					}
				}
			}
			if ($aloneId === null) {
				$aloneId = 0;
			}

			$diff = $this->resultTotal - $realprice;

			if (abs($diff) >= 0.001) {
				if ($this->items[$aloneId]['quantity'] === 1000) {
					$this->items[$aloneId]['price'] = round($this->items[$aloneId]['price'] + $diff);
				} elseif (
					count($this->items) == 1
					&& abs(round($this->resultTotal / $this->items[$aloneId]['quantity']) - $this->resultTotal / $this->items[$aloneId]['quantity']) < 0.001
				) {
					$this->items[$aloneId]['price'] = round($this->resultTotal / $this->items[$aloneId]['quantity']);
				} elseif ($this->items[$aloneId]['quantity'] > 1000) {
					$tmpItem = $this->items[$aloneId];
					$item    = array(
						"quantity"       => 1000,
						"price"          => round($tmpItem['price'] + $diff),
						"vat"            => $tmpItem['vat'],
						"name"           => $tmpItem['name'],
						"payment_object" => $tmpItem['payment_object'],
						"payment_method" => $tmpItem['payment_method'],
						"sno"            => $tmpItem['sno'],
					);
					$this->items[$aloneId]['quantity'] -= 1000;
					array_splice($this->items, $aloneId + 1, 0, array($item));
				} else {
					$this->items[$aloneId]['price'] = round($this->items[$aloneId]['price'] + $diff / ($this->items[$aloneId]['quantity'] / 1000));

				}
			}
		}
	}

	private function correctDimmensoin()
	{
		foreach ($this->items as &$item) {
			$item['quantity'] = number_format(round($item['quantity'] / 1000, 3), 3, '.', '');
			$item['price']    = number_format(round($item['price'] / 100, 2), 2, '.', '');
		}
	}

	public function getItems()
	{
		$this->normalize();
		$this->correctDimmensoin();
		return $this->items;
	}

	public function getJson()
	{
		return json_encode($this->getItems(), JSON_HEX_APOS);
	}
}
