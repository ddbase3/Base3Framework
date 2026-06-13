# ==============================================================================
# BASE3 / Project tooling
#
# Quickstart:
#   make install      # generates composer.json + installs deps + builds plugin files
#   make test         # runs PHPUnit (prefers ./vendor/bin/phpunit)
#   make coverage     # generates docs/coverage (pcov if available, otherwise xdebug)
#   make stan         # runs PHPStan
#   make doc          # generates PHPDoc into docs/phpdoc
#
# Notes:
# - Composer is OPTIONAL for the framework runtime.
# - composer.base.json is the stable input (committed).
# - composer.json is GENERATED (safe to delete).
# - Generated output folders:
#     docs/coverage, docs/phpdoc
# ==============================================================================

PUBLIC_DIR	:= public
TOOLS_DIR	:= tools
SETUP_DIR	:= $(TOOLS_DIR)/setup

MERGE_SCRIPT		:= $(SETUP_DIR)/merge-composer.php
BUILD_ASSETS_SCRIPT	:= $(SETUP_DIR)/build-assets.php
BUILD_ROOTFILES_SCRIPT	:= $(SETUP_DIR)/build-rootfiles.php
BUILD_PUBLICFILES_SCRIPT	:= $(SETUP_DIR)/build-publicfiles.php

PHPUNIT_LOCAL = ./vendor/bin/phpunit
PHPUNIT_GLOBAL = phpunit

.PHONY: help all init merge install update \
        rootfiles publicfiles assets \
        test test-issues coverage coverage-clean \
        doc doc-clean stan \
        composer phpunit-version \
        clean clean-all

help:
	@echo "Targets:"
	@echo "  install         Generate composer.json + install composer deps + build plugin files"
	@echo "  update          Generate composer.json + update composer deps + build plugin files"
	@echo "  test            Run PHPUnit"
	@echo "  test-issues     Run PHPUnit with --display-all-issues"
	@echo "  coverage        Build coverage report (docs/coverage)"
	@echo "  stan            Run PHPStan"
	@echo "  doc             Build PHPDoc (docs/phpdoc)"
	@echo "  clean           Remove vendor + generated composer.json (keeps composer.lock)"
	@echo "  clean-all       Like clean, but also removes composer.lock"

all: install

init:
	@echo "Init..."

merge:
	@echo "🔧 Generating composer.json (base + plugins)..."
	php $(MERGE_SCRIPT)

install: merge
	@if [ -f composer.json ]; then \
		echo "📦 Installing composer dependencies..."; \
		composer install --no-interaction; \
	else \
		echo "❌ composer.json missing (merge step failed?)."; \
		exit 1; \
	fi
	@$(MAKE) rootfiles
	@$(MAKE) publicfiles
	@$(MAKE) assets

update: merge
	@if [ -f composer.json ]; then \
		echo "📦 Updating composer dependencies..."; \
		composer update --no-interaction; \
	else \
		echo "❌ composer.json missing (merge step failed?)."; \
		exit 1; \
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
	@echo "🧹 Cleaning vendor and generated composer files..."
	rm -rf vendor
	rm -f composer.json

clean-all: clean
	@echo "🧹 Also removing composer.lock..."
	rm -f composer.lock

composer:
	@composer --version

phpunit-version:
	@if [ -x "$(PHPUNIT_LOCAL)" ]; then \
		$(PHPUNIT_LOCAL) --version; \
	else \
		$(PHPUNIT_GLOBAL) --version; \
	fi

test:
	@echo "✅ Running PHPUnit tests..."
	@if [ -x "$(PHPUNIT_LOCAL)" ]; then \
		$(PHPUNIT_LOCAL) --no-coverage; \
	else \
		$(PHPUNIT_GLOBAL) --no-coverage; \
	fi

test-issues:
	@echo "✅ Running PHPUnit tests and display all issues..."
	@if [ -x "$(PHPUNIT_LOCAL)" ]; then \
		$(PHPUNIT_LOCAL) --display-all-issues; \
	else \
		$(PHPUNIT_GLOBAL) --display-all-issues; \
	fi

coverage:
	@echo "📊 Building coverage report..."
	@if [ -x "$(PHPUNIT_LOCAL)" ]; then \
		PHPUNIT_BIN="$(PHPUNIT_LOCAL)"; \
	elif command -v $(PHPUNIT_GLOBAL) >/dev/null 2>&1; then \
		PHPUNIT_BIN="$$(command -v $(PHPUNIT_GLOBAL))"; \
	else \
		echo "❌ PHPUnit not found. Run 'make install' or install phpunit globally."; \
		exit 1; \
	fi; \
	if php -m | grep -qi '^pcov$$'; then \
		echo "➡️  Using PCOV for coverage"; \
		php -d pcov.enabled=1 \
		    -d pcov.directory="$(CURDIR)" \
		    -d pcov.exclude='#/(vendor|docs|tmp|public|plugin/[^/]+/tpl|test)/#' \
		    $$PHPUNIT_BIN \
		    --coverage-html docs/coverage \
		    --coverage-text; \
	elif php -m | grep -qi '^xdebug$$'; then \
		echo "➡️  Using Xdebug for coverage"; \
		XDEBUG_MODE=coverage $$PHPUNIT_BIN \
		    --coverage-html docs/coverage \
		    --coverage-text; \
	else \
		echo "❌ No coverage driver found. Install/enable pcov or xdebug."; \
		exit 1; \
	fi

coverage-clean:
	rm -rf docs/coverage

doc:
	phpdoc run -d src,plugin/*/src -t docs/phpdoc

doc-clean:
	rm -rf docs/phpdoc

stan:
	@echo "🔎 Running PHPStan..."
	php -d memory_limit=1G $$(command -v phpstan) analyse -c phpstan.neon
