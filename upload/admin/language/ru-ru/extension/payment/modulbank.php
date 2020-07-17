<?php
// Heading
$_['heading_title'] = 'Интернет-эквайринг Модульбанк';

// Text
$_['text_extension'] = 'Расширения';
$_['text_success']   = 'Настройки Модульбанка сохранены';
$_['text_edit']      = 'Настройки Интернет-эквайринга Модульбанк';

$_['text_mode_test'] = 'Тестовый';
$_['text_mode_prod'] = 'Боевой';

$_['text_logging_off']   = 'Выключено';
$_['text_logging_on']    = 'Включено';
$_['text_preauth_off']      = 'Выключено';
$_['text_preauth_on']       = 'Включено';
$_['text_download_logs'] = 'Скачать логи';

$_['text_vat_0']      = 'Брать из настроек товара';
$_['text_vat_none']   = 'Без НДС';
$_['text_vat_vat0']   = 'НДС по ставке 0%';
$_['text_vat_vat10']  = 'НДС чека по ставке 10%';
$_['text_vat_vat20']  = 'НДС чека по ставке 20%';
$_['text_vat_vat110'] = 'НДС чека по расчетной ставке 10%';
$_['text_vat_vat120'] = 'НДС чека по расчетной ставке 20%';

$_['text_sno_osn']                = 'Общая СН';
$_['text_sno_usn_income']         = 'Упрощенная СН (доходы)';
$_['text_sno_usn_income_outcome'] = 'Упрощенная СН (доходы минус расходы)';
$_['text_sno_envd']               = 'Единый налог на вмененный доход';
$_['text_sno_esn']                = 'Единый сельскохозяйственный налог';
$_['text_sno_patent']             = 'Патентная СН';

$_['text_pm_full_prepayment'] = 'Предоплата 100%';
$_['text_pm_prepayment']      = 'Предоплата';
$_['text_pm_advance']         = 'Аванс';
$_['text_pm_full_payment']    = 'Полный расчет';
$_['text_pm_partial_payment'] = 'Частичный расчет и кредит';
$_['text_pm_credit']          = 'Передача в кредит';
$_['text_pm_credit_payment']  = 'Оплата кредита';

$_['text_po_commodity']             = 'Товар';
$_['text_po_excise']                = 'Подакцизный товар';
$_['text_po_job']                   = 'Работа';
$_['text_po_service']               = 'Услуга';
$_['text_po_gambling_bet']          = 'Ставка азартной игры';
$_['text_po_gambling_prize']        = 'Выигрыш азартной игры';
$_['text_po_lottery']               = 'Лотерейный билет';
$_['text_po_lottery_prize']         = 'Выигрыш лотереи';
$_['text_po_intellectual_activity'] = 'Предоставление результатов интеллектуальной деятельности';
$_['text_po_payment']               = 'Платеж';
$_['text_po_agent_commission']      = 'Агентское вознаграждение';
$_['text_po_composite']             = 'Составной предмет расчета';
$_['text_po_another']               = 'Другое';

$_['text_modulbank'] = '<img src="view/image/payment/modulbank.png" alt="Модульбанк" title="Модульбанк" style="border: 1px solid #EEEEEE;" />';

// Entry
$_['entry_paymentname']             = 'Название способа оплаты';
$_['entry_merchant']                = 'Мерчант';
$_['entry_secret_key']              = 'Секретный ключ';
$_['entry_test_secret_key']         = 'Тестовый секретный ключ';
$_['entry_success_url']             = 'Адрес для перехода после успешной оплаты';
$_['entry_fail_url']                = 'Адрес для перехода после ошибки при оплате';
$_['entry_back_url']                = 'Адрес для перехода в случае нажатия кнопки «Вернуться в магазин»';
$_['entry_mode']                    = 'Режим';
$_['entry_sno']                     = 'Система налогообложения';
$_['entry_product_vat']             = 'Ставка НДС на товары';
$_['entry_delivery_vat']            = 'Ставка НДС на доставку';
$_['entry_voucher_vat']             = 'Ставка НДС на сертификаты';
$_['entry_payment_method']          = 'Метод платежа';
$_['entry_payment_object']          = 'Предмет расчёта';
$_['entry_payment_object_delivery'] = 'Предмет расчёта на доставку';
$_['entry_payment_object_voucher']  = 'Предмет расчёта на сертификаты';
$_['entry_total']                   = 'Сумма';
$_['entry_order_status']            = 'Статус оплаченного заказа';
$_['entry_confirm_order_status']    = 'Статус для подтверждения оплаты';
$_['entry_order_refund_status']     = 'Статус возврата заказа';
$_['entry_geo_zone']                = 'Гео зона';
$_['entry_status']                  = 'Статус';
$_['entry_sort_order']              = 'Сортировка';
$_['entry_logging']                 = 'Логирование';
$_['entry_log_size_limit']          = 'Ограничение размеров лога (Mb)';
$_['entry_preauth']                 = 'Предавторизация';

// Help
$_['help_total'] = 'The checkout total the order must reach before this payment method becomes active.';

// Error
$_['error_permission']  = 'Warning: You do not have permission to modify payment Modulbank!';
$_['error_merchant']    = 'Merchant ID не указан!';
$_['error_secret_key']  = 'Секретный ключ не указан!';
$_['error_paymentname'] = 'Не указано название способа оплаты';
