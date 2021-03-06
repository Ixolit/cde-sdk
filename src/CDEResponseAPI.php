<?php

namespace Ixolit\CDE;

use Ixolit\CDE\Exceptions\CDEFeatureNotSupportedException;
use Ixolit\CDE\Exceptions\CookieSetFailedException;
use Ixolit\CDE\Exceptions\HeaderSetFailedException;
use Ixolit\CDE\Exceptions\InvalidStatusCodeException;
use Ixolit\CDE\Interfaces\ResponseAPI;
use Psr\Http\Message\ResponseInterface;

class CDEResponseAPI implements ResponseAPI {
	/**
	 * {@inheritdoc}
	 */
	public function redirectTo($location, $permanent = false) {
		if (!\function_exists('redirectTo')) {
			throw new CDEFeatureNotSupportedException('redirectTo');
		}
		redirectTo($location, $permanent);
	}

	/**
	 * {@inheritdoc}
	 */
	public function redirectToPage($page, $lang = null, $permanent = false, $abortRendering = true) {
		if (!\function_exists('redirectToPage')) {
			throw new CDEFeatureNotSupportedException('redirectToPage');
		}
		redirectToPage($page, $lang, $permanent);
		if ($abortRendering) {
			exit;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function setContentType($contentType) {
		if (!\function_exists('setContentType')) {
			throw new CDEFeatureNotSupportedException('setContentType');
		}
		\setContentType($contentType);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setStatusCode($statusCode) {
		if (!\function_exists('setStatusCode')) {
			throw new CDEFeatureNotSupportedException('setStatusCode');
		}
		if (!\setStatusCode($statusCode)) {
			throw new InvalidStatusCodeException($statusCode);
		}
	}

	private function setCookieInternal($name, $value, $maxAge = 0, $path = null, $domain = null, $secure = false, $httponly = false) {
        if ($domain === null) {
            $domain = '.' . str_replace('www.', '', getVhost());
        }
        return \setCookieAdvanced($name, $value, $maxAge, $path, $domain, $secure, $httponly);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setCookie($name, $value, $maxAge = 0, $path = null, $domain = null, $secure = false, $httponly = false) {
		if (!\function_exists('setCookie')) {
			throw new CDEFeatureNotSupportedException('setCookie');
		}
		if (!$this->setCookieInternal($name, $value, $maxAge, $path, $domain, $secure, $httponly)) {
			throw new CookieSetFailedException($name, $value, $maxAge, $path, $domain, $secure, $httponly);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setHeader($name, $value) {
		if (!\function_exists('setHeader')) {
			throw new CDEFeatureNotSupportedException('setHeader');
		}
		if (!\setHeader($name, $value)) {
			throw new HeaderSetFailedException($name, $value);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function sendPSR7(ResponseInterface $response) {
		$this->setStatusCode($response->getStatusCode());
		$headers = $response->getHeaders();

		foreach ($headers as $header => $content) {
			switch (strtolower($header)) {
				case 'content-type':
					$this->setContentType(\implode(',', $content));
					break;
				case 'location':
					$this->redirectTo(\implode(',', $content), ($response->getStatusCode() == 301 ? true : false));
					break;
				case 'set-cookie':
					foreach ($content as $cookie) {
						$cookieData = [];
						$parts      = explode(';', $cookie);
						$maxAge     = 0;
						foreach ($parts as $part) {
							$partComponents = explode('=', $part);
							$key            = urldecode(trim($partComponents[0]));
							if (isset($partComponents[1])) {
								$value = urldecode(trim($partComponents[1]));
							} else {
								$value = true;
							}
							switch (strtolower($key)) {
								case 'expires':
									$maxAge = strtotime($value) - time();
									break;
								case 'domain':
								case 'path':
								case 'secure':
								case 'httponly':
									//ignore
									break;
								default:
									$cookieData[$key] = $value;
									break;
							}
						}
						foreach ($cookieData as $key => $value) {
							$this->setCookie($key, $value, $maxAge);
						}
					}
					break;
				default:
					throw new CDEFeatureNotSupportedException('Sending header type ' . $header .
						' is not supported');
			}
		}

		echo $response->getBody();
		exit;
	}
}
