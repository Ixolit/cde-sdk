<?php

namespace Ixolit\CDE\View\Html;


/**
 * Generic HTML element
 *
 * Manages name and attributes
 *
 * @package Ixolit\CDE\View\Html
 */
abstract class Element implements Html {

	// region HTML code

	const TAG_EMPTY = 0;
	const TAG_START = 1;
	const TAG_END = 2;

	const NAME_DIV = 'div';
	const NAME_FORM = 'form';
	const NAME_LABEL = 'label';
	const NAME_INPUT = 'input';
	const NAME_SELECT = 'select';
	const NAME_OPTION = 'option';
	const NAME_META = 'meta';
	const NAME_SPAN = 'span';

	const ATTRIBUTE_NAME_ID = 'id';
	const ATTRIBUTE_NAME_CLASS = 'class';

	const ATTRIBUTE_NAME_ACTION = 'action';
	const ATTRIBUTE_NAME_METHOD = 'method';
	const ATTRIBUTE_NAME_NAME = 'name';
	const ATTRIBUTE_NAME_TYPE = 'type';
	const ATTRIBUTE_NAME_VALUE = 'value';
	const ATTRIBUTE_NAME_FOR = 'for';
	const ATTRIBUTE_NAME_CHECKED = 'checked';
	const ATTRIBUTE_NAME_SELECTED = 'selected';
	const ATTRIBUTE_NAME_CONTENT = 'content';
	const ATTRIBUTE_NAME_HTTPEQUIV = 'http-equiv';
	const ATTRIBUTE_NAME_PROPERTY = 'property';

	const ATTRIBUTE_VALUE_TYPE_HIDDEN = 'hidden';
	const ATTRIBUTE_VALUE_TYPE_TEXT = 'text';
	const ATTRIBUTE_VALUE_TYPE_EMAIL = 'email';
	const ATTRIBUTE_VALUE_TYPE_PASSWORD = 'password';
	const ATTRIBUTE_VALUE_TYPE_CHECKBOX = 'checkbox';
	const ATTRIBUTE_VALUE_TYPE_RADIO = 'radio';

	// endregion

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	private $attributes;

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Creates and initializes a new element
	 *
	 * @param string $name
	 * @param array $attributes
	 */
	public function __construct($name, $attributes = []) {
		$this->name = $name;
		$this->attributes = $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public function __toString() {
		return $this->getCode();
	}

	/**
	 * Returns the element's code representation
	 *
	 * @return string
	 */
	public abstract function getCode();

	/**
	 * Returns a tag
	 *
	 * @param int $type
	 *
	 * @return string
	 */
	protected function getTag($type = self::TAG_EMPTY) {

		// TODO: validate tag & attribute names?

		$code = '<';

		if ($type === self::TAG_END) {
			$code .= '/';
		}

		$code .= \strtolower($this->getName());

		if (($type === self::TAG_EMPTY) || ($type === self::TAG_START)) {
			foreach ($this->getAttributes() as $name => $value) {
				$code .= ' ' . \strtolower($name) . '="' . \html($value) . '"';
			}
		}

		if ($type === self::TAG_EMPTY) {
			$code .= ' /';
		}

		$code .= '>';

		return $code;
	}

	/**
	 * Sets an element's attributes, optionally keep existing ones
	 *
	 * @param array $attributes
	 * @param bool $keep
	 *
	 * @return static
	 */
	public function setAttributes($attributes, $keep = false) {
		foreach ($attributes as $name => $value) {
			$this->setAttribute($name, $value, $keep);
		}
		return $this;
	}

	/**
	 * Sets an element's attribute, optionally keep existing ones
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param bool $keep
	 *
	 * @return static
	 */
	public function setAttribute($name, $value, $keep = false) {
		if (!($keep && $this->hasAttribute($name))) {
			$this->attributes[$name] = $value;
		}
		return $this;
	}

	/**
	 * Deletes an element's attribute
	 *
	 * @param $name
	 *
	 * @return static
	 */
	public function deleteAttribute($name) {
		unset($this->attributes[$name]);
		return $this;
	}

	/**
	 * Sets or deletes an element's boolean attribute
	 *
	 * @param string $name
	 * @param bool $value
	 *
	 * @return static
	 */
	public function booleanAttribute($name, $value) {
		if ($value) {
			return $this->setAttribute($name, $name);
		}
		else {
			return $this->deleteAttribute($name);
		}
	}

	private function hasAttribute($name) {
		return isset($this->attributes[$name]);
	}

	/**
	 * Shortcut to set the element's id attribute
	 *
	 * @param mixed $value
	 *
	 * @return static
	 */
	public function setId($value) {
		return $this->setAttribute(self::ATTRIBUTE_NAME_ID, $value);
	}

	/**
	 * Add the given classes to the element's attribute, appending to existing ones
	 *
	 * @param string[]|string $class
	 *
	 * @return static
	 */
	public function addClass($class) {
		if (is_array($class)) {
			foreach ($class as $item) {
				$this->addClass($item);
			}
		}
		elseif (isset($class)) {
			if (empty($this->attributes[self::ATTRIBUTE_NAME_CLASS])) {
				$this->attributes[self::ATTRIBUTE_NAME_CLASS] = $class;
			}
			else {
				$this->attributes[self::ATTRIBUTE_NAME_CLASS] .= ' ' . $class;
			}
		}
		return $this;
	}
}