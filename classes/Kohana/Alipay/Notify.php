<?php

defined('SYSPATH') or die('No direct access allowed.');

class Kohana_Alipay_Notify extends Alipay {

	/**
	 * @var string 默认通知类型
	 */
	public static $default = 'notify';

	/**
	 * @var array notify实例数组
	 */
	public static $instances = array();

	/**
	 * @string 通知类型
	 */
	protected $notify_type = '';

	/**
	 * 单件模式获得一个notify实例
	 *
	 * @param string 合作ID
	 * @param string 验证串
	 * @param string 加密方式
	 */
	public static function instance($partner, $key, $sign_type = '')
	{
		//判断当前要的实例是否存在
		if (isset(Alipay_Notify::$instances[$type]))
		{
			return Alipay_Notify::$instances[$type];
		}

		//不存在则生成新的实例
		$notify_class = 'Alipay_Notify';
		Alipay_Notify::$instances[$type] = new $notify_class($partner, $key, $sign_type = '');

		//返回实例
		return Alipay_Notify::$instances[$type];
	}

	/**
	 * 构造方法
	 * 
	 * @param string 合作ID
	 * @param string 验证串
	 * @param string 加密方式
	 */
	public function __construct($partner, $key, $sign_type = '')
	{
		parent::__construct();
		$this->transport = $this->config['transport'];
		if ($this->transport == "https")
		{
			$this->gateway = "https://www.alipay.com/cooperate/gateway.do?";
		}
		else
		{
			$this->gateway = "http://notify.alipay.com/trade/notify_query.do?";
		}

		$this->partner = $partner;
		$this->key = $key;
		$this->sign_type = empty($sign_type) ? $this->config['sign_type'] : $sign_type;
		$this->input_charset = $this->config['input_charset'];
	}

	/**
	 * 验证支付返回结果
	 */
	public function verify_return()
	{
		$this->parameter = $_GET;
		return $this->_do_verify();
	}

	/**
	 * 验证支付通知结果
	 */
	public function verify_notify()
	{
		$this->parameter = $_POST;
		return $this->_do_verify();
	}

	/**
	 * 执行验证
	 *
	 * @return boolean
	 */
	private function _do_verify()
	{
		//需要验证的参数为空，则返回FALSE
		if (empty($this->parameter))
			return FALSE;

		//生成验证地址
		if ($this->transport == "https")
		{
			$verify_url = $this->gateway . "service=notify_verify" .
				"&partner=" . $this->partner .
				"&notify_id=" . $this->parameter['notify_id'];
		}
		else
		{
			$verify_url = $this->gateway . "partner=" . $this->partner .
				"&notify_id=" . $this->parameter['notify_id'];
		}

		//获取远程服务器ATN结果，验证是否是支付宝服务器发来的请求
		$verify_result = $this->get_verify($verify_url);

		//对$parameter去空
		$post_or_get = $this->para_filter($this->parameter);

		//对$parameter数据排序
		$sort_post_or_get = $this->arg_sort($post_or_get);

		//生成签名
		$mysign = $this->build_mysign($sort_post_or_get, $this->key, $this->sign_type);
		if (preg_match("/true$/i", $verify_result) && $mysign == $this->parameter["sign"])
			return TRUE;

		return FALSE;
	}

	/**
	 * 获取远程服务器ATN结果
	 *
	 * @param string 指定URL路径地址
	 * @param int 超时时间
	 * 
	 * @return 服务器ATN结果集
	 */
	protected function get_verify($url, $time_out = 60)
	{
		$urlarr = parse_url($url);
		$errno = "";
		$errstr = "";
		$transports = "";

		if ($urlarr["scheme"] == "https")
		{
			$transports = "ssl://";
			$urlarr["port"] = "443";
		}
		else
		{
			$transports = "tcp://";
			$urlarr["port"] = "80";
		}

		$fp = @fsockopen($transports . $urlarr['host'], $urlarr['port'], $errno, $errstr, $time_out);
		if ( ! $fp)
		{
			die("ERROR: $errno - $errstr<br />\n");
		}
		else
		{
			fputs($fp, "POST " . $urlarr["path"] . " HTTP/1.1\r\n");
			fputs($fp, "Host: " . $urlarr["host"] . "\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: " . strlen($urlarr["query"]) . "\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $urlarr["query"] . "\r\n\r\n");
			while ( ! feof($fp))
			{
				$info[] = @fgets($fp, 1024);
			}
			fclose($fp);
			$info = implode(",", $info);
			return $info;
		}
	}

}