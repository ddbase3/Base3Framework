<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IVectorSearch
 *
 * Defines a generic contract for vector similarity search.
 * Implementations may wrap Qdrant, Pinecone, Weaviate, FAISS, etc.
 */
interface IVectorSearch {

	/**
	 * Search the vector store for the most similar items.
	 *
	 * @param array<float> $vector   The embedding vector to search for.
	 * @param int          $limit    Maximum number of results to return.
	 * @param float|null   $minScore Optional similarity threshold (0..1).
	 *
	 * @return array<int, array<string,mixed>> Results including payload and score.
	 */
	public function search(array $vector, int $limit = 3, ?float $minScore = null): array;
}

