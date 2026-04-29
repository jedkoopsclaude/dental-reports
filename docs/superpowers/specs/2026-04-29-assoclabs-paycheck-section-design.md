# AssocLabs Paycheck Section

Add an admin-only Paycheck summary at the top of the existing **Associate Labs** view in `index.html`. The section reproduces the format of the legacy CodeIgniter `assoclabs` report and shows Dr. Thanh's monthly compensation breakdown.

## Behavior

- Section renders at the top of the `AssocLabs` view, only when `isAdmin === true`.
- Lab Case Details and Crown/Bridge Units sections remain below, unchanged, visible to everyone.
- Toggling admin off (locking) hides the section without refetching data.
- Section reacts to the same monthly nav as the rest of the view.

## Data

Two queries are added to the existing `Promise.all` block in `AssocLabs` (same shape as the queries `MonthlyStats` uses for `assocPatAmt` / `assocInsAmt` at `index.html:313-314`):

```sql
-- pattotal
SELECT SUM(SplitAmt) AS pattotal FROM paysplit
WHERE ProvNum=216 AND DatePay BETWEEN '${s}' AND '${e}'

-- instotal
SELECT SUM(cp.InsPayAmt) AS instotal FROM claimpayment cy
INNER JOIN claimproc cp ON cp.ClaimPaymentNum=cy.ClaimPaymentNum
WHERE cp.ProvNum=216 AND cy.CheckDate BETWEEN '${s}' AND '${e}'
```

The queries are duplicated from `MonthlyStats` rather than extracted to a server endpoint or shared hook. Reason: the existing codebase puts SQL inline in components; introducing shared state between two report views would be inconsistent with the established pattern for one query pair.

Derived values stored alongside `totalLabFees` in `data`:

| Field | Formula |
|---|---|
| `assocCollections` | `pat + ins` |
| `collections35` | `assocCollections * 0.35` |
| `labFees30` | `totalLabFees * 0.30` |
| `paycheck` | `collections35 - labFees30` |

The percentages 35 and 30 are hardcoded constants in the component, matching the existing compensation rule.

## Layout

A new `report-section` titled **Paycheck**, rendered above the existing Lab Case Details section. Inside it, a two-column `report-table` (label, money-aligned amount):

| Label | Amount |
|---|---|
| Dr. Thanh's Collections | `$<assocCollections>` |
| 35% | `$<collections35>` |
| Lab Fees | `$<totalLabFees>` |
| 30% | `$<labFees30>` |
| **Paycheck** | **`$<paycheck>`** |

The final row uses the existing `.total` CSS class (already in use by the Lab Fees Total row).

The April 2026 reference values from the legacy report — used as a manual sanity check during implementation — are:

| Label | Amount |
|---|---|
| Dr. Thanh's Collections | $34,423.17 |
| 35% | $12,048.11 |
| Lab Fees | $4,713.61 |
| 30% | $1,414.08 |
| Paycheck | $10,634.03 |

## Wiring

- `App` already has `isAdmin` in component state (`index.html:1548`).
- `<AssocLabs month={month} />` (`index.html:1608`) becomes `<AssocLabs month={month} isAdmin={isAdmin} />`.
- `function AssocLabs({ month })` (`index.html:511`) becomes `function AssocLabs({ month, isAdmin })`.
- Render `{isAdmin && <PaycheckSection ... />}` as the first child of the existing return value.

## Verification

After implementation, with the server running on port 3000:

1. Open `http://localhost:3000`, navigate to **Associate Labs**, set the month to April 2026.
2. Without admin (lock icon visible): only Lab Case Details and Crown/Bridge Units render.
3. Click the lock, enter the admin password, confirm unlock state.
4. The Paycheck section appears at the top with values matching the April 2026 reference table above.
5. Toggle admin off again: section disappears, lower sections remain.
6. Change the month and confirm Paycheck values update consistently with the lab fees total below.

## Out of Scope

- Crown-code list inconsistency between `AssocLabs` (4 codes) and project memory (5 codes including D6548). Not changed.
- Configurable percentages.
- Surfacing the Paycheck data anywhere outside the `AssocLabs` view.
- Server-side endpoint extraction for shared collections logic.
