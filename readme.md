# Bro World Notification Backend

This repository hosts the Bro World notification service, a Symfony 7 application that orchestrates multi-channel notifications and supporting infrastructure. It ships with a production-like Docker environment, automated quality gates, and reference documentation that make it easy to develop and operate email, SMS, and push messaging at scale.

## Features at a Glance
- **Multi-channel delivery** – Unified domain model and services for email, SMS, and push notifications, including status tracking and completion timestamps for every message. 
- **Template management** – Built-in Mailjet template synchronization and upload commands so that marketing and transactional templates can be managed centrally.
- **Audience targeting** – Scopes and scope targets let you address individual users or batched recipients, while helpers fetch all eligible members for a notification run.
- **Asynchronous processing** – Symfony Messenger with RabbitMQ, cronable command scheduler, and Redis provide the plumbing for queued work and temporal scheduling.
- **Observability and tooling** – Elasticsearch/Kibana stacks, rich logging, and a curated Makefile expose diagnostics and developer operations out of the box.

## Tech Stack
- Symfony 7 with Doctrine ORM, Messenger, Notifier, and JWT authentication.
- PHP 8.4 FPM served behind Nginx, with MySQL 8 as the primary datastore.
- RabbitMQ 4 for queues, Redis 7 for caching, Elasticsearch 7 and Kibana 7 for search and observability.
- Mailjet for email templating and Twilio SDK for SMS delivery.

## Repository Layout
- `src/Notification` – Domain entities, repositories, application services, commands, and HTTP controllers dedicated to notification flows.
- `src/General` – Cross-cutting utilities, shared infrastructure, and base abstractions for the application.
- `docs/` – Extended documentation covering development workflows, command reference, API clients, testing guidance, and integration tips for IDEs and tooling.
- `docker/` – Environment-specific PHP, Nginx, MySQL, RabbitMQ, Elasticsearch, and supporting configuration used by the compose files.

## Prerequisites
Before you start, install the following locally:
1. Docker Engine 23+ and Docker Compose 2+.
2. Make (pre-installed on macOS/Linux; Windows users can rely on WSL).
3. An editor or IDE such as PhpStorm.
4. Optional: MySQL Workbench, Postman, or Redis Desktop Manager for inspection utilities.

On Linux, add your user to the Docker group: `sudo usermod -aG docker $USER`. macOS users on Docker Desktop ≥ 4.22 benefit from enabling *virtiofs* for optimal performance.

## Getting Started
1. **Clone or create the project**
   ```bash
   git clone git@github.com:bro-world/bro-world-backend-notification.git
   cd bro-world-backend-notification
   # or install from Packagist
   composer create-project systemsdk/docker-symfony-api bro-world-backend-notification
   ```

2. **Configure secrets** – Update `APP_SECRET` and channel credentials (Mailjet, Twilio, etc.) in the environment files (`.env`, `.env.prod`, `.env.staging`). Regenerate RSA keys for JWT after every rotation.

3. **Adjust Docker ports (optional)** – Override defaults in `.env.local` if you need to change exposed ports or Xdebug settings. Delete `var/mysql-data` if you want a clean database state.

4. **Add local hostnames** – Append `127.0.0.1 localhost` (and any custom domains) to `/etc/hosts`.

5. **Start the development stack**
   ```bash
   make build
   make start
   make composer-install
   make generate-jwt-keys
   ```

6. **Bootstrap application data**
   ```bash
   make migrate
   make create-roles-groups
   make migrate-cron-jobs
   make messenger-setup-transports
   make elastic-create-or-update-template
   ```

7. **Access services** – Once the containers are up you can reach:
   - API documentation: http://localhost/api/doc
   - RabbitMQ management: http://localhost:15672
   - Kibana dashboards: http://localhost:5601
   - Mailpit inbox (dev email capture): http://localhost:8025

## Working with Templates and Notifications
- Use `make ssh` to enter the Symfony container and run console commands.
- Synchronise or upload Mailjet templates via `bin/console app:notification:upload-templates` (`UploadTemplateCommand`).
- Trigger ad-hoc notifications with `bin/console app:temporal:create-schedule` or through the REST controllers in `src/Notification/Transport/Controller/Api`.
- `NotificationService` orchestrates channel-specific services (`EmailService`, `SmsService`, `PushService`) and writes completion timestamps back through `NotificationRepository`.

## Common Make Targets
| Command | Purpose |
| --- | --- |
| `make help` | List all available developer shortcuts. |
| `make stop` / `make down` | Stop containers or remove the full stack. |
| `make restart` | Recreate the running development environment. |
| `make logs-<service>` | Tail logs from Symfony, Nginx, MySQL, RabbitMQ, Elasticsearch, or Kibana. |
| `make ssh-<service>` | Open a shell inside any container (Symfony, Nginx, Supervisord, MySQL, RabbitMQ, Elasticsearch, Kibana). |

See [`docs/commands.md`](docs/commands.md) for the complete catalogue including staging, test, and production variants.

## Testing and Quality Gates
The Makefile exposes every quality tool required by CI:
- `make phpunit` – Run the automated test suite.
- `make ecs` / `make ecs-fix` – Check or fix coding standards.
- `make phpstan` – Static analysis for catching regressions.
- `make phpmd`, `make phpcpd`, `make phpmetrics`, `make phpinsights` – Additional maintainability audits.
- `make composer-normalize`, `make composer-validate`, `make composer-unused`, `make composer-require-checker` – Keep Composer metadata tidy and accurate.

Refer to [`docs/testing.md`](docs/testing.md) and [`docs/development.md`](docs/development.md) for broader guidance on development practices and expectations.

## API Exploration
- Open the interactive Swagger UI at `http://localhost/api/doc` once the stack is running.
- Import the Postman collections located under `docs/postman` to experiment with the REST endpoints.
- Authentication is handled via API keys and JWT; see [`docs/api-key.md`](docs/api-key.md) for provisioning instructions.

## Additional Resources
- **IDE tips** – [`docs/phpstorm.md`](docs/phpstorm.md) and the supporting configuration in `docs/phpstorm/`.
- **Debugging** – [`docs/xdebug.md`](docs/xdebug.md) covers Xdebug setup for each host OS.
- **Messaging internals** – [`docs/messenger.md`](docs/messenger.md) outlines how RabbitMQ transports and message routing are configured.
- **Tooling** – [`docs/rdm.md`](docs/rdm.md) for Redis Desktop Manager, [`docs/swagger.md`](docs/swagger.md) for API documentation workflows.

## Troubleshooting
- If containers fail during startup, run `make wait-for-db` and `make wait-for-elastic` to confirm dependencies are reachable.
- Purge stuck state with `make down` followed by `docker volume prune` (only if you are sure you can lose local data).
- Delete `var/mysql-data` when you need to reset the MySQL dataset.

## Contributing
1. Create feature branches from `main`.
2. Write unit and integration tests for new behaviours.
3. Run the linting and quality commands listed above before opening a pull request.
4. Document new endpoints or workflows under `docs/` and update this README if usage changes.

## License
This project is distributed under the MIT License. See [LICENSE](LICENSE) for details.
