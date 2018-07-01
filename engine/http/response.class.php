<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 01.07.2018
 * @Time         : 16:46
 *
 * @Documentation:
 */

namespace mcr\http;


if (!defined("MCR")) {
	exit("Hacking Attempt!");
}

class response
{
	private $headers = array();

	private $status_code = 200;

	/**
	 * Status codes translation table.
	 *
	 * The list of codes is complete according to the
	 * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
	 * (last updated 2016-03-01).
	 *
	 * Unless otherwise noted, the status code is defined in RFC2616.
	 *
	 * @var array
	 */
	public static $status_texts = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',            // RFC2518
		103 => 'Early Hints',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',          // RFC4918
		208 => 'Already Reported',      // RFC5842
		226 => 'IM Used',               // RFC3229
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',    // RFC7238
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Payload Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',                                               // RFC2324
		421 => 'Misdirected Request',                                         // RFC7540
		422 => 'Unprocessable Entity',                                        // RFC4918
		423 => 'Locked',                                                      // RFC4918
		424 => 'Failed Dependency',                                           // RFC4918
		425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
		426 => 'Upgrade Required',                                            // RFC2817
		428 => 'Precondition Required',                                       // RFC6585
		429 => 'Too Many Requests',                                           // RFC6585
		431 => 'Request Header Fields Too Large',                             // RFC6585
		451 => 'Unavailable For Legal Reasons',                               // RFC7725
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',                                     // RFC2295
		507 => 'Insufficient Storage',                                        // RFC4918
		508 => 'Loop Detected',                                               // RFC5842
		510 => 'Not Extended',                                                // RFC2774
		511 => 'Network Authentication Required',                             // RFC6585
	);

	private $status_text = 'OK';

	private $content = '';

	private $charset = 'utf8';

	public function __construct($content, $charset = 'UTF-8', $status = 200, array $headers = array())
	{
		$this->headers = $headers;

		$this->set_status_code($status);
		$this->set_status_text();

		$this->charset = $charset;

		$this->set_content($content);
	}

	public function set_status_code($code)
	{
		if ($code < 100 || $code >= 600) {
			throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
		}

		$this->status_code = $code;
	}

	public function set_status_text()
	{
		if (array_key_exists($this->status_code, self::$status_texts)) {
			$this->status_text = self::$status_texts[$this->status_code];
		} else {
			$this->status_text = '';
		}
	}

	public function set_content($content)
	{
		if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
			throw new \UnexpectedValueException('The Response content must be a string or object implementing __toString(), "'.gettype($content).'" given.');
		}
		$this->content = (string) $content;
	}

	private function prepare()
	{
		if ($this->isInformational() || in_array($this->status_code, array(204, 304))) {
			$this->content = '';
		}

		// Fix Content-Type
		$charset = $this->charset ?: 'UTF-8';
		if (!array_key_exists('Content-Type', $this->headers)) {
			$this->headers['Content-Type'] = 'text/html; charset='.$charset;
		} elseif (0 === strpos($this->headers['Content-Type'], 'text/') && false === strpos($this->headers['Content-Type'], 'charset')) {
			$this->headers['Content-Type'] = $this->headers['Content-Type'].'; charset='.$charset;
		}

		// Fix Content-Length
		if (array_key_exists('Transfer-Encoding', $this->headers)) {
			unset($this->headers['Content-Length']);
		}
	}

	public function send_headers()
	{
		// headers have already been sent by the developer
		if (headers_sent()) {
			return;
		}

		$this->prepare();

		// status
		header(sprintf('HTTP/%s %s %s', $_SERVER['SERVER_PROTOCOL'], $this->status_code, $this->status_text));

		// headers
		foreach ($this->headers as $name => $value) {
			header($name.': '.$value);
		}
	}

	public function send()
	{
		$this->send_headers();
		echo $this->content;

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
	}

	private function isInformational()
	{
		return $this->status_code >= 100 && $this->status_code < 200;
	}
}