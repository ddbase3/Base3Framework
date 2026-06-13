<?php declare(strict_types=1);

namespace Base3\Xrm;

class XrmFilter {

	public string $attr = '';
	public string $op = '';
	public mixed $val = null;
	public ?int $offset = null;
	public ?int $limit = null;

	public function __construct(?string $attr = null, ?string $op = null, mixed $val = null, ?int $offset = null, ?int $limit = null) {
		if ($attr !== null) {
			$this->attr = $attr;
		}
		if ($op !== null) {
			$this->op = $op;
		}
		$this->val = $val;
		$this->offset = $offset;
		$this->limit = $limit;
	}

	public function fromJson(string $json): void {
		$data = json_decode($json, true);
		$this->fromData($data);
	}

	public function fromData($data): void {
		$d = is_object($data) ? (array) $data : $data;

		$this->attr = (string)($d['attr'] ?? '');
		$this->op   = (string)($d['op'] ?? '');

		if ($this->attr === '') {
			throw new \InvalidArgumentException('XrmFilter: missing "attr" in data.');
		}
		if ($this->op === '') {
			throw new \InvalidArgumentException('XrmFilter: missing "op" in data.');
		}

		if ($this->attr === 'conj' && $this->op !== 'not') {
			$this->val = [];
			foreach (($d['val'] ?? []) as $val) {
				$filter = new XrmFilter();
				$filter->fromData($val);
				$this->val[] = $filter;
			}
		} else {
			$this->val = $d['val'] ?? null;
		}

		$this->offset = isset($d['offset']) ? (int)$d['offset'] : null;
		$this->limit  = isset($d['limit'])  ? (int)$d['limit']  : null;
	}
}
