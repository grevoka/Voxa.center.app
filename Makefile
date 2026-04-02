.PHONY: help up down build restart logs shell migrate fresh seed queue test

# Variables
DC = docker compose
PHP = $(DC) exec php
ARTISAN = $(PHP) php artisan

help: ## Afficher l'aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Docker
up: ## Demarrer tous les containers
	$(DC) up -d

down: ## Arreter tous les containers
	$(DC) down

build: ## Rebuild tous les containers
	$(DC) build --no-cache

restart: ## Redemarrer tous les containers
	$(DC) restart

logs: ## Voir les logs (tous les services)
	$(DC) logs -f

logs-php: ## Logs du container PHP
	$(DC) logs -f php

logs-ast: ## Logs du container Asterisk
	$(DC) logs -f asterisk

# Laravel
shell: ## Shell dans le container PHP
	$(PHP) bash

artisan: ## Executer une commande artisan (ex: make artisan cmd="route:list")
	$(ARTISAN) $(cmd)

migrate: ## Lancer les migrations
	$(ARTISAN) migrate

fresh: ## Reset complet de la base + migrate + seed
	$(ARTISAN) migrate:fresh --seed

seed: ## Lancer les seeders
	$(ARTISAN) db:seed

cache: ## Cache config/routes/views
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache

clear: ## Vider tous les caches
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear
	$(ARTISAN) cache:clear

queue: ## Relancer le queue worker
	$(DC) restart php

test: ## Lancer les tests
	$(PHP) php artisan test

# Composer / NPM
composer-install: ## Installer les dependances Composer
	$(PHP) composer install

npm-build: ## Build les assets
	$(PHP) npm run build

npm-dev: ## Build dev avec watch
	$(PHP) npm run dev

# Asterisk
ast-cli: ## Ouvrir le CLI Asterisk
	$(DC) exec asterisk asterisk -rvvv

ast-reload: ## Reload PJSIP
	$(DC) exec asterisk asterisk -rx "pjsip reload"

ast-endpoints: ## Lister les endpoints PJSIP
	$(DC) exec asterisk asterisk -rx "pjsip show endpoints"

ast-registrations: ## Voir les registrations trunk
	$(DC) exec asterisk asterisk -rx "pjsip show registrations"

ast-channels: ## Voir les appels actifs
	$(DC) exec asterisk asterisk -rx "core show channels"

# Base de donnees
db-shell: ## Shell MySQL
	$(DC) exec mariadb mysql -u root -p

db-backup: ## Backup des bases
	$(DC) exec mariadb mysqldump -u root -p sip_manager | gzip > backups/app_$$(date +%Y%m%d).sql.gz
	$(DC) exec mariadb mysqldump -u root -p asterisk_rt | gzip > backups/ast_$$(date +%Y%m%d).sql.gz

# Production
prod-up: ## Demarrer en mode production
	$(DC) -f docker-compose.yml -f docker-compose.prod.yml up -d

prod-deploy: ## Deployer une mise a jour
	git pull
	$(DC) -f docker-compose.yml -f docker-compose.prod.yml build php
	$(DC) -f docker-compose.yml -f docker-compose.prod.yml up -d
	$(ARTISAN) migrate --force
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	$(DC) restart php
