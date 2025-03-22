# Task Management API Documentation

This document describes the TaskMan API endpoints for managing tasks. All endpoints require authentication using Laravel Sanctum.

## Authentication

All API requests require a valid Sanctum token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

You can obtain a token by using Laravel's authentication system or by implementing a custom token issuance endpoint.

## API Endpoints

### List Tasks

Retrieves a paginated list of tasks that belong to the authenticated user, with optional filtering.

**URL:** `GET /api/v1/tasks`

**Query Parameters:**

| Parameter       | Description                                      | Example                       |
|-----------------|--------------------------------------------------|-------------------------------|
| search          | Search term for title and description            | `?search=important`           |
| status          | Filter by task status                            | `?status=completed`           |
| priority        | Filter by priority level                         | `?priority=HIGH`              |
| sort_by         | Field to sort by                                 | `?sort_by=due`                |
| sort_direction  | Sort direction (asc or desc)                     | `?sort_direction=desc`        |
| per_page        | Number of items per page                         | `?per_page=20`                |

**Status Options:**
- `completed` - All completed tasks
- `uncompleted` - All uncompleted tasks
- `overdue` - Tasks past their due date
- `today` - Tasks due today
- `this_week` - Tasks due this week
- `this_month` - Tasks due this month
- `this_year` - Tasks due this year
- `next_7_days` - Tasks due in the next 7 days
- `next_30_days` - Tasks due in the next 30 days
- `next_90_days` - Tasks due in the next 90 days

**Example Response:**

```json
{
  "tasks": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Complete API Documentation",
        "desc": "Write comprehensive documentation for the Task API",
        "due": "2025-03-25T12:00:00.000000Z",
        "priority": "HIGH",
        "completed": false,
        "user_id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "slug": "complete-api-documentation",
        "created_at": "2025-03-21T12:00:00.000000Z",
        "updated_at": "2025-03-21T12:00:00.000000Z"
      },
      // More tasks...
    ],
    "first_page_url": "http://example.com/api/v1/tasks?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://example.com/api/v1/tasks?page=3",
    "links": [
      // Pagination links...
    ],
    "next_page_url": "http://example.com/api/v1/tasks?page=2",
    "path": "http://example.com/api/v1/tasks",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 25
  }
}
```

### Get a Single Task

Retrieves a specific task by ID.

**URL:** `GET /api/v1/tasks/{id}`

**Path Parameters:**

| Parameter | Description        | Example |
|-----------|--------------------|---------|
| id        | Task ID            | `/api/v1/tasks/1` |

**Example Response:**

```json
{
  "task": {
    "id": 1,
    "title": "Complete API Documentation",
    "desc": "Write comprehensive documentation for the Task API",
    "due": "2025-03-25T12:00:00.000000Z",
    "priority": "HIGH",
    "completed": false,
    "user_id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "slug": "complete-api-documentation",
    "created_at": "2025-03-21T12:00:00.000000Z",
    "updated_at": "2025-03-21T12:00:00.000000Z"
  }
}
```

### Create a Task

Creates a new task for the authenticated user.

**URL:** `POST /api/v1/tasks`

**Request Body:**

| Field      | Type                 | Required | Description                   |
|------------|----------------------|----------|-------------------------------|
| title      | string               | Yes      | Task title (5-250 chars)      |
| desc       | string               | Yes      | Task description              |
| priority   | enum (PriorityLevel) | Yes      | Task priority                 |
| due        | datetime             | No       | Due date and time             |

**Priority Level Options:**
- `CRITICAL`
- `HIGH`
- `MEDIUM`
- `LOW`
- `NONE`

**Example Request:**

```json
{
  "title": "Review Project Proposal",
  "desc": "Review the new project proposal document and provide feedback",
  "priority": "HIGH",
  "due": "2025-03-28T15:00:00"
}
```

**Example Response:**

```json
{
  "message": "Task created successfully",
  "task": {
    "id": 10,
    "title": "Review Project Proposal",
    "desc": "Review the new project proposal document and provide feedback",
    "due": "2025-03-28T15:00:00.000000Z",
    "priority": "HIGH",
    "completed": false,
    "user_id": 1,
    "uuid": "7f1bfd09-2d48-4c3a-a874-2c55eac1d695",
    "slug": "review-project-proposal",
    "created_at": "2025-03-21T12:35:42.000000Z",
    "updated_at": "2025-03-21T12:35:42.000000Z"
  }
}
```

### Update a Task

Updates an existing task.

**URL:** `PUT /api/v1/tasks/{id}`

**Path Parameters:**

| Parameter | Description        | Example |
|-----------|--------------------|---------|
| id        | Task ID            | `/api/v1/tasks/10` |

**Request Body:**

| Field      | Type                 | Required | Description                   |
|------------|----------------------|----------|-------------------------------|
| title      | string               | No       | Task title (5-250 chars)      |
| desc       | string               | No       | Task description              |
| priority   | enum (PriorityLevel) | No       | Task priority                 |
| due        | datetime             | No       | Due date and time             |
| completed  | boolean              | No       | Completion status             |

**Example Request:**

```json
{
  "title": "Review Project Proposal - Urgent",
  "priority": "CRITICAL"
}
```

**Example Response:**

```json
{
  "message": "Task updated successfully",
  "task": {
    "id": 10,
    "title": "Review Project Proposal - Urgent",
    "desc": "Review the new project proposal document and provide feedback",
    "due": "2025-03-28T15:00:00.000000Z",
    "priority": "CRITICAL",
    "completed": false,
    "user_id": 1,
    "uuid": "7f1bfd09-2d48-4c3a-a874-2c55eac1d695",
    "slug": "review-project-proposal-urgent",
    "created_at": "2025-03-21T12:35:42.000000Z",
    "updated_at": "2025-03-21T12:40:15.000000Z"
  }
}
```

### Delete a Task

Deletes a specific task.

**URL:** `DELETE /api/v1/tasks/{id}`

**Path Parameters:**

| Parameter | Description        | Example |
|-----------|--------------------|---------|
| id        | Task ID            | `/api/v1/tasks/10` |

**Example Response:**

```json
{
  "message": "Task deleted successfully"
}
```

### Toggle Task Completion

Toggles the completion status of a task.

**URL:** `PATCH /api/v1/tasks/{id}/toggle-completion`

**Path Parameters:**

| Parameter | Description        | Example |
|-----------|--------------------|---------|
| id        | Task ID            | `/api/v1/tasks/5/toggle-completion` |

**Example Response:**

```json
{
  "message": "Task completion toggled successfully",
  "task": {
    "id": 5,
    "title": "Team Meeting",
    "desc": "Weekly team sync",
    "due": "2025-03-22T10:00:00.000000Z",
    "priority": "MEDIUM",
    "completed": true,
    "completed_at": "2025-03-21T12:45:22.000000Z",
    "user_id": 1,
    "uuid": "a1b2c3d4-e5f6-4a5b-8c7d-9e8f7a6b5c4d",
    "slug": "team-meeting",
    "created_at": "2025-03-20T09:30:00.000000Z",
    "updated_at": "2025-03-21T12:45:22.000000Z"
  }
}
```

### Export Tasks

Exports all tasks for the authenticated user.

**URL:** `GET /api/v1/tasks/export`

**Example Response:**

```json
{
  "metadata": {
    "version": "1.0",
    "created_at": "2025-03-21T13:00:00.000000Z",
    "user_id": 1,
    "user_email": "user@example.com",
    "task_count": 25
  },
  "tasks": [
    {
      "id": 1,
      "title": "Complete API Documentation",
      "desc": "Write comprehensive documentation for the Task API",
      "due": "2025-03-25T12:00:00.000000Z",
      "priority": "HIGH",
      "completed": false,
      "user_id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "slug": "complete-api-documentation",
      "created_at": "2025-03-21T12:00:00.000000Z",
      "updated_at": "2025-03-21T12:00:00.000000Z"
    },
    // More tasks...
  ]
}
```

### Import Tasks

Imports tasks from a backup.

**URL:** `POST /api/v1/tasks/import`

**Request Body:**

| Field             | Type                 | Required | Description                   |
|-------------------|----------------------|----------|-------------------------------|
| data              | object               | Yes      | Task backup data              |
| data.metadata     | object               | Yes      | Backup metadata               |
| data.tasks        | array                | Yes      | Array of task objects         |
| duplicate_action  | string               | Yes      | How to handle duplicates      |

**Duplicate Action Options:**
- `skip` - Skip tasks with duplicate UUIDs
- `overwrite` - Overwrite tasks with duplicate UUIDs
- `keep_both` - Keep both by generating new UUIDs for imported tasks

**Example Request:**

```json
{
  "data": {
    "metadata": {
      "version": "1.0",
      "created_at": "2025-03-20T13:00:00.000000Z",
      "user_id": 1,
      "user_email": "user@example.com",
      "task_count": 3
    },
    "tasks": [
      {
        "title": "Imported Task 1",
        "desc": "This is an imported task",
        "due": "2025-04-01T09:00:00.000000Z",
        "priority": "MEDIUM",
        "completed": false,
        "uuid": "b3c4d5e6-f7g8-5h6i-9j0k-1l2m3n4o5p6q"
      },
      // More tasks...
    ]
  },
  "duplicate_action": "skip"
}
```

**Example Response:**

```json
{
  "message": "Tasks imported successfully",
  "stats": {
    "imported": 2,
    "updated": 0,
    "skipped": 1
  }
}
```

## Error Responses

The API uses standard HTTP status codes to indicate the success or failure of requests.

### Common Status Codes

- `200 OK` - The request was successful
- `201 Created` - A resource was successfully created
- `401 Unauthorized` - Authentication is required
- `403 Forbidden` - The authenticated user doesn't have permission
- `404 Not Found` - The requested resource was not found
- `422 Unprocessable Entity` - Validation errors

### Validation Error Example

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": [
      "The title field is required.",
      "The title must be at least 5 characters."
    ],
    "priority": [
      "The selected priority is invalid."
    ]
  }
}
```

## Rate Limiting

API requests are subject to rate limiting. If you exceed the limit, you'll receive a `429 Too Many Requests` response.