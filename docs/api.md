# Paste API Documentation

## Overview

The Paste API allows you to programmatically create, retrieve, list, search, and delete pastes. 

**Base URL:** `https://your-paste-site.com/api.php`

## Authentication

All API requests (except `languages`) require authentication via an API key.

### Getting an API Key

1. Log in to your Paste account
2. Go to your **Profile** page
3. Scroll down to the **API Keys** section
4. Click **Create API Key**
5. Copy your key immediately - it won't be shown again!

### Using Your API Key

Include your API key in requests using one of these methods:

**HTTP Header (recommended):**
```
X-API-Key: your_api_key_here
```

**Query Parameter:**
```
?api_key=your_api_key_here
```

**POST Body:**
```json
{
  "api_key": "your_api_key_here"
}
```

---

## Endpoints

### Create a Paste

Create a new paste.

**Endpoint:** `POST /api.php?action=paste`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `content` | string | Yes | The paste content |
| `title` | string | No | Paste title (default: "Untitled") |
| `syntax` | string | No | Syntax highlighting language (default: "text") |
| `visibility` | string | No | `public`, `unlisted`, or `private` (default: "public") |
| `expiry` | string | No | Expiry time: `NULL` (never), `T` (10 min), `H` (1 hour), `W` (1 week), `M` (1 month), `B` (6 months), `Y` (1 year) |
| `password` | string | No | Password protect the paste |

**Example Request (cURL):**
```bash
curl -X POST "https://paste.example.com/api.php?action=paste" \
  -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "print(\"Hello, World!\")",
    "title": "My Python Script",
    "syntax": "python",
    "visibility": "public"
  }'
```

**Example Request (Python):**
```python
import requests

response = requests.post(
    "https://paste.example.com/api.php?action=paste",
    headers={"X-API-Key": "your_api_key"},
    json={
        "content": "print('Hello, World!')",
        "title": "My Python Script",
        "syntax": "python",
        "visibility": "public"
    }
)
print(response.json())
```

**Success Response (201):**
```json
{
  "success": true,
  "paste": {
    "id": 12345,
    "slug": "aBcDeFgH",
    "url": "https://paste.example.com/aBcDeFgH",
    "raw_url": "https://paste.example.com/raw/aBcDeFgH",
    "title": "My Python Script",
    "syntax": "python",
    "visibility": "public",
    "expiry": "NULL",
    "encrypted": false,
    "created_at": "2025-02-01 12:00:00"
  }
}
```

---

### Get a Paste

Retrieve a paste by ID or slug.

**Endpoint:** `GET /api.php?action=get&id={id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Paste ID or slug |
| `password` | string | No | Required if paste is password-protected |

**Example Request:**
```bash
curl "https://paste.example.com/api.php?action=get&id=aBcDeFgH" \
  -H "X-API-Key: your_api_key"
```

**Success Response (200):**
```json
{
  "success": true,
  "paste": {
    "id": 12345,
    "slug": "aBcDeFgH",
    "url": "https://paste.example.com/aBcDeFgH",
    "title": "My Python Script",
    "content": "print('Hello, World!')",
    "syntax": "python",
    "visibility": "public",
    "expiry": "NULL",
    "encrypted": false,
    "author": "username",
    "created_at": "2025-02-01 12:00:00",
    "views": 42
  }
}
```

---

### List Your Pastes

Get a paginated list of your pastes.

**Endpoint:** `GET /api.php?action=list`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | int | No | Page number (default: 1) |
| `limit` | int | No | Items per page, max 100 (default: 20) |

**Example Request:**
```bash
curl "https://paste.example.com/api.php?action=list&page=1&limit=10" \
  -H "X-API-Key: your_api_key"
```

**Success Response (200):**
```json
{
  "success": true,
  "pastes": [
    {
      "id": 12345,
      "slug": "aBcDeFgH",
      "url": "https://paste.example.com/aBcDeFgH",
      "title": "My Python Script",
      "syntax": "python",
      "visibility": "public",
      "expiry": "NULL",
      "encrypted": false,
      "created_at": "2025-02-01 12:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 42,
    "pages": 5
  }
}
```

---

### Delete a Paste

Delete one of your pastes.

**Endpoint:** `DELETE /api.php?action=delete&id={id}`

Or: `POST /api.php?action=delete&id={id}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Paste ID or slug |

**Example Request:**
```bash
curl -X DELETE "https://paste.example.com/api.php?action=delete&id=aBcDeFgH" \
  -H "X-API-Key: your_api_key"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Paste deleted"
}
```

---

### Search Pastes

Search public pastes and your own pastes.

**Endpoint:** `GET /api.php?action=search&q={query}`

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | string | Yes | Search query (min 2 characters) |
| `page` | int | No | Page number (default: 1) |
| `limit` | int | No | Items per page, max 50 (default: 20) |

**Note:** Only searches non-encrypted pastes.

**Example Request:**
```bash
curl "https://paste.example.com/api.php?action=search&q=python" \
  -H "X-API-Key: your_api_key"
```

**Success Response (200):**
```json
{
  "success": true,
  "query": "python",
  "pastes": [
    {
      "id": 12345,
      "slug": "aBcDeFgH",
      "url": "https://paste.example.com/aBcDeFgH",
      "title": "My Python Script",
      "syntax": "python",
      "author": "username",
      "created_at": "2025-02-01 12:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 5,
    "pages": 1
  }
}
```

---

### List Languages

Get available syntax highlighting languages. **No authentication required.**

**Endpoint:** `GET /api.php?action=languages`

**Example Request:**
```bash
curl "https://paste.example.com/api.php?action=languages"
```

**Success Response (200):**
```json
{
  "success": true,
  "languages": {
    "text": "Plain Text",
    "python": "Python",
    "javascript": "JavaScript",
    "php": "PHP",
    ...
  }
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "error": "Error message here"
}
```

**Common HTTP Status Codes:**

| Code | Description |
|------|-------------|
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Invalid or missing API key |
| 403 | Forbidden - Access denied |
| 404 | Not Found - Paste doesn't exist |
| 405 | Method Not Allowed - Wrong HTTP method |
| 503 | Service Unavailable - API is disabled |

---

## Rate Limits

- API requests are subject to reasonable rate limits
- Avoid making more than 60 requests per minute
- Excessive usage may result in temporary API key suspension

---

## Code Examples

### Python - Create and Retrieve Paste

```python
import requests

API_URL = "https://paste.example.com/api.php"
API_KEY = "your_api_key_here"

headers = {"X-API-Key": API_KEY}

# Create a paste
response = requests.post(
    f"{API_URL}?action=paste",
    headers=headers,
    json={
        "content": "Hello from Python!",
        "title": "API Test",
        "syntax": "text"
    }
)
result = response.json()
print(f"Created paste: {result['paste']['url']}")

# Get the paste back
paste_id = result['paste']['slug']
response = requests.get(
    f"{API_URL}?action=get&id={paste_id}",
    headers=headers
)
paste = response.json()['paste']
print(f"Content: {paste['content']}")
```

### Bash - Quick Paste Script

```bash
#!/bin/bash
# paste.sh - Quick paste from command line

API_KEY="your_api_key_here"
API_URL="https://paste.example.com/api.php"

# Read from stdin or file
if [ -z "$1" ]; then
    CONTENT=$(cat)
else
    CONTENT=$(cat "$1")
fi

# Post to API
RESPONSE=$(curl -s -X POST "$API_URL?action=paste" \
    -H "X-API-Key: $API_KEY" \
    -H "Content-Type: application/json" \
    -d "{\"content\": $(echo "$CONTENT" | jq -Rs .)}")

# Extract URL
echo "$RESPONSE" | jq -r '.paste.url'
```

Usage: `cat myfile.py | ./paste.sh` or `./paste.sh myfile.py`

### JavaScript/Node.js

```javascript
const API_URL = 'https://paste.example.com/api.php';
const API_KEY = 'your_api_key_here';

async function createPaste(content, title = 'Untitled', syntax = 'text') {
    const response = await fetch(`${API_URL}?action=paste`, {
        method: 'POST',
        headers: {
            'X-API-Key': API_KEY,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ content, title, syntax })
    });
    return response.json();
}

// Usage
createPaste('console.log("Hello!");', 'JS Test', 'javascript')
    .then(result => console.log(result.paste.url));
```

---

## Support

If you encounter issues with the API, please check:

1. Your API key is correct and active
2. The API is enabled (admin setting)
3. You're using the correct HTTP method
4. Required parameters are provided

For further assistance, contact the site administrator.
