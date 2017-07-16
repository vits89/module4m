<?php

include dirname(__DIR__) . '/constants.php';

use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;


/**
 * Используется для установки/удаления модуля.
 */

class module4m extends CModule
{
	/**
	 * Инициализирует свойства модуля.
	 */

	function __construct()
	{
		$this->MODULE_DESCRIPTION = 'Мой первый модуль.';
		$this->MODULE_ID = 'module4m';
		$this->MODULE_NAME = 'Мой модуль';
		$this->PARTNER_NAME = 'Виталий Сорокин';

		include __DIR__ . '/version.php';

		if (is_array($arModuleVersion)) {
			if (array_key_exists('VERSION', $arModuleVersion))
				$this->MODULE_VERSION = $arModuleVersion['VERSION'];

			if (array_key_exists('VERSION_DATE', $arModuleVersion))
				$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}
	}


	/**
	 * Установливает модуль.
	 * 
	 * @global object $APPLICATION
	 */

	function DoInstall()
	{
		global $APPLICATION;

		$this->InstallEvents();

		EventManager::getInstance()->registerEventHandler('main', 'OnBeforeUserSendPassword', $this->MODULE_ID, '\Module4M\CModule4M', 'onBeforeUserSendPassword');

		ModuleManager::registerModule($this->MODULE_ID);

		$APPLICATION->IncludeAdminFile('Установка моего модуля', dirname(__DIR__) . '/install/step.php');
	}


	/**
	 * Удаляет модуль.
	 * 
	 * @global object $APPLICATION
	 */

	function DoUninstall()
	{
		global $APPLICATION;

		EventManager::getInstance()->unRegisterEventHandler('main', 'OnBeforeUserSendPassword', $this->MODULE_ID, '\Module4M\CModule4M', 'onBeforeUserSendPassword');

		$this->UnInstallEvents();

		ModuleManager::unRegisterModule($this->MODULE_ID);

		$APPLICATION->IncludeAdminFile('Удаление моего модуля', dirname(__DIR__) . '/install/unstep.php');
	}


	/**
	 * Устанавливает тип и шаблон сообщения события почтовой системы.
	 * 
	 * @return boolean True в любом случае.
	 */

	function InstallEvents()
	{
		CEventType::Add(
			[
				'LID' => 'ru',
				'EVENT_NAME' => EVENT_TYPE_ID,
				'NAME' => 'Новый пароль',
				'DESCRIPTION' => '
					#NAME# - имя пользователя
					#PASSWORD# - пароль
				'
			]
		);

		$siteIds = \Bitrix\Main\SiteTable::getList(['select' => ['LID']])->fetchAll();

		(new CEventMessage())->Add(
			[
				'ACTIVE' => 'Y',
				'EVENT_NAME' => EVENT_TYPE_ID,
				'LID' => array_column($siteIds, 'LID'),
				'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO' => '#EMAIL#',
				'BCC' => '',
				'SUBJECT' => '#SITE_NAME#: Ваш новый пароль',
				'BODY_TYPE' => 'text',
				'MESSAGE' => 'Здравствуйте, #NAME#! Ваш новый пароль: #PASSWORD#'
			]
		);

		return true;
	}


	/**
	 * Удаляет шаблон сообщения и тип события почтовой системы.
	 * 
	 * @return boolean True в любом случае.
	 */

	function UnInstallEvents()
	{
		$message = CEventMessage::GetList($by = '', $order = '', ['TYPE_ID' => EVENT_TYPE_ID])->Fetch();

		CEventMessage::Delete($message['ID']);

		CEventType::Delete(EVENT_TYPE_ID);

		return true;
	}
}
