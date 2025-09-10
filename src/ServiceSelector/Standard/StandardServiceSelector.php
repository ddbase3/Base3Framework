<?php declare(strict_types=1);

namespace Base3\ServiceSelector\Standard;

use Base3\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\ServiceSelector\AbstractServiceSelector;

/**
 * Standard service selector for single-language applications.
 */
class StandardServiceSelector extends AbstractServiceSelector {

	public function __construct(protected IContainer $container) {
		parent::__construct($container);
	}
}
