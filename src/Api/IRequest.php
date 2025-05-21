<?php declare(strict_types=1);

namespace Base3\Api;

interface IRequest {

	const CONTEXT_CLI = 'cli';
	const CONTEXT_WEB_GET = 'web_get';
	const CONTEXT_WEB_POST = 'web_post';
	const CONTEXT_WEB_AJAX = 'web_ajax';
	const CONTEXT_WEB_API = 'web_api';
	const CONTEXT_WEB_UPLOAD = 'web_upload';
	const CONTEXT_CRON = 'cron';
	const CONTEXT_TEST = 'test';
	const CONTEXT_BUILTIN_SERVER = 'builtin_server';

	public function get(string $key, $default = null);
	public function post(string $key, $default = null);
	public function cookie(string $key, $default = null);
	public function session(string $key, $default = null);
	public function server(string $key, $default = null);
	public function files(string $key, $default = null);

	public function allGet(): array;
	public function allPost(): array;
	public function allCookie(): array;
	public function allSession(): array;
	public function allServer(): array;
	public function allFiles(): array;

	public function isCli(): bool;
	public function getContext(): string;
}

