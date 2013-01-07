<?php

defined('SYSPATH') or die('No direct access allowed.');

abstract class Kohana_Alipay {

	/**
	 * @var array 配置数组
	 */
	protected $config;

	/**
	 * @var string 网关地址
	 */
	protected $gateway;

	/**
	 * @var string校验key
	 */
	protected $key;

	/**
	 * @var string 签名方式
	 */
	protected $sign_type = 'MD5';

	/**
	 * @var string 签名结果
	 */
	protected $mysign;

	/**
	 * @var string 字符编码方式
	 */
	protected $input_charset;

	/**
	 * @var string 访问模式http,https
	 */
	protected $transport;

	/**
	 * @var array 构建表单或参与验证的参数
	 */
	protected $parameter = array();

	/**
	 * 构造函数，加载一些配置选项
	 */
	protected function __construct()
	{
		//获得alipay的配置
		$config = Kohana::$config->load('alipay');
		if ( ! $config)
		{
			throw new Kohana_Alipay_Exception('Failed to load Kohana Alipay config: :config',
				array(':config' => 'alipay'));
		}

		$this->config = $config;
		$this->mysign = "";
	}

	/**
	 *  生成签名结果
	 *
	 * @param array 要签名的数组
	 * @param string 密钥
	 * @param string 签名类型
	 * @return string 签名结果字符串
	 */
	protected function build_mysign($sort_array, $key, $sign_type = "MD5")
	{
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->create_linkstring($sort_array);

		//把拼接后的字符串再与安全校验码直接连接起来
		$prestr = $prestr . $key;

		//把最终的字符串签名，获得签名结果
		$mysgin = $this->sign($prestr, $sign_type);

		//返回签名
		return $mysgin;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 *
	 * @param array 需要拼接的数组
	 * @return array 拼接完成以后的字符串
	 */
	protected function create_linkstring($array)
	{
		$arg = "";
		while (list ($key, $val) = each($array))
		{
			$arg .= $key . "=" . $val . "&";
		}

		//去掉最后一个&字符
		$arg_final = substr($arg, 0, count($arg) - 2);

		//返回
		return $arg_final;
	}

	/**
	 * 除去数组中的空值和签名参数
	 * 
	 * @access protected
	 * @param array 签名参数组
	 * @return array 去掉空值与签名参数后的新签名参数组
	 */
	protected function para_filter($parameter)
	{
		$para = array();
		while (list ($key, $val) = each($parameter))
		{
			if ($key == "sign" || $key == "sign_type" || $val == "")
				continue;
			else
				$para[$key] = $parameter[$key];
		}
		return $para;
	}

	/**
	 * 对数组排序
	 *
	 * $array 排序前的数组
	 * return 排序后的数组
	 */
	protected function arg_sort($array)
	{
		ksort($array);
		reset($array);
		return $array;
	}

	/**
	 * 签名字符串
	 * $prestr 需要签名的字符串
	 * return 签名结果
	 */
	protected function sign($prestr, $sign_type)
	{
		$sign = '';
		if ($sign_type == 'MD5')
		{
			$sign = md5($prestr);
		}
		elseif ($sign_type == 'DSA')
		{
			//DSA 签名方法待后续开发
			die("DSA 签名方法待后续开发，请先使用MD5签名方式");
		}
		else
		{
			die("支付宝暂不支持" . $sign_type . "类型的签名方式");
		}
		return $sign;
	}

	/**
	 * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
	 * 注意：由于低版本的PHP配置环境不支持远程XML解析，因此必须服务器、本地电脑中装有高版本的PHP配置环境。
	 * 建议本地调试时使用PHP开发软件
	 * $partner 合作身份者ID
	 * return 时间戳字符串
	 */
	public function query_timestamp($partner)
	{
		$URL = "https://mapi.alipay.com/gateway.do?service=query_timestamp&partner=" . $partner;

		$doc = new DOMDocument();
		$doc->load($URL);
		$itemEncrypt_key = $doc->getElementsByTagName("encrypt_key");
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;

		return $encrypt_key;
	}

}