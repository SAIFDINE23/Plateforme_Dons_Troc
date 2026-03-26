# 🔌 API Endpoints - Charte System

## 📍 Endpoints Disponibles

### 1. Page Web Stepper

```
GET /charte/stepper
Route Name: app_charte_stepper
Auth: ROLE_USER required
Redirect: /app_login if not authenticated
Response: HTML (Twig template with React component)
```

**Usage:**
```
Browser: http://127.0.0.1:8000/charte/stepper
```

---

### 2. API: Accept Charte (Main)

```
POST /api/user/charte/accept
Route Name: api_user_charte_accept
Auth: ROLE_USER required (@IsGranted)
Content-Type: application/json
```

**Request Body:**
```json
{
  "sections": [
    "Esprit de la plateforme (Don et Troc)",
    "Objets interdits et limites",
    "Respect, courtoisie et rendez-vous",
    "Responsabilité de l'ULCO"
  ]
}
```

**Response 200 OK:**
```json
{
  "message": "Charte acceptée avec succès",
  "sections_accepted": 4,
  "timestamp": "2026-02-23 14:35:45"
}
```

**Response 400 Bad Request:**
```json
{
  "message": "Aucune section fournie"
}
```

**Response 401 Unauthorized:**
```json
{
  "message": "Utilisateur non authentifié"
}
```

**Response 500 Error:**
```json
{
  "message": "Erreur lors de l'enregistrement de la charte: ..."
}
```

---

### 3. API: Charte Status (Optional)

```
GET /api/user/charte/status
Route Name: api_user_charte_status
Auth: ROLE_USER required
Response: JSON with acceptance history
```

**Response 200 OK:**
```json
{
  "accepted": true,
  "sections_accepted": 4,
  "agreements": [
    {
      "section": "Esprit de la plateforme (Don et Troc)",
      "accepted_at": "2026-02-23 14:32:10"
    },
    {
      "section": "Objets interdits et limites",
      "accepted_at": "2026-02-23 14:33:20"
    },
    {
      "section": "Respect, courtoisie et rendez-vous",
      "accepted_at": "2026-02-23 14:34:30"
    },
    {
      "section": "Responsabilité de l'ULCO",
      "accepted_at": "2026-02-23 14:35:45"
    }
  ]
}
```

---

## 🧪 Test avec cURL

### Test 1: Accepter la charte

```bash
curl -X POST http://127.0.0.1:8000/api/user/charte/accept \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=$(php -r 'echo bin2hex(random_bytes(32));')" \
  -d '{
    "sections": [
      "Esprit de la plateforme (Don et Troc)",
      "Objets interdits et limites",
      "Respect, courtoisie et rendez-vous",
      "Responsabilité de l'"'"'ULCO"
    ]
  }'
```

### Test 2: Vérifier status

```bash
curl -X GET http://127.0.0.1:8000/api/user/charte/status \
  -H "Cookie: PHPSESSID=..."
```

### Test 3: Non-authentifié (devrait retourner 401)

```bash
curl -X GET http://127.0.0.1:8000/api/user/charte/status \
  -H "Content-Type: application/json"

# Response:
# {"message": "Utilisateur non authentifié"}
```

---

## 📊 Database Schema

### CharteAgreement Table

```sql
CREATE TABLE charte_agreement (
  id INT PRIMARY KEY AUTO_INCREMENT,
  section_name VARCHAR(255) NOT NULL,
  agreed_at DATETIME NOT NULL IMMUTABLE,
  user_id UUID NOT NULL FOREIGN KEY,
  INDEX (user_id),
  CONSTRAINT fk_charte_user FOREIGN KEY (user_id) REFERENCES user(id)
);
```

### Sample Data

```sql
SELECT * FROM charte_agreement WHERE user_id = '550e8400-e29b-41d4-a716-446655440000';

Result:
┌────┬──────────────────────────────────┬─────────────────────┬──────────────────────────────────┐
│ id │ section_name                     │ agreed_at           │ user_id                          │
├────┼──────────────────────────────────┼─────────────────────┼──────────────────────────────────┤
│  1 │ Esprit de la plateforme...       │ 2026-02-23 14:32:10 │ 550e8400-e29b-41d4-a716... │
│  2 │ Objets interdits et limites      │ 2026-02-23 14:33:20 │ 550e8400-e29b-41d4-a716... │
│  3 │ Respect, courtoisie et...        │ 2026-02-23 14:34:30 │ 550e8400-e29b-41d4-a716... │
│  4 │ Responsabilité de l'ULCO         │ 2026-02-23 14:35:45 │ 550e8400-e29b-41d4-a716... │
└────┴──────────────────────────────────┴─────────────────────┴──────────────────────────────────┘
```

---

## 🔐 Security

### Authentication Required
- ✅ `@IsGranted('ROLE_USER')` on both endpoints
- ✅ User must be logged in
- ✅ Invalid token returns 401 Unauthorized

### Authorization
- User can only accept charte for themselves
- User ID inferred from `$this->getUser()`
- No user_id parameter needed in request

### Data Validation
- ✅ Sections array required
- ✅ Non-empty array validation
- ✅ Section names matched exactly

---

## 💾 Backend Logic

### CharteController@acceptCharte (Line-by-line)

```php
1. Get authenticated user (ROLE_USER enforced)
2. Validate user exists (return 401 if not)
3. Parse JSON request body
4. Extract sections array
5. Validate sections not empty (return 400 if empty)

6. FOR EACH section:
   - Check if user already accepted this section
   - IF NOT already accepted:
     * Create new CharteAgreement entity
     * Set user, sectionName, agreedAt (now)
     * Persist to entityManager
   
7. Flush all changes to database
8. Return JSON 200 with success message and timestamp
```

### Error Handling

```php
Try/Catch block catches:
- JSON parse errors
- Database persist errors
- Invalid entity state
- Returns 500 with error message
```

---

## 🎯 Integration Points

### Frontend Call Chain

```
CharteStepper.jsx (React)
  ↓
  onClick={() => handleFinalAccept()}
  ↓
  fetch('/api/user/charte/accept', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({sections: [...]})
  })
  ↓
  response.ok ? toast.success() : toast.error()
  ↓
  window.location.href = '/'
```

### Symfony Request Lifecycle

```
Browser POST /api/user/charte/accept
  ↓
Kernel (AppKernel)
  ↓
Security (Check ROLE_USER)
  ↓
Router (Match route)
  ↓
Controller (CharteController@acceptCharte)
  ↓
validate user
extract data
persist entities
  ↓
Response JSON (200)
```

---

## 📈 Monitoring

### Check Charte Status for User

```bash
# Get current user's charte acceptance status
php bin/console doctrine:query:dql \
  "SELECT c FROM App\Entity\CharteAgreement c WHERE c.user = :user" \
  --hydrate=array
```

### List All Charte Acceptances

```bash
# See all charte acceptances ordered by date
php bin/console doctrine:query:dql \
  "SELECT c FROM App\Entity\CharteAgreement c ORDER BY c.agreedAt DESC"
```

---

## 🚦 Status Codes

| Code | Meaning | Trigger |
|------|---------|---------|
| 200 | OK | Charte accepted successfully |
| 400 | Bad Request | Missing or invalid sections array |
| 401 | Unauthorized | User not authenticated |
| 500 | Server Error | Database or processing error |

---

## 📝 Notes

1. **Idempotency:** If same section accepted twice, only 1 entry created (check existing)
2. **Timestamp:** Auto-set to `new \DateTimeImmutable()` at save time
3. **User Reference:** Automatically tied to logged-in user via `$this->getUser()`
4. **No Pagination:** For small charte (4 sections), all returned at once

---

**All endpoints are CORS-disabled (same-origin only).**
**All requests must include valid PHPSESSID session cookie.**
