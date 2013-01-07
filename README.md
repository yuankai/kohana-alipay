kohana-alipay
=============

kohana alipay module

$param = array(
    'service' => Alipay_Service::DIRECT,
    'partner' => $this->_partner,
    'seller_email' => $this->_seller_email,
    "return_url" => $pay_config['return_url'],
    "notify_url" => $pay_config['notify_url'],
    "show_url" => $pay_config['show_url'],
    "out_trade_no" => $data['order']->no,
    "subject" => $data['order']->title,
    "body" => $data['order']->title,
    "total_fee" => $data['order']->price,
    "receive_name" => $data['order']->contact_username,
    "receive_mobile" => $data['order']->contact_cellphone,
);

$form_html = Alipay_Service::instance($param, $this->_key)->build_form();

$this->response->body($form_html);