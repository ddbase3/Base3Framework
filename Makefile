PUBLIC_DIR = public
MERGE_SCRIPT = setup/merge-composer.php
BUILD_ASSETS_SCRIPT = setup/build-assets.php
BUILD_ROOTFILES_SCRIPT = setup/build-rootfiles.php
BUILD_PUBLICFILES_SCRIPT = setup/build-publicfiles.php

.PHONY: all init merge clean test install update

all: install

init:
	@echo "Init..."

merge:
	@echo "🔧 Merging plugin composer.json files..."
	php $(MERGE_SCRIPT)

install: merge
	@if [ -f composer.json ]; then \
		echo "📦 Installing composer dependencies..."; \
		composer install --no-interaction; \
	else \
		echo "ℹ️  No composer.json found. Skipping composer install."; \
	fi
	@$(MAKE) rootfiles
	@$(MAKE) publicfiles
	@$(MAKE) assets

update: merge
	@if [ -f composer.json ]; then \
		echo "📦 Updating composer dependencies..."; \
		composer update --no-interaction; \
	else \
		echo "ℹ️  No composer.json found. Skipping composer update."; \
	fi
	@$(MAKE) rootfiles
	@$(MAKE) publicfiles
	@$(MAKE) assets

rootfiles:
	@echo "📄 Copying plugin/*/rootfiles/ to /..."
	php $(BUILD_ROOTFILES_SCRIPT)

publicfiles:
	@echo "📄 Copying plugin/*/publicfiles/ to public/..."
	php $(BUILD_PUBLICFILES_SCRIPT)

assets:
	@if [ -d "$(PUBLIC_DIR)" ]; then \
		echo "🎨 Building public/assets/ from plugin assets..."; \
		php $(BUILD_ASSETS_SCRIPT); \
	else \
		echo "⚠️ No public directory found, skipping assets build."; \
	fi

clean:
	@echo "🧹 Cleaning vendor and composer files..."
	rm -rf vendor
	rm -f composer.lock
	rm -f composer.json

test:
	@echo "✅ Running PHPUnit tests..."
	phpunit --no-coverage

test-issues:
	@echo "✅ Running PHPUnit tests and display all issues..."
	phpunit --display-all-issues

coverage:
	@echo "📊 Building coverage report..."
	php \
		-d pcov.enabled=1 \
		-d pcov.directory=/srv/www/html/contourz.photo/test.contourz.photo \
		-d pcov.exclude='#/(vendor|docs|tmp|public|plugin/[^/]+/tpl)/#' \
		$$(command -v phpunit) \
		--coverage-html docs/coverage \
		--coverage-text

coverage-clean:
	rm -rf docs/coverage

doc:
	phpdoc run -d src,plugin/*/src -t docs/phpdoc

doc-clean:
	rm -rf docs/phpdoc

stan:
	@echo "🔎 Running PHPStan..."
	php -d memory_limit=1G $$(command -v phpstan) analyse -c phpstan.neon

