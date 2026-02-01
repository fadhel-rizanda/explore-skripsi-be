# FLOW BE – ADOPTION

---

## 1. Adopt

**POST** `/adopt`

- `pet.status_id` → **pending**
- `adoption.status_id` → **need_action**
- `adoption.stage_id` → **requirement**

---

## 2. Requirement

### Create Requirements

**POST** `/requirements`

- `adoption.status_id` → need_action
- `adoption.stage_id` → requirement
- `requirements.status_id` → pending

---

### Fill Requirement

**PATCH** `/requirements/fill`

- `adoption.status_id` → need_action
- `adoption.stage_id` → requirement
- `requirements.status_id` → inprogress

---

### Approve / Reject Requirement

**PATCH** `/requirements/approve`
**PATCH** `/requirements/reject`
**PATCH** `/requirements/cancel`

- `adoption.status_id` → need_action
- `adoption.stage_id` → requirement
- `requirements.status_id` → completed **OR** rejected **OR** cancelled

---

### Finalize Requirements

**PATCH** `/requirements/finalize`

- `adoption.status_id` → need_action
- `adoption.stage_id` → **meet-n-greet**
- `requirements.status_id` → completed **OR** rejected

---

## 3. Meet N Greet

### Create Meet N Greet

**POST** `/meet-n-greet`

- `adoption.status_id` → need_action
- `adoption.stage_id` → meet-n-greet
- `meet_n_greet.status_id` → inprogress

---

### Approve Meet N Greet Schedule

**PATCH** `/meet-n-greet/approve`

- `adoption.status_id` → in_progress
- `adoption.stage_id` → meet-n-greet
- `meet_n_greet.status_id` → inprogress

> ℹ️ Approve hanya menyetujui schedule, **bukan penyelesaian proses**

---

### Finalize Meet N Greet

**PATCH** `/meet-n-greet/finalize`

- `adoption.status_id` → need_action
- `adoption.stage_id` → **handover**
- `meet_n_greet.status_id` → completed

---

## 4. Handover

### Create Handover

**POST** `/handover`

- `adoption.status_id` → need_action
- `adoption.stage_id` → handover
- `handover.status_id` → inprogress
- `handover.meet_n_greet.status_id` → inprogress

---

### Create / Propose Meet N Greet (Handover Context)

**POST** `/handover/meet-n-greet`

- `adoption.status_id` → need_action
- `adoption.stage_id` → handover
- `handover.status_id` → inprogress
- `handover.meet_n_greet.status_id` → inprogress

---

### Approve Meet N Greet (Handover)

**PATCH** `/handover/meet-n-greet/approve`

- `adoption.status_id` → in_progress
- `adoption.stage_id` → handover
- `handover.status_id` → inprogress
- `handover.meet_n_greet.status_id` → inprogress

---

### Upload Handover Evidence

**PATCH** `/handover/evidence`

- `adoption.status_id` → need_action
- `adoption.stage_id` → handover
- `handover.status_id` → inprogress
- `handover.meet_n_greet.status_id` → inprogress

---

### Finalize Handover

**PATCH** `/handover/finalize`

- `adoption.status_id` → **completed**
- `adoption.stage_id` → handover
- `handover.status_id` → **completed**

---
