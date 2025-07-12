<?php declare(strict_types=1);

namespace Base3\ServiceSelector\LangBased;

use Base3\ServiceSelector\AbstractServiceSelector;

/**
 * Language-aware service selector for multi-language applications.
 *
 * Uses the "data" parameter to switch language context.
 */
class LangBasedServiceSelector extends AbstractServiceSelector {

	private static ?self $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if (self::$instance === null) self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Sets the language from the "data" request parameter if valid (e.g. "en", "de").
	 *
	 * @param string $data Request data parameter
	 */
	protected function handleLanguage(string $data): void {
		if (strlen($data) === 2) {
			$language = $this->servicelocator->get('language');
			$language->setLanguage($data);
		}
	}
}

/*
// .htaccess:

<files *.ini>
order deny,allow
deny from all
</files>

RewriteEngine On
RewriteRule ^docs/ - [L]
RewriteRule ^assets/ - [L]
RewriteRule ^tpl/ - [L]
RewriteRule ^userfiles/ - [L]
RewriteRule ^favicon.ico - [L]
RewriteRule ^robots.txt - [L]
RewriteRule ^$ index.html
RewriteRule ^(.+)/(.+)\.(.+) index.php?data=$1&name=$2&out=$3 [L,QSA]
RewriteRule ^(.+)\.(.+) index.php?name=$1&out=$2 [L,QSA]

*/
