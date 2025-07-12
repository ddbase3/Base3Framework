<?php declare(strict_types=1);

namespace Base3\ServiceSelector\Standard;

use Base3\ServiceSelector\AbstractServiceSelector;

/**
 * Standard service selector for single-language applications.
 */
class StandardServiceSelector extends AbstractServiceSelector {

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
}

/*
// .htaccess:

<files *.ini>
order deny,allow
deny from all
</files>

RewriteEngine On
RewriteRule ^assets/ - [L]
RewriteRule ^tpl/ - [L]
RewriteRule ^userfiles/ - [L]
RewriteRule ^favicon.ico - [L]
RewriteRule ^robots.txt - [L]
RewriteRule ^$ index.html
RewriteRule ^(.+)/(.+)\.(.+) index.php?app=$1&name=$2&out=$3 [L,QSA]
RewriteRule ^(.+)\.(.+) index.php?app=&name=$1&out=$2 [L,QSA]

*/
