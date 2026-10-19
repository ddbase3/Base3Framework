<?php declare(strict_types=1);

namespace Base3\Authorization\Dto;

/** What the native authority can expose through the common rights administration UI. */
final readonly class AuthorizationCapabilities {

	/**
	 * @param string $authority Registered authorization authority
	 * @param bool $canRead Grants and permissions may be inspected by the current operator
	 * @param bool $canWrite The current operator may change supported native grants
	 * @param bool $supportsHierarchy The native authority supports resource inheritance
	 * @param list<string> $subjectKinds Subject kinds for which this authority exposes native grants
	 * @param list<string> $limitations Human-readable description of unsupported operations
	 */
	public function __construct(
		public string $authority,
		public bool $canRead,
		public bool $canWrite,
		public bool $supportsHierarchy,
		public array $subjectKinds,
		public array $limitations = []
	) {}
}
