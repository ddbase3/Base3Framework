<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class XrmFilterTest extends TestCase {

	public function testConstructorPreservesTypedFields(): void {
		$filter = new \Base3\Xrm\XrmFilter('type', 'eq', 'page', 5, 10);
		$this->assertSame('type', $filter->attr);
		$this->assertSame('eq', $filter->op);
		$this->assertSame('page', $filter->val);
		$this->assertSame(5, $filter->offset);
		$this->assertSame(10, $filter->limit);
	}

	public function testNestedConjunctionRestoresFilterObjects(): void {
		$filter = new \Base3\Xrm\XrmFilter();
		$filter->fromJson('{"attr":"conj","op":"and","val":[{"attr":"name","op":"eq","val":"A"},{"attr":"type","op":"eq","val":"B"}],"offset":"2","limit":"7"}');
		$this->assertCount(2, $filter->val);
		$this->assertInstanceOf(\Base3\Xrm\XrmFilter::class, $filter->val[0]);
		$this->assertSame('A', $filter->val[0]->val);
		$this->assertSame('type', $filter->val[1]->attr);
		$this->assertSame(2, $filter->offset);
		$this->assertSame(7, $filter->limit);
	}

	public function testNegationPreservesUnparsedValue(): void {
		$filter = new \Base3\Xrm\XrmFilter();
		$filter->fromData((object)['attr' => 'conj', 'op' => 'not', 'val' => ['attr' => 'type']]);
		$this->assertSame(['attr' => 'type'], $filter->val);
		$this->assertNull($filter->limit);
	}

	public function testRejectsMissingAttribute(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Xrm\XrmFilter())->fromData(['op' => 'eq']);
	}

	public function testRejectsMissingOperator(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Xrm\XrmFilter())->fromData(['attr' => 'name']);
	}

}
