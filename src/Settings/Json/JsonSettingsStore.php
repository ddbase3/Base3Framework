<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Settings\Json;

use Base3\Configuration\Api\IConfiguration;
use Base3\Settings\Api\ISettingsStore;
use JsonException;
use RuntimeException;

/**
 * Class JsonSettingsStore
 *
 * Stores grouped, named settings datasets in a JSON file.
 *
 * File location:
 * - <directories.data>/cnf/settings.json
 */
class JsonSettingsStore implements ISettingsStore {

	private IConfiguration $configuration;

	/**
	 * @var array<string, array<string, array>>
	 */
	private array $data = [];

	private string $filePath = '';

	private bool $dirty = false;

	public function __construct(IConfiguration $configuration) {
		$this->configuration = $configuration;
		$this->filePath = $this->buildFilePath();
		$this->reload();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSettings(string $group, string $name, array $default = []): array {
		if(!$this->hasSettings($group, $name)) {
			return $default;
		}

		return $this->data[$group][$name];
	}

	/**
	 * {@inheritDoc}
	 */
	public function setSettings(string $group, string $name, array $settings): void {
		if($group === '') {
			throw new RuntimeException('Settings group must not be empty.');
		}

		if($name === '') {
			throw new RuntimeException('Settings name must not be empty.');
		}

		if(
			isset($this->data[$group][$name]) &&
			$this->data[$group][$name] === $settings
		) {
			return;
		}

		if(!isset($this->data[$group]) || !is_array($this->data[$group])) {
			$this->data[$group] = [];
		}

		$this->data[$group][$name] = $settings;
		$this->dirty = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasSettings(string $group, string $name): bool {
		return isset($this->data[$group][$name]) && is_array($this->data[$group][$name]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeSettings(string $group, string $name): void {
		if(!$this->hasSettings($group, $name)) {
			return;
		}

		unset($this->data[$group][$name]);

		if(empty($this->data[$group])) {
			unset($this->data[$group]);
		}

		$this->dirty = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function save() {
		$directory = dirname($this->filePath);

		if(!is_dir($directory)) {
			if(!mkdir($directory, 0775, true) && !is_dir($directory)) {
				throw new RuntimeException('Failed to create settings directory: ' . $directory);
			}
		}

		try {
			$json = json_encode(
				$this->data,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
			);
		}
		catch(JsonException $e) {
			throw new RuntimeException('Failed to encode settings JSON: ' . $e->getMessage(), 0, $e);
		}

		$tempFile = $this->filePath . '.tmp';

		if(file_put_contents($tempFile, $json) === false) {
			throw new RuntimeException('Failed to write temporary settings file: ' . $tempFile);
		}

		if(!rename($tempFile, $this->filePath)) {
			@unlink($tempFile);
			throw new RuntimeException('Failed to move temporary settings file to target path: ' . $this->filePath);
		}

		$this->dirty = false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function reload(): void {
		if(!is_file($this->filePath)) {
			$this->data = [];
			$this->dirty = false;
			return;
		}

		$content = file_get_contents($this->filePath);

		if($content === false) {
			throw new RuntimeException('Failed to read settings file: ' . $this->filePath);
		}

		if(trim($content) === '') {
			$this->data = [];
			$this->dirty = false;
			return;
		}

		try {
			$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
		}
		catch(JsonException $e) {
			throw new RuntimeException('Failed to decode settings JSON: ' . $e->getMessage(), 0, $e);
		}

		if(!is_array($data)) {
			throw new RuntimeException('Settings JSON root must be an object or array.');
		}

		$this->assertValidStructure($data);

		$this->data = $data;
		$this->dirty = false;
	}

	/**
	 * Builds the settings file path from base configuration.
	 *
	 * @return string
	 */
	private function buildFilePath(): string {
		$dataDirectory = trim($this->configuration->getString('directories', 'data', ''));

		if($dataDirectory === '') {
			throw new RuntimeException('Missing configuration value: [directories] data');
		}

		return rtrim($dataDirectory, '/\\') . '/cnf/settings.json';
	}

	/**
	 * Validates the expected nested structure:
	 * - group => array
	 * - name => array
	 *
	 * @param array $data
	 * @return void
	 */
	private function assertValidStructure(array $data): void {
		foreach($data as $group => $groupSettings) {
			if(!is_string($group) || $group === '') {
				throw new RuntimeException('Invalid settings group key in JSON file.');
			}

			if(!is_array($groupSettings)) {
				throw new RuntimeException('Settings group "' . $group . '" must contain an object.');
			}

			foreach($groupSettings as $name => $settings) {
				if(!is_string($name) || $name === '') {
					throw new RuntimeException('Invalid settings name in group "' . $group . '".');
				}

				if(!is_array($settings)) {
					throw new RuntimeException(
						'Settings dataset "' . $group . '/' . $name . '" must be an object.'
					);
				}
			}
		}
	}
}
