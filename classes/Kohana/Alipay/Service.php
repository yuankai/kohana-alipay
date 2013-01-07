<?php

defined('SYSPATH') or die('No direct access allowed.');

class Kohana_Alipay_Service extends Alipay {
	// 交易类型

	const ESCROW = 'create_partner_trade_by_buyer';
	const DIRECT = 'create_direct_pay_by_user';
	const DUALFUN = 'trade_create_by_buyer';
	const TRANS = 'batch_trans_notify';
	const SENDGOODS = 'send_goods_confirm_by_platform';

	/**
	 * @var string 默认服务类型
	 */
	public static $default = Alipay_Service::ESCROW;

	/**
	 * @var   Kohana_Alipay_Service instances
	 */
	public static $instances = array();

	/**
	 * 单件模式获得一个service实例
	 *
	 * @param array 参数
	 * @param string 接口key
	 * @param string 签名类型 
	 */
	public static function instance($parameter, $key, $sign_type = '')
	{
		//如果服务类型为空，则使用默认类型
		if (empty($parameter['service']))
		{
			$service = Alipay_Service::$default;
		}

		//判断当前要的实例是否存在
		if (isset(Alipay_Service::$instances[$service]))
		{
			return Alipay_Service::$instances[$service];
		}

		//不存在则生成新的实例
		$service_class = 'Alipay_Service';
		Alipay_Service::$instances[$service] =
			new $service_class($parameter, $key, $sign_type);

		//返回实例
		return Alipay_Service::$instances[$service];
	}

	/**
	 * service构造方法，初始化gateway地址
	 *
	 * @param array 参数
	 * @param string 接口key
	 * @param string 签名类型 
	 */
	public function __construct($parameter, $key, $sign_type = '')
	{
		//调用父类初始化
		parent::__construct();

		//设定服务的网关地址
		$this->gateway = "https://www.alipay.com/cooperate/gateway.do?";
		$this->key = $key;
		$this->sign_type = empty($sign_type) ? $this->config['sign_type'] : $sign_type;
		$this->parameter = $this->para_filter($parameter);

		if (empty($parameter['input_charset']))
		{
			$this->parameter['_input_charset'] = $this->config['input_charset'];
		}

		if (empty($parameter['payment_type']))
		{
			$this->parameter['payment_type'] = $this->config['payment_type'];
		}

		$this->_input_charset = $this->parameter['_input_charset'];

		//得到从字母a到z排序后的签名参数数组
		$sort_array = $this->arg_sort($this->parameter);

		//获得签名结果
		$this->mysign = $this->build_mysign($sort_array, $this->key, $this->sign_type);
	}

	/**
	 * 构造表单提交HTML
	 *
	 * @param array 要提交的参数
	 * @param string 表单提交方法
	 * @return string 表单提交HTML文本
	 */
	public function build_form($method = 'GET', $auto_submit = TRUE)
	{
		$str_html = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gateway .
			"_input_charset=" . $this->parameter['_input_charset'] . "' method='$method'>";

		while (list ($key, $val) = each($this->parameter))
		{
			$str_html.= ("<input type='hidden' name='" . $key . "' value='" . $val . "'/>");
		}

		$str_html .= ("<input type='hidden' name='sign' value='" . $this->mysign . "'/>");
		$str_html .= ("<input type='hidden' name='sign_type' value='" . $this->sign_type . "'/>");

		//submit按钮控件请不要含有name属性
		$str_html .= "<input type='submit' value='submit'></form>";

		//自动提交
		if ($auto_submit)
		{
			$str_html .= "<script>document.forms['alipaysubmit'].submit();</script>";
		}

		//返回生成后的表单
		return $str_html;
	}

}