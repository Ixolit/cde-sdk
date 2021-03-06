<?php

namespace Ixolit\CDE\Form;

use Ixolit\CDE\Validator\MaximumLengthValidator;
use Ixolit\CDE\Validator\PatternValidator;
use Ixolit\CDE\Validator\SingleLineValidator;

/**
 * This class was ported from the Piccolo form library with permission.
 */
class TextField extends FormField {

	const TYPE_TEXT = 'text';

	private $pattern;
	private $patternValidator;
	private $maxLength = null;
	private $maxLengthValidator = null;

	public function __construct($name) {
		parent::__construct($name);

		$this->addValidator(new SingleLineValidator());
	}

	public function setMaximumLength($maxLength) {
		$this->maxLength = $maxLength;
		if ($this->maxLengthValidator) {
			$this->removeValidator($this->maxLengthValidator);
		}
		if (!\is_null($maxLength)) {
			$this->maxLengthValidator = new MaximumLengthValidator((int)$maxLength);
			$this->addValidator($this->maxLengthValidator);
		} else {
			$this->maxLengthValidator = null;
		}

		return $this;
	}

	public function setPattern($pattern) {
		$this->pattern = $pattern;
		if ($this->patternValidator) {
			$this->removeValidator($this->patternValidator);
		}
		if (!\is_null($pattern)) {
			$this->patternValidator = new PatternValidator($pattern);
			$this->addValidator($this->patternValidator);
		} else {
			$this->patternValidator = null;
		}
	}

	public function getType() {
		return self::TYPE_TEXT;
	}
}