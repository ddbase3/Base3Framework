PLUGIN_DIR = plugin
MERGE_SCRIPT = setup/merge-composer.php
ASSET_SCRIPT = setup/build-assets.php

.PHONY: all init plugins merge install update clean test assets

all: install

init:
	@echo "⚙️  Init..."

merge:
	@echo "🔀 Merging plugin composer.json files..."
	php $(MERGE_SCRIPT)

assets:
ifeq ($(clean),true)
	@echo "🧽 Cleaning public/assets/ before build..."
	rm -rf public/assets/
endif
	@echo "🎨 Building assets..."
	php $(ASSET_SCRIPT)

install: merge
	@echo "📦 Installing dependencies..."
	composer --working-dir=$(PLUGIN_DIR) install
	$(MAKE) assets

update: merge
	@echo "⬆️  Updating dependencies..."
	composer --working-dir=$(PLUGIN_DIR) update
	$(MAKE) assets

clean:
	@echo "🧹 Cleaning plugin/vendor and composer files..."
	rm -rf $(PLUGIN_DIR)/vendor
	rm -f  $(PLUGIN_DIR)/composer.lock
	rm -f  $(PLUGIN_DIR)/composer.json

test:
	@echo "🧪 Running PHPUnit tests..."
	phpunit

