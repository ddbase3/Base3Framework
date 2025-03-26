<?php declare(strict_types=1);

namespace Base3\Api;

interface IComparable {

	/* Sortierung von Objekt-Arrays - Liefert -1, wenn dieses Objekt kleiner als das übergebene Objekt ist, 0 wenn gleich und 1 wenn größer */
	public function compareTo($o);

}
