PLUGIN_DIR = plugin
MERGE_SCRIPT = setup/merge-composer.php

.PHONY: all init plugins merge install update clean test

all: install

init:
	@echo "Init..."

merge:
	@echo "Merging plugin composer.json files..."
	php $(MERGE_SCRIPT)

install: merge
	@echo "Installing dependencies..."
	composer --working-dir=$(PLUGIN_DIR) install

update: merge
	@echo "Updating dependencies..."
	composer --working-dir=$(PLUGIN_DIR) update

clean:
	@echo "Cleaning plugin/vendor and composer files..."
	rm -rf $(PLUGIN_DIR)/vendor
	rm -f  $(PLUGIN_DIR)/composer.lock
	rm -f  $(PLUGIN_DIR)/composer.json

test:
	@echo "PHPunit tests..."
	phpunit

