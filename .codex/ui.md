Below is the updated **Shadcn-Vue UI Agent specification**, with an explicit **Data Tables** mandate and standards. You can paste this into your `ui-agent.md` / `rules.md`.

---

# Shadcn-Vue UI Agent — “Industrial Enterprise Modern” (Updated with Data Tables)

## 1) Mission

Create enterprise-grade FacilityOS screens that are:

* **highly visual** (infographics, KPI tiles, timelines, progress)
* **data-dense** (tables as the primary data surface)
* **permission-aware**
* consistent with your Industrial Enterprise Modern tokens
* built with **Vue 3 + Inertia v2 + Tailwind v4 + shadcn-vue only**, and **lucide icons**.

---

## 2) Non-Negotiables (Hard Rules)

### 2.1 Strict shadcn-vue usage

* Use only components from `@/components/ui/*`.
* To publish additional shadcn components, first find the component you want to publish. Then, publish the component using npx: `npx shadcn@latest add switch`

```
In this example, the command will publish the Switch component to resources/js/components/ui/switch.tsx. Once the component has been published, you can use it in any of your pages:

import { Switch } from "@/components/ui/switch"
 
const MyPage = () => {
  return (
    <div>
      <Switch />
    </div>
  );
};
 
export default MyPage;
```

* use show me avaliable components in shadcn-vue registry to check for components first.
* Custom components allowed only when shadcn-vue does not exist or is not the right fit.
* Any custom component must compose shadcn primitives and include a short justification comment.

```
Example Prompts
Once the MCP server is configured, you can use natural language to interact with registries. Try one of the following prompts:

Browse & Search
Show me all available components in the shadcn registry
Find me a login form from the shadcn registry
Install Items
Add the button component to my project
Create a login form using shadcn components
```

### 2.2 Data Tables are mandatory where list data exists

* Any “Index/List” page must present records as a **shadcn-vue Table**.
* If the page includes list data, a table is the default view; cards are permitted only for summaries/KPIs, not as a replacement for the table.

### 2.3 Visual + semantic color coding

* Status and severity must be color-coded using consistent semantics:

  * Success: `text-success` + subtle green background
  * Warning: `text-yellow-700` + `bg-yellow-100`
  * Danger: `text-red-700` + `bg-red-100`
  * Primary highlight: `bg-primary` / `bg-primary/10`
* Never introduce arbitrary new colors.

### 2.4 Typography hierarchy

* `font-display`: titles, section headers, KPI numbers
* `font-sans`: navigation, table data, buttons
* Table headers: uppercase microtext

### 2.5 Layout rules

* Always use `AppShell`, Breadcrumbs, and `<Head title="...">`.
* Use responsive grids and dense but breathable spacing.

---

## 3) Data Table Standards (Required)

### 3.1 Table primitives

Use shadcn-vue:

* `Table`, `TableHeader`, `TableBody`, `TableRow`, `TableHead`, `TableCell`

### 3.2 Table container (FacilityOS standard)

Every table must be wrapped in a `Card` with:

* rounded-xl
* border
* subtle shadow
* overflow handling

### 3.3 Header spec (match design system)

* Header background: `bg-gray-50` (dark: subtle gray overlay)
* Header border: `border-b border-gray-100`
* Header text: `uppercase tracking-wider text-xs font-bold text-gray-500`

### 3.4 Row spec

* Row hover: `hover:bg-gray-50/50`
* Row border: `border-b border-gray-100`
* Primary cell text: `font-bold text-gray-900`
* Secondary text: `text-xs text-gray-500`

### 3.5 Table toolbar (mandatory on Index pages)

Above every table, include a toolbar that may contain:

* Search input (`Input`)
* Filters (`Select`, `Popover` for date range)
* “Create” CTA (`Button` primary) if user has create permission
* Optional: `DropdownMenu` for table actions (export, column toggles if implemented)

### 3.6 Table actions column (required)

* Rightmost column named **Actions**
* Use icon buttons:

  * `Button variant="ghost" size="icon"`
  * Lucide icons for view/edit
* Action visibility is permission-gated.

### 3.7 Pagination (required when paginated)

* Index pages must support pagination via existing `PaginationLinks`.
* Table footer should include:

  * pagination links
  * “showing X–Y of Z” text (if data available)

### 3.8 Empty, loading, error states

* Loading: `Skeleton` rows
* Empty: centered `Card` empty-state with icon + CTA
* Validation errors: per-field on forms, but index pages show a top `Alert` (if available) or a `Card` message.

---

## 4) Infographic Requirements (Still Mandatory)

Each page must include at least one of:

* KPI cards
* insight strip
* progress visualization
* timeline / recent activity
* status distribution tiles

**Index pages** should typically include:

* KPI strip (top) + table (main)

---

## 5) Component Playbook (Preferred Patterns)

### 5.1 KPI Cards

* Card with icon pill + label + big number
* Optional sub-metrics (“Overdue”, “Pending”, etc.)

### 5.2 Status Chips

* Use `Badge`; custom `StatusChip` allowed when dot semantics are required.

### 5.3 Tables as the “work surface”

* KPIs explain the system
* Tables are where the work happens

### 5.4 Row-level quick context

Whenever useful, rows should include:

* primary line (bold)
* secondary line (muted microtext)
  Example: facility name + facility type under it.

---

## 6) Iconography Rules (lucide-vue-next)

* Default sizes: `w-5 h-5` for UI, `w-4 h-4` for metadata
* Use meaningful icons per module:

  * Facilities: `Building2`
  * Inspections: `ClipboardCheck`
  * Maintenance: `Wrench`
  * Work Orders: `Hammer`
  * Vendors: `Handshake` (or `Users`)
  * Payments: `CreditCard`
  * Reports: `BarChart3`
  * Audit: `ShieldCheck`
  * Todos: `ListTodo`

---

## 7) Output Contract (What the Agent Produces)

For every page:

1. Vue SFC page (shadcn-vue + lucide icons + tokens)
2. Props shape (TS-like)
3. Permission map (blocks and actions)
4. Visual layout summary (KPI strip + table + secondary panels)
5. reference <https://laravel.com/docs/12.x/starter-kits> on how to add a shadcn-vue component. only create custom component if one does not exist.

---

## 8) Acceptance Criteria (Definition of Done)

A generated page is acceptable only if:

* It includes **a table** for record lists (Index pages)
* It includes **a visual summary** (KPI cards or infographic strip)
* All actions are permission-gated in UI **and** assumed enforced server-side
* No raw `<button>`, `<input>`, `<select>` unless using shadcn wrappers/components
* Consistent typography, spacing, and color semantics
