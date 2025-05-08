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
	@if [ -f plugin/composer.json ]; then \
		echo "📦 Installing composer dependencies..."; \
		cd plugin && composer install --no-interaction; \
	else \
		echo "ℹ️  No plugin/composer.json found. Skipping composer install."; \
	fi
	@$(MAKE) rootfiles
	@$(MAKE) publicfiles
	@$(MAKE) assets

update: merge
	@if [ -f plugin/composer.json ]; then \
		echo "📦 Updating composer dependencies..."; \
		cd plugin && composer update --no-interaction; \
	else \
		echo "ℹ️  No plugin/composer.json found. Skipping composer update."; \
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
	@echo "🧹 Cleaning plugin/vendor and composer files..."
	rm -rf $(PLUGIN_DIR)/vendor
	rm -f  $(PLUGIN_DIR)/composer.lock
	rm -f  $(PLUGIN_DIR)/composer.json

test:
	@echo "✅ Running PHPUnit tests..."
	phpunit

