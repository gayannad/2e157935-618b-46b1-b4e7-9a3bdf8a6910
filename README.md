# Coding Challenge

This is a command-line Laravel application for generating student assessment reports. It supports three types of reports:

- Diagnostic Report
- Progress Report
- Feedback Report
---

### Prerequisites

- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)

---

## Running the Application with Docker Compose

### Start the Laravel App

```bash
docker-compose run --rm app php artisan report-generate
```

### Run Tests

```bash
docker-compose run --rm test
```
