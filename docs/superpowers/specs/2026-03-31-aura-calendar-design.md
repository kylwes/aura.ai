# Aura AI — Calendar & Task Planner Design Spec

## Product Overview

A smart calendar application that connects to external channels (Jira, Slack, Gmail, Notion, Google Calendar, GitHub) and uses AI to automatically convert incoming messages, tickets, and emails into prioritised tasks scheduled into the user's calendar.

## Tech Stack

- **Backend**: Laravel 13, PHP 8.4
- **Frontend**: Blade templates, Livewire 3, Alpine.js, Tailwind CSS v4
- **Build**: Vite 8, laravel-vite-plugin
- **Routing**: Livewire full-page components with `wire:navigate` for SPA-like transitions

## Visual Design System

### Color Palette

- **Primary accent**: Indigo-violet (`~#4338ca` / Tailwind `indigo-700`) — used for AI elements, CTAs, active states
- **Priority colors**: Red (urgent), Orange (high), Blue (medium), Green (low)
- **Neutral base light**: White / `neutral-50` / `neutral-100` backgrounds, `neutral-900` text
- **Neutral base dark**: `neutral-950` / `neutral-900` / `neutral-800` backgrounds, `neutral-100` text
- **Integration brand colors**: Used sparingly on source icon background circles (Slack purple, Jira blue, Gmail red, GitHub dark, Notion black)

### Typography

- Font: Inter (loaded via Google Fonts or self-hosted)
- Hierarchy: Bold headings, regular body, small muted labels (uppercase tracking-wide for field labels, matching reference)

### Design Tokens

- Border radius: `rounded-lg` (10px) for cards, `rounded-xl` (12px) for modals/large cards, `rounded-full` for pills/avatars
- Shadows: `shadow-sm` for event blocks, `shadow-xl` / `shadow-2xl` for modals and elevated panels
- Borders: 1px `neutral-200` (dark: `neutral-700`) for card borders, 3px left borders for accents
- Spacing: 8px base grid

### AI Visual Language

- Sparkle/stars icon for AI-related buttons and badges
- "AI Suggested" badge: Indigo outlined pill with sparkle icon
- AI reasoning sections: `indigo-50` background (dark: `indigo-950/20`), rounded-lg
- AI-scheduled task blocks: Dashed indigo border, `indigo-50` wash
- Subtle — never overdone

### Dark Mode

- Both light and dark variants from the start
- Tailwind `dark:` variants using class strategy
- Dark backgrounds: `neutral-950` (page), `neutral-900` (cards), `neutral-800` (elevated/inputs)
- Careful contrast preservation for priority colors and brand icons in dark mode

### Iconography

- Outline-style icons, 20–24px, consistent stroke weight (Heroicons or similar)
- Real brand SVG logos for integrations: Jira, Slack, Gmail, Notion, Google Calendar, GitHub, Linear, Asana, Microsoft Teams, Outlook

---

## Route Structure

| Route | Component | Description |
|-------|-----------|-------------|
| `/login` | Auth\Login | Login page |
| `/register` | Auth\Register | Registration page |
| `/forgot-password` | Auth\ForgotPassword | Password reset request |
| `/reset-password/{token}` | Auth\ResetPassword | Password reset form |
| `/verify-email` | Auth\VerifyEmail | Email verification notice |
| `/onboarding` | Onboarding | Post-register setup wizard |
| `/` | Calendar | Main weekly calendar view |
| `/settings` | Settings | Integrations & AI preferences |
| `/plan-summary` | PlanSummary | AI scheduling review |
| `/profile` | Profile | User profile management |

---

## Component Architecture

### Full-Page Livewire Components (routed)

- `Calendar` — Weekly time grid with event rendering
- `Settings` — Integration management and AI preferences
- `PlanSummary` — Post-auto-schedule review/approval
- `Profile` — User account management
- `Onboarding` — Post-registration setup wizard
- Auth pages — Login, Register, ForgotPassword, ResetPassword, VerifyEmail

### Shared Livewire Components

- `Sidebar` — Persistent left panel: mini calendar, integrations status, unscheduled task queue
- `InboxPanel` — Slide-over right panel: incoming feed from all channels
- `TaskDetailModal` — Modal: edit task fields, view AI reasoning
- `TopBar` — Navigation, view switcher, auto-schedule button, notifications, avatar

### Blade Components (stateless, reusable)

- `task-block` — Calendar event block (regular + AI-scheduled variants)
- `priority-badge` — Colored dot + label (Urgent/High/Medium/Low)
- `source-icon` — Brand SVG icon with colored background circle
- `ai-badge` — "AI Suggested" indigo shimmer pill
- `integration-card` — Logo + status + toggle for settings grid
- `confidence-indicator` — Visual confidence bar (3 segments)
- `inbox-item` — Single feed item with action buttons
- `plan-diff-row` — Before/after scheduling change card

### Event Communication

- Sidebar dispatches to Calendar (date selection, queue task clicks)
- TopBar "Auto-schedule" triggers navigation to PlanSummary with loading state
- InboxPanel slides in via Alpine from any page (triggered by notification bell or keyboard shortcut)
- TaskDetailModal opens on task block click, dispatches update events to Calendar
- PlanSummary "Approve All" navigates back to `/` with success toast

---

## Screen Specifications

### Screen 1: Main Calendar View (`/`)

**Layout**: Three-column — sidebar (260px fixed), center content (fluid time grid), right edge (InboxPanel overlay zone).

**Top Bar**
- Left: "Aura" logo + wordmark, "Today" button, prev/next chevrons, week range label ("Mar 30 – Apr 5, 2026")
- Center: View switcher pills — Day / Week (active) / Month
- Right: "Auto-schedule" indigo filled button with sparkle icon, notification bell with red badge count, user avatar dropdown

**Sidebar (left, persistent across routes)**
- Mini month calendar at top (current month, clickable dates, today highlighted in indigo)
- Connected integrations list: icon + name + green/grey status dot, each clickable to filter calendar
- "Unscheduled Tasks" queue at bottom: scrollable list of draggable task items showing title, source icon, priority dot. Items can be dragged onto the time grid.

**Time Grid**
- Y-axis: Hours 08:00–22:00, half-hour gridlines in `neutral-100` (dark: `neutral-800`)
- X-axis: 7 day columns (Mon–Sun)
- Today's column: subtle `indigo-50` background wash (dark: `indigo-950/20`)
- Current time indicator: horizontal red hairline across full width + small red dot on left edge, updates every minute via Alpine interval

**Event Blocks — Regular (meetings, calls)**
- Solid `neutral-100` background (dark: `neutral-800`), `neutral-600` left border (3px)
- Title in semibold, time range in muted small text
- `rounded-lg`, `shadow-sm`

**Event Blocks — AI-Scheduled Tasks**
- `indigo-50` background (dark: `indigo-950/30`), 1px dashed `indigo-300` border
- Left border: 3px solid `indigo-500`
- Content: task title, source icon (16px), duration label, priority dot
- `ai-badge` shimmer in top-right corner
- Hover: Quick-action toolbar fades in (Alpine) — edit, reschedule, done, dismiss icons

**Interactions**
- Drag-and-drop: Alpine.js drag for rescheduling task blocks within grid and dragging from unscheduled queue onto time slots
- Click task block: Opens TaskDetailModal
- Click notification bell: Toggles InboxPanel slide-over

---

### Screen 2: Incoming Feed / Inbox Panel

**Container**: Slide-over from right edge, 400px wide, full viewport height. Alpine transition `translate-x-full -> translate-x-0`, 200ms ease-out. No backdrop on desktop (calendar compresses), semi-transparent backdrop on mobile.

**Header**
- "Inbox" title + item count badge ("12 new")
- Filter bar: horizontal pill toggles — source (All / Jira / Slack / Gmail / GitHub / Notion), priority (All / High / Medium / Low), date range dropdown
- Close X button top-right

**Feed Items** — Vertical scrollable list of cards:
- Left: Source brand icon (24px) in colored background circle
- Top line: Channel/context label, muted small text ("Slack -> #dev-team", "Jira -> AUR-402")
- Middle: Message/ticket preview, 2-line truncated
- Right: Relative timestamp ("2h ago"), AI suggested priority badge + confidence indicator (3-segment bar)
- Bottom: Action buttons — "Accept as task" (indigo text), "Snooze" (neutral), "Dismiss" (neutral), "Edit priority" (neutral)
- Unread: Left `indigo-500` border (3px), fades on scroll-into-view

**Batch Actions** — Sticky bottom bar:
- "Accept all suggested" (indigo filled button)
- "Let AI decide" (indigo outlined button, sparkle icon)

**Empty State**: Centered illustration, "All caught up" message

---

### Screen 3: Task Detail / Edit Modal

**Container**: Centered modal, max-w `640px`. White card (dark: `neutral-900`), `rounded-xl`, `shadow-2xl`. Blurred backdrop overlay. Alpine transition: fade + scale 95% -> 100%, 150ms.

**Header**
- Left: Indigo vertical bar (4px) + "Edit Task" large semibold title
- Right: X close button

**Body (stacked fields)**
- **Task Title**: "TASK TITLE" uppercase muted label, large semibold editable text
- **Description**: "DESCRIPTION" label, left indigo border accent on text block, regular weight paragraph
- **Priority + Duration row** (side by side):
  - Priority: Three selectable pills — Urgent (red dot), High (orange dot), Mid (blue dot), active has subtle background fill
  - Estimated Duration: Editable time "2h 30m", "Total time" sublabel, "AI Suggested" badge
- **Source + Deadline row** (side by side):
  - Source: Link icon + "View in Jira: AUR-402" indigo text link (deep link)
  - Deadline: Calendar icon + date

**AI Reasoning Section** (collapsible)
- `indigo-50` background (dark: `indigo-950/20`), `rounded-lg`
- Header: Sparkle icon in indigo circle + "AI SCHEDULING LOGIC" uppercase indigo label + chevron toggle
- Body: Paragraph explaining scheduling rationale

**Footer**
- "Cancel" neutral text button
- "Reschedule" indigo filled button with sparkle icon

Reference: Matches provided task-detail.png design exactly.

---

### Screen 4: Integrations / Settings Page (`/settings`)

**Layout**: Same shell (TopBar + Sidebar), center content scrollable, max-w-4xl centered.

**Page Header**
- "Settings" large title, "Manage your integrations and AI preferences" muted subtitle
- Two tab pills: "Integrations" (active) / "AI Preferences"

**Integrations Tab — Card Grid** (3 columns, 2 on tablet)
Each card:
- White card (dark: `neutral-900`), `rounded-xl`, subtle border
- Brand logo (40px) centered top
- Integration name in semibold
- Status: Green dot + "Connected" or grey dot + "Disconnected"
- Connected: Toggle switch (indigo active) to pause/resume + "Configure" text link
- Disconnected: "Connect" indigo outlined button, slightly muted card appearance
- Available: Jira, Slack, Gmail, Notion, Google Calendar, GitHub, Linear, Asana, Microsoft Teams, Outlook

**Per-Integration Configure** (expands below card or sub-panel):
- Slack: Multi-select channels to watch
- Jira: Project picker + issue type filter
- Gmail: Label selector + sender filter
- GitHub: Repo picker + event types (issues, PRs, mentions)
- Each: "Save" button + "Disconnect" danger text link

**AI Preferences Tab** — Stacked form sections with dividers:
- **Working Hours**: Start/end time pickers, day-of-week checkboxes
- **Focus Time**: Toggle enable, preferred time range, min block duration
- **Task Scheduling**: Max task duration slider, buffer time pills (5/10/15/30 min), auto-break toggle
- **Priority Overrides**: Per-source default priority dropdowns
- Save button at bottom (indigo filled)

---

### Screen 5: AI Planning Summary (`/plan-summary`)

**Trigger**: "Auto-schedule" button in TopBar. Button shows spinner + "Planning..." during processing, then navigates to `/plan-summary`.

**Page Header**
- Sparkle icon + "AI Schedule Proposal" large title
- Subtitle: "Here's how I'd organize your upcoming tasks. Review and approve."
- Stats row: "8 tasks scheduled", "3h 45m total", "2 conflicts resolved" — muted pill badges

**Visual Diff Toggle** (optional, top):
- "Show on calendar" toggle revealing compact mini weekly calendar — existing events in neutral, proposed tasks in dashed indigo, same visual language as main calendar

**Proposed Changes List** — Vertical card stack:
Each card:
- Left: Source icon (24px) + priority dot
- Center: Task name (semibold), proposed time slot ("Tue Mar 31, 09:00 – 10:30"), duration badge. If moved: "Previously: Mon 14:00" muted strikethrough
- Right: "Approve" (green outlined, check icon), "Reschedule" (neutral outlined, clock icon), "Remove" (red text, X icon)
- Approved items: `green-50` background (dark: `green-950/20`) + green left border
- Removed items: Alpine fade-out transition

**Sticky Bottom Bar**
- Left: "Redo" neutral outlined button (clears all, re-runs AI)
- Right: "Approve All" indigo filled button with sparkle icon
- After approval: Navigates to `/` with updated calendar + success toast "Schedule updated"

---

### Screen 6: Auth Pages

**Shared Auth Layout**: Centered card (max-w-md) on `neutral-50` (dark: `neutral-950`) full page. Aura logo + wordmark at top. Subtle grid or gradient background pattern.

**Login (`/login`)**
- Email input, password input, "Remember me" checkbox
- "Sign in" indigo filled button, full width
- "Forgot password?" text link
- Divider "or continue with" — Google + GitHub OAuth buttons (outlined, brand icons)
- Bottom: "Don't have an account? Sign up" link

**Register (`/register`)**
- Full name, email, password, confirm password inputs
- "Create account" indigo filled button
- Same OAuth options
- Bottom: "Already have an account? Sign in" link

**Forgot Password (`/forgot-password`)**
- Email input + "Send reset link" button
- Success state: confirmation message with envelope icon

**Reset Password (`/reset-password/{token}`)**
- New password + confirm password inputs + "Reset password" button

**Email Verification (`/verify-email`)**
- Centered message: "Check your email", resend link

---

### Screen 7: Onboarding (`/onboarding`)

Post-registration 2–3 step wizard:
1. **Connect integrations**: Grid of integration cards, select and authorize
2. **Set working hours**: Time range picker + day checkboxes
3. **Import tasks**: Preview of detected tasks from connected sources, bulk accept

- Progress dots at top
- "Skip" link + "Next" / "Get started" indigo CTA per step

---

### Screen 8: Profile (`/profile`)

Same app shell layout. Center content max-w-2xl.

- Avatar upload (circle, click to change)
- Name + email editable fields
- Timezone selector dropdown
- Password change section (current + new + confirm)
- "Danger Zone" at bottom: "Delete account" red outlined button with confirmation modal

---

### Screen 9: 404 Page

Minimal full-page layout. Aura branding. Large "404" display, "Page not found" subtitle, "Back to calendar" indigo link.

---

## Responsive Behavior

- **Desktop (1440px+)**: Full three-column layout, all panels visible
- **Tablet (1024px)**: Sidebar collapses to icon-only (expandable), InboxPanel overlays, settings grid goes to 2 columns
- **Mobile (375px, bonus)**: Day view only, sidebar hidden behind hamburger, InboxPanel full-screen overlay, bottom navigation bar replaces TopBar

## Key Interactions

- **Drag-and-drop**: Reschedule tasks on calendar grid, drag from unscheduled queue
- **Hover states**: Task blocks show quick-action toolbar (edit, reschedule, done, dismiss)
- **Slide-in animation**: InboxPanel 200ms ease-out from right
- **Modal transitions**: Fade + scale 95%->100%, 150ms
- **Auto-schedule flow**: Button spinner -> navigate to PlanSummary -> approve -> toast -> return to calendar
- **wire:navigate**: SPA-like page transitions across all routes, persistent sidebar
