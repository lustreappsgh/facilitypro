# Changes In Commit `628dbc6`

## Requests

- Added `priority` to maintenance requests.
- Displayed priority in request lists, approvals, and details.
- Added `rejection_reason` for rejected requests.
- Prevented facility managers, maintenance managers, and managers from reviewing their own requests.
- Added bulk review/approval flow styling similar to bulk inspections/work orders.
- Moved bulk review entry beside the create request button.
- Clamped request descriptions to `3` rows on list/review pages, with full text on details pages.
- Separated manager-owned requests from subordinate facility manager requests in request views.
- Added manager and maintenance-manager own request cards to dashboards.

## Approval Settings

- Implemented request-type approve/reject settings.
- Final state is role-based, not user-based.
- Added role-level approve/reject controls in `Roles > Edit`.
- Managers now inherit review permissions from their role configuration.

## Todos

- Todo creation now allows selecting tasks for the current week.
- Todo week values still normalize to Monday-based `week_start`.

## Facilities

- Facility ordering changed to:
  - `facility type`
  - `parent`
  - `facility`
  - all ascending
- Managers creating requests, todos, or inspections now only see facilities directly managed by them.
- Facilities index no longer shows:
  - `Parent node`
  - `Any child count`
- Fixed facilities search SQL ambiguity by qualifying joined columns.

## Managers And Dashboards

- Manager dashboard user cards now include the manager themself.
- The manager’s own card is shown distinctly as `You`.
- Managers can view payment approvals read-only; only admins can take approval actions.

## Search

- Added live search to:
  - inspections index
  - todos index
  - maintenance requests index
- Search updates are debounced and preserve page state.

## Vendors And Work Orders

- Vendor is now optional when creating work orders.
- Added inline vendor creation support.
- Updated work-order create, edit, and bulk flows for optional vendor selection.

## Approval Queue

- Managers can open and view the approval queue.
- Only admins can approve, reject, or use bulk approval actions.

## Auth And Frontend Fixes

- Fixed auth-page and client route issues caused by generated route helper problems.
- Fixed related frontend/navigation issues after login.
- Fixed stale filter reset behavior on live-search pages.
- Fixed lint issues and a frontend build-breaking invalid character.

## Database

- Added migration for maintenance request priority.
- Added migration for rejection reason.
- Added migration for role-level review permissions on request types.
- Added migration for the earlier user-level request type permission table as part of the full worktree commit.

## Tests Added Or Updated

- Dashboard data tests
- Todo workflow tests
- Approval workflow tests
- Request priority, search, and facility ordering tests
- Work-order optional vendor tests
- Maintenance review restriction tests
- Role request type settings tests
