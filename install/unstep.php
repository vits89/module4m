<?php

if (!check_bitrix_sessid()) 
	return;

echo CAdminMessage::ShowNote('Мой модуль удалён.');
