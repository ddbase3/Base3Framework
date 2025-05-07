PLUGIN_DIR = plugin
MERGE_SCRIPT = setup/merge-composer.php
BUILD_ASSETS_SCRIPT = setup/build-assets.php
BUILD_ROOTFILES_SCRIPT = setup/build-rootfiles.php

.PHONY: all init merge install update clean test assets rootfiles

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
	@$(MAKE) assets
	@$(MAKE) rootfiles

update: merge
	@if [ -f plugin/composer.json ]; then \
		echo "📦 Updating composer dependencies..."; \
		cd plugin && composer update --no-interaction; \
	else \
		echo "ℹ️  No plugin/composer.json found. Skipping composer update."; \
	fi
	@$(MAKE) assets
	@$(MAKE) rootfiles

assets:
	@echo "🎨 Building public/assets/ from plugin assets..."
	php $(BUILD_ASSETS_SCRIPT)

rootfiles:
	@echo "📄 Copying plugin/*/rootfiles/ to public/..."
	php $(BUILD_ROOTFILES_SCRIPT)

clean:
	@echo "🧹 Cleaning plugin/vendor and composer files..."
	rm -rf $(PLUGIN_DIR)/vendor
	rm -f  $(PLUGIN_DIR)/composer.lock
	rm -f  $(PLUGIN_DIR)/composer.json

test:
	@echo "✅ Running PHPUnit tests..."
	phpunit

