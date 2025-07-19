<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface for embedding models (e.g. OpenAI, Ollama, HuggingFace).
 *
 * Accepts text input and returns one or more embeddings (float vectors).
 */
interface IAiEmbeddingModel {

	/**
	 * Encodes one or multiple texts into embedding vectors.
	 *
	 * @param string[] $texts Text inputs (1..n)
	 * @return float[][] List of embedding vectors (one per input text)
	 */
	public function embed(array $texts): array;

	/**
	 * Sets model options like model name, endpoint, etc.
	 *
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options): void;

	/**
	 * Optional: get model options (e.g. for debugging or introspection).
	 *
	 * @return array
	 */
	public function getOptions(): array;
}

