<?php declare(strict_types=1);

namespace Language\Api;

interface ILanguage {

	public function getLanguage();
	public function setLanguage($language);
	public function getLanguages();

}
