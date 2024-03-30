<?php

namespace Xrm;

class XrmFilter {

	// string
	public $attr;

	// string
	public $op;

	// mixed (z.B. Array von Filtern bei conj/or oder conj/and)
	public $val;

	// int, null bei kein Offset (oder Offset 0)
	public $offset;

	// int, null bei kein Limit (oder bis zum Ende)
	public $limit;

	public function __construct($attr = null, $op = null, $val = null, $offset = null, $limit = null) {
		$this->attr = $attr;
		$this->op = $op;
		$this->val = $val;
		$this->offset = $offset;
		$this->limit = $limit;
	}

	public function fromJson($json) {
		$data = json_decode($json, true);
		$this->fromData($data);
	}

	public function fromData($data) {
		$d = is_object($data) ? (array) $data : $data;
		$this->attr = $d["attr"];
		$this->op = $d["op"];
		if ($this->attr == "conj" && $this->op != "not") {
			$this->val = array();
			foreach ($d["val"] as $val) {
				$filter = new XrmFilter;
				$filter->fromData($val);
				$this->val[] = $filter;
			}
		} else {
			$this->val = $d["val"];
		}
		if (isset($d["offset"])) $this->offset = $d["offset"];
		if (isset($d["limit"])) $this->limit = $d["limit"];
	}

}
