<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

final class XrmSearchFixture {

	public array $ids=['one','two'];
	public array $data=[['id'=>'one','name'=>'Entry']];
	public array $calls=[];
	public function getEntriesIntern($filter, $idsonly=false) {
		$this->calls[]=['ids',$filter,$idsonly];
		return $this->ids;
	}
	public function getFilteredEntries($filter) {
		$this->calls[]=['data',$filter];
		return $this->data;
	}
}
