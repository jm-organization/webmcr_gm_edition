<?php
/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 08.07.2018
 * @Time         : 0:00
 *
 * @Documentation:
 */

namespace mcr\validation;


use mcr\http\redirect_response;
use Particle\Validator\Chain;
use Particle\Validator\Validator as BaseValidator;

trait validator
{
	private $rules = [
		'array' => 'isArray',
		'alpha' => 'alpha',
		'between' => 'between',
		'bool' => 'bool',
		'datetime' => 'datetime',
		'digits' => 'digits',
		'email' => 'email',
		'equals' => 'equals',
		'float' => 'float',
		'greater_than' => 'greaterThan',
		'in_array' => 'inArray',
		'integer' => 'integer', //Rule\Integer::STRICT => integer:true
		'json' => 'json',
		'length' => 'length', // lengthBetween
		'less_than' => 'lessThan',
		'numeric' => 'numeric',
		'regex' => 'regex',
		'string' => 'string',
		'url' => 'url',
		'uuid' => 'uuid', // Uuid::UUID_V4 | Uuid::UUID_NIL
	];

	/**
	 * @param array  $value
	 * @param array  $rules
	 *
	 * @param string $route
	 *
	 * @return \Particle\Validator\ValidationResult
	 * @throws validation_exception
	 * @throws \engine\http\routing\url_builder_exception
	 */
	public function validate(array $value, array $rules, $route = 'home')
	{
		$validator = new BaseValidator();

		$this->make_validator_rules($validator, $rules);

		$result = $validator->validate($value);

		if ($result->isNotValid()) {
			$validator_messages = $result->getMessages();

			$redirect = redirect();

			foreach ($validator_messages as $key => $messages) {
				foreach ($messages as $message) {
					$redirect->with('message', [
						'text' => $message,
						'type' => 2
					]);
				}
			}

			return $redirect->route($route);
		}

	}

	/**
	 * @param BaseValidator $validator
	 * @param array         $validator_rules
	 *
	 * @throws validation_exception
	 */
	private function make_validator_rules(BaseValidator &$validator, array $validator_rules)
	{
		foreach ($validator_rules as $validate_item => $item_rules) {
			$required_rule_occurrence = strstr($item_rules, 'required', true);

			// Если указано правило required и оно стоит на первом месте
			if ($required_rule_occurrence === '') {

				$vltr = $validator->required($validate_item);

				// исключаем required из набора правил
				$item_rules = preg_replace('/required\|?/', '', $item_rules);
				// Если не указан required вовсе
			} elseif ($required_rule_occurrence === false) {

				$vltr = $validator->optional($validate_item);

				$item_rules = preg_replace('/optional\|?/', '', $item_rules);
			} else {
				throw new validation_exception('validator::validate(): Error when parse rules: required isn`t first');
			}

			// Если есть дополнительные правила, то создаём их.
			if ($item_rules != '') {
				$this->make_rules_for_item($vltr, $item_rules);
			}
		}
	}

	/**
	 * @param Chain $validator
	 * @param       $item_rules
	 *
	 * @throws validation_exception
	 */
	private function make_rules_for_item(Chain &$validator, $item_rules)
	{
		$item_rules = explode('|', $item_rules);

		foreach ($item_rules as $rule) {
			$rule_and_arguments = explode(':', $rule);
			$validator_rule = null;
			$arguments = [];

			switch (count($rule_and_arguments)) {
				case 2:
					list($rule, $arguments) = $rule_and_arguments;

					$this->set_rule($validator_rule, $rule);
					if (!empty($validator_rule)) {
						$arguments = explode(',', $arguments);
					}

					break;
				case 1:
					$this->set_rule($validator_rule, $rule_and_arguments[0]);

					break;

				default: throw new validation_exception('validator::validate(): Undefined rule: ' . $rule); break;
			}

			$validator->$validator_rule(...$arguments);
		}
	}

	/**
	 * @param $validation_rule
	 * @param $rule
	 *
	 * @throws validation_exception
	 */
	private function set_rule(&$validation_rule, $rule)
	{
		if (array_key_exists($rule, $this->rules)) {
			$validation_rule = $this->rules[$rule];
		} else {
			throw new validation_exception('validator::validate(): Unknown rule: ' . $rule);
		}
	}
}