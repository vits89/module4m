<?php

namespace Module4M;

include dirname(__DIR__) . '/constants.php';

use Bitrix\Main\Security\Random;
use Bitrix\Main\UserTable;

/**
 * Содержит обработчики событий.
 */

class CModule4M
{
	/**
	 * Генерирует случайный пароль.
	 * 
	 * @param array $groupIds
	 * @return string
	 */

	protected static function randomPassword($groupIds) {
		global $USER;

		$groupPolicy = \CUser::GetGroupPolicy($groupIds);

		$length = $groupPolicy['PASSWORD_LENGTH'];

		if ($length < 7)
			$length = 7;
		elseif ($length > 10)
			$length = 10;

		$alphabet = Random::ALPHABET_ALPHAUPPER | Random::ALPHABET_ALPHALOWER | Random::ALPHABET_NUM;

		if ($groupPolicy['PASSWORD_PUNCTUATION'] == 'Y')
			$alphabet = Random::ALPHABET_ALL;

		do {
			$password = Random::getStringByAlphabet($length, $alphabet);

			$errors = $USER->CheckPasswordAgainstPolicy($password, $groupPolicy);
		} while (!empty($errors));

		return $password;
	}


	/**
	 * Обработчик события "OnBeforeUserSendPassword".
	 * 
	 * @param array $parameters
	 * @return boolean
	 */

	static function onBeforeUserSendPassword(&$parameters)
	{
		global $USER;

		$arguments = [
			'select' => ['ID', 'EMAIL', 'NAME'],
			'filter' => [
				[
					'LOGIC' => 'OR',
					['LOGIN' => $parameters['LOGIN']],
					['=EMAIL' => $parameters['EMAIL']]
				]
			]
		];

		$user = UserTable::getList($arguments)->fetch();

		if (!empty($user)) {
			$password = self::randomPassword(UserTable::getUserGroupIds($user['ID']));

			$USER->Update($user['ID'], ['PASSWORD' => $password]);

			\Bitrix\Main\Mail\Event::send(
				[
					'EVENT_NAME' => EVENT_TYPE_ID,
					'LID' => $parameters['SITE_ID'],
					'C_FIELDS' => [
						'EMAIL' => $user['EMAIL'],
						'NAME' => $user['NAME'],
						'PASSWORD' => $password
					]
				]
			);

			return false;
		} else {
			return true;
		}
	}
}
