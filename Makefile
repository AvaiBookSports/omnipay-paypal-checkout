.PHONY: help check unit-tests static-analysis lint coding-style-fix coding-style-check

help: ## Show this help.
	@printf "\033[33mUsage:\033[0m\n  make [target] [arg=\"val\"...]\n\n\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-30s\033[0m %s\n", $$1, $$2}'

check: coding-style-check lint static-analysis unit-tests ## Run all checks.

unit-tests: ## Run unit tests.
	vendor/bin/phpunit

static-analysis: ## Run PHPStan static analysis.
	vendor/bin/phpstan analyse

lint: ## Lint source code with Mago.
	vendor/bin/mago lint

coding-style-fix: ## Fix code formatting with Mago.
	vendor/bin/mago format

coding-style-check: ## Check code formatting with Mago.
	vendor/bin/mago format --dry-run
