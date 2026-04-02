# Aura AI Calendar & Task Planner — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a full-featured AI calendar & task planner UI with 9 screens (calendar, inbox, task detail, settings, AI planning summary, auth pages, onboarding, profile, 404) using Livewire 3 + Alpine.js + Tailwind CSS v4.

**Architecture:** Livewire full-page components with `wire:navigate` for SPA-like routing. Persistent sidebar + top bar in a shared Blade layout. Stateless Blade components for reusable UI elements (task blocks, priority badges, source icons). Alpine.js handles client-side interactions (modals, slide-overs, drag-and-drop, transitions). All pages support light + dark mode.

**Tech Stack:** Laravel 13, Livewire 3, Alpine.js, Tailwind CSS v4, Vite 8, Pest 4

**Spec:** `docs/superpowers/specs/2026-03-31-aura-calendar-design.md`

**Reference image:** `/Users/kylianwester/Downloads/stitch/task-detail.png` (Task Detail modal design)

---

## File Map

### Dependencies & Config
- Modify: `composer.json` (add Livewire)
- Modify: `package.json` (add Inter font)
- Modify: `resources/css/app.css` (design tokens, dark mode, custom utilities)
- Modify: `vite.config.js` (no changes expected)
- Modify: `routes/web.php` (all routes)

### Layout
- Create: `resources/views/layouts/app.blade.php` (main app shell with sidebar + topbar)
- Create: `resources/views/layouts/auth.blade.php` (centered card auth layout)

### Blade Components (stateless)
- Create: `resources/views/components/task-block.blade.php`
- Create: `resources/views/components/priority-badge.blade.php`
- Create: `resources/views/components/source-icon.blade.php`
- Create: `resources/views/components/ai-badge.blade.php`
- Create: `resources/views/components/integration-card.blade.php`
- Create: `resources/views/components/confidence-indicator.blade.php`
- Create: `resources/views/components/inbox-item.blade.php`
- Create: `resources/views/components/plan-diff-row.blade.php`

### Brand SVG Icons
- Create: `resources/views/components/icons/jira.blade.php`
- Create: `resources/views/components/icons/slack.blade.php`
- Create: `resources/views/components/icons/gmail.blade.php`
- Create: `resources/views/components/icons/notion.blade.php`
- Create: `resources/views/components/icons/google-calendar.blade.php`
- Create: `resources/views/components/icons/github.blade.php`
- Create: `resources/views/components/icons/linear.blade.php`
- Create: `resources/views/components/icons/asana.blade.php`
- Create: `resources/views/components/icons/teams.blade.php`
- Create: `resources/views/components/icons/outlook.blade.php`
- Create: `resources/views/components/icons/sparkle.blade.php`

### Database
- Create: migration for `integrations` table
- Create: migration for `tasks` table
- Create: migration for `calendar_events` table
- Create: migration for `inbox_items` table
- Create: migration to add preference columns to `users` table
- Create: `app/Models/Integration.php`
- Create: `app/Models/Task.php`
- Create: `app/Models/CalendarEvent.php`
- Create: `app/Models/InboxItem.php`
- Modify: `app/Models/User.php` (add relationships + preference casts)
- Create: `database/factories/IntegrationFactory.php`
- Create: `database/factories/TaskFactory.php`
- Create: `database/factories/CalendarEventFactory.php`
- Create: `database/factories/InboxItemFactory.php`
- Create: `database/seeders/DemoSeeder.php`
- Create: `app/Enums/IntegrationType.php`
- Create: `app/Enums/IntegrationStatus.php`
- Create: `app/Enums/TaskPriority.php`
- Create: `app/Enums/TaskStatus.php`
- Create: `app/Enums/InboxItemStatus.php`

### Livewire Full-Page Components
- Create: `app/Livewire/Calendar.php`
- Create: `resources/views/livewire/calendar.blade.php`
- Create: `app/Livewire/Settings.php`
- Create: `resources/views/livewire/settings.blade.php`
- Create: `app/Livewire/PlanSummary.php`
- Create: `resources/views/livewire/plan-summary.blade.php`
- Create: `app/Livewire/Profile.php`
- Create: `resources/views/livewire/profile.blade.php`
- Create: `app/Livewire/Onboarding.php`
- Create: `resources/views/livewire/onboarding.blade.php`

### Livewire Shared Components
- Create: `app/Livewire/Sidebar.php`
- Create: `resources/views/livewire/sidebar.blade.php`
- Create: `app/Livewire/TopBar.php`
- Create: `resources/views/livewire/top-bar.blade.php`
- Create: `app/Livewire/InboxPanel.php`
- Create: `resources/views/livewire/inbox-panel.blade.php`
- Create: `app/Livewire/TaskDetailModal.php`
- Create: `resources/views/livewire/task-detail-modal.blade.php`

### Auth Pages
- Create: `app/Livewire/Auth/Login.php`
- Create: `resources/views/livewire/auth/login.blade.php`
- Create: `app/Livewire/Auth/Register.php`
- Create: `resources/views/livewire/auth/register.blade.php`
- Create: `app/Livewire/Auth/ForgotPassword.php`
- Create: `resources/views/livewire/auth/forgot-password.blade.php`
- Create: `app/Livewire/Auth/ResetPassword.php`
- Create: `resources/views/livewire/auth/reset-password.blade.php`
- Create: `app/Livewire/Auth/VerifyEmail.php`
- Create: `resources/views/livewire/auth/verify-email.blade.php`

### Error Pages
- Create: `resources/views/errors/404.blade.php`

### Tests
- Create: `tests/Feature/Models/IntegrationTest.php`
- Create: `tests/Feature/Models/TaskTest.php`
- Create: `tests/Feature/Models/CalendarEventTest.php`
- Create: `tests/Feature/Models/InboxItemTest.php`
- Create: `tests/Feature/Auth/LoginTest.php`
- Create: `tests/Feature/Auth/RegisterTest.php`
- Create: `tests/Feature/Auth/PasswordResetTest.php`
- Create: `tests/Feature/Livewire/CalendarTest.php`
- Create: `tests/Feature/Livewire/SettingsTest.php`
- Create: `tests/Feature/Livewire/PlanSummaryTest.php`
- Create: `tests/Feature/Livewire/ProfileTest.php`
- Create: `tests/Feature/Livewire/OnboardingTest.php`
- Create: `tests/Feature/Livewire/SidebarTest.php`
- Create: `tests/Feature/Livewire/TopBarTest.php`
- Create: `tests/Feature/Livewire/InboxPanelTest.php`
- Create: `tests/Feature/Livewire/TaskDetailModalTest.php`

---

## Phase 1: Foundation

### Task 1: Install Livewire and configure dependencies

**Files:**
- Modify: `composer.json`
- Modify: `package.json`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`

- [ ] **Step 1: Install Livewire via Composer**

```bash
cd /Users/kylianwester/Sites/Personal/aura.ai
composer require livewire/livewire --no-interaction
```

Expected: Livewire 3.x installed, auto-discovered.

- [ ] **Step 2: Verify Livewire is available**

```bash
php artisan livewire:list
```

Expected: Empty list (no components yet), no errors.

- [ ] **Step 3: Set up Tailwind CSS design tokens in app.css**

Replace `resources/css/app.css` with:

```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';

    --color-accent-50: oklch(0.97 0.02 277);
    --color-accent-100: oklch(0.94 0.04 277);
    --color-accent-200: oklch(0.88 0.08 277);
    --color-accent-300: oklch(0.79 0.13 277);
    --color-accent-400: oklch(0.68 0.17 277);
    --color-accent-500: oklch(0.59 0.20 277);
    --color-accent-600: oklch(0.52 0.20 277);
    --color-accent-700: oklch(0.46 0.19 277);
    --color-accent-800: oklch(0.39 0.16 277);
    --color-accent-900: oklch(0.33 0.13 277);
    --color-accent-950: oklch(0.24 0.10 277);

    --color-priority-urgent: oklch(0.64 0.21 25);
    --color-priority-high: oklch(0.75 0.18 55);
    --color-priority-medium: oklch(0.62 0.17 250);
    --color-priority-low: oklch(0.72 0.17 155);
}

@utility ai-shimmer {
    background: linear-gradient(
        135deg,
        var(--color-accent-50) 0%,
        var(--color-accent-100) 50%,
        var(--color-accent-50) 100%
    );
    background-size: 200% 200%;
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
```

- [ ] **Step 4: Update app.js to import Alpine (Livewire bundles it)**

`resources/js/app.js` — no changes needed. Livewire auto-injects Alpine. Keep as-is:

```js
import './bootstrap';
```

- [ ] **Step 5: Build frontend to verify no errors**

```bash
cd /Users/kylianwester/Sites/Personal/aura.ai
npm install && npm run build
```

Expected: Build completes with no errors.

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock resources/css/app.css
git commit -m "feat: install Livewire and configure Tailwind design tokens"
```

---

### Task 2: Create app layout with dark mode support

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/layouts/auth.blade.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create the main app layout**

Create `resources/views/layouts/app.blade.php`:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Aura' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100 antialiased">
    <div class="flex h-screen overflow-hidden">
        <livewire:sidebar />

        <div class="flex flex-1 flex-col overflow-hidden">
            <livewire:top-bar :title="$title ?? ''" />

            <main class="flex-1 overflow-auto">
                {{ $slot }}
            </main>
        </div>

        <livewire:inbox-panel />
        <livewire:task-detail-modal />
    </div>
</body>
</html>
```

- [ ] **Step 2: Create the auth layout**

Create `resources/views/layouts/auth.blade.php`:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Aura' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 dark:bg-neutral-950 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-12"
         style="background-image: radial-gradient(circle at 1px 1px, rgb(0 0 0 / 0.05) 1px, transparent 0); background-size: 24px 24px;">

        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="/" class="inline-flex items-center gap-2 text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                    <x-icons.sparkle class="size-8 text-accent-600" />
                    Aura
                </a>
            </div>

            <div class="rounded-xl bg-white p-8 shadow-lg ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/
git commit -m "feat: add app and auth layouts with dark mode support"
```

---

### Task 3: Create brand SVG icon components

**Files:**
- Create: `resources/views/components/icons/sparkle.blade.php`
- Create: `resources/views/components/icons/jira.blade.php`
- Create: `resources/views/components/icons/slack.blade.php`
- Create: `resources/views/components/icons/gmail.blade.php`
- Create: `resources/views/components/icons/notion.blade.php`
- Create: `resources/views/components/icons/google-calendar.blade.php`
- Create: `resources/views/components/icons/github.blade.php`
- Create: `resources/views/components/icons/linear.blade.php`
- Create: `resources/views/components/icons/asana.blade.php`
- Create: `resources/views/components/icons/teams.blade.php`
- Create: `resources/views/components/icons/outlook.blade.php`

- [ ] **Step 1: Create the sparkle AI icon**

Create `resources/views/components/icons/sparkle.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 2L13.09 8.26L18 6L14.74 10.91L21 12L14.74 13.09L18 18L13.09 15.74L12 22L10.91 15.74L6 18L9.26 13.09L3 12L9.26 10.91L6 6L10.91 8.26L12 2Z" fill="currentColor"/>
</svg>
```

- [ ] **Step 2: Create all brand integration icons**

Create `resources/views/components/icons/jira.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M11.53 2C11.53 2 4.24 8.3 4.24 12.18C4.24 14.89 6.05 17.14 8.52 17.93L11.53 14.92V2Z" fill="#2684FF"/>
    <path d="M12.47 2V14.92L15.48 17.93C17.95 17.14 19.76 14.89 19.76 12.18C19.76 8.3 12.47 2 12.47 2Z" fill="url(#jira-gradient)"/>
    <path d="M8.52 17.93C8.52 17.93 10.14 19.54 12 19.54C13.86 19.54 15.48 17.93 15.48 17.93L12 21.41L8.52 17.93Z" fill="url(#jira-gradient-2)"/>
    <defs>
        <linearGradient id="jira-gradient" x1="12.47" y1="14.92" x2="19.76" y2="12.18"><stop stop-color="#0052CC"/><stop offset="1" stop-color="#2684FF"/></linearGradient>
        <linearGradient id="jira-gradient-2" x1="8.52" y1="17.93" x2="15.48" y2="17.93"><stop stop-color="#2684FF"/><stop offset="1" stop-color="#0052CC"/></linearGradient>
    </defs>
</svg>
```

Create `resources/views/components/icons/slack.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313z" fill="#E01E5A"/>
    <path d="M8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312z" fill="#36C5F0"/>
    <path d="M18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312z" fill="#2EB67D"/>
    <path d="M15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z" fill="#ECB22E"/>
</svg>
```

Create `resources/views/components/icons/gmail.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z" fill="#EA4335"/>
</svg>
```

Create `resources/views/components/icons/notion.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M4.459 4.208c.746.606 1.026.56 2.428.466l13.215-.793c.28 0 .047-.28-.046-.326L18.09 2.17c-.466-.373-.98-.653-2.055-.56L3.01 2.636c-.466.046-.56.28-.373.466l1.822 1.106zm.793 3.172v13.915c0 .746.373 1.026 1.213.98l14.523-.84c.84-.046.933-.56.933-1.166V6.354c0-.606-.233-.933-.746-.886l-15.177.886c-.56.047-.746.28-.746.886v.14zm14.336.42c.093.42 0 .84-.42.886l-.7.14v10.264c-.607.327-1.166.514-1.633.514-.746 0-.933-.234-1.493-.934l-4.573-7.186v6.953l1.446.327s0 .84-1.166.84l-3.22.186c-.092-.186 0-.653.327-.746l.84-.233V8.96l-1.166-.093c-.093-.42.14-1.026.793-1.073l3.453-.233 4.76 7.279V8.353l-1.213-.14c-.093-.513.28-.886.746-.933l3.22-.186h-.001z" class="dark:fill-white fill-black"/>
</svg>
```

Create `resources/views/components/icons/google-calendar.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M18.316 5.684H5.684v12.632h12.632V5.684z" fill="#fff"/>
    <path d="M18.316 22.105L22.105 18.316V5.684L18.316 1.895z" fill="#EA4335"/>
    <path d="M5.684 22.105L1.895 18.316h0V5.684L5.684 1.895z" fill="#34A853"/>
    <path d="M18.316 1.895H5.684L1.895 5.684h20.21L18.316 1.895z" fill="#4285F4"/>
    <path d="M5.684 22.105h12.632l3.789-3.789H1.895l3.789 3.789z" fill="#FBBC04"/>
    <path d="M9.5 16.5v-1.2l2.5-2.8c.3-.4.5-.7.5-1.1 0-.5-.3-.9-.9-.9-.5 0-.9.3-.9.9H9.5c0-1.2.9-2.1 2.1-2.1s2.1.8 2.1 2c0 .7-.3 1.2-.8 1.8L11.1 15h2.6v1.5H9.5z" fill="#4285F4"/>
</svg>
```

Create `resources/views/components/icons/github.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" class="dark:fill-white fill-[#24292f]"/>
</svg>
```

Create `resources/views/components/icons/linear.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M2.513 12.833l8.654 8.654a9.545 9.545 0 01-8.654-8.654zm-.36-2.86a9.56 9.56 0 012.202-4.81L14.837 15.645a9.56 9.56 0 01-4.81 2.202L2.153 9.973zm3.628-6.105a9.545 9.545 0 0112.391 12.391L5.781 3.868z" fill="#5E6AD2"/>
</svg>
```

Create `resources/views/components/icons/asana.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M18.78 12.653a4.61 4.61 0 100 9.22 4.61 4.61 0 000-9.22zm-13.56 0a4.61 4.61 0 100 9.22 4.61 4.61 0 000-9.22zM12 2.127a4.61 4.61 0 100 9.22 4.61 4.61 0 000-9.22z" fill="#F06A6A"/>
</svg>
```

Create `resources/views/components/icons/teams.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M20.625 8.5h-5.75c-.69 0-1.25.56-1.25 1.25v5.75c0 .69.56 1.25 1.25 1.25h3.25v3.5c0 .966.784 1.75 1.75 1.75h0A1.75 1.75 0 0021.625 20.25v-10.5c0-.69-.31-1.25-1-1.25z" fill="#5059C9"/>
    <circle cx="19.5" cy="5.5" r="2.5" fill="#5059C9"/>
    <circle cx="12" cy="4.5" r="3.5" fill="#7B83EB"/>
    <path d="M16.25 8.5H7.75C7.06 8.5 6.5 9.06 6.5 9.75v6.75A4.5 4.5 0 0011 21h2a4.5 4.5 0 004.5-4.5V9.75c0-.69-.56-1.25-1.25-1.25z" fill="#7B83EB"/>
</svg>
```

Create `resources/views/components/icons/outlook.blade.php`:

```html
@props(['class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M24 7.387v10.478c0 .23-.08.424-.238.576a.806.806 0 01-.588.236h-8.174v-8.47l1.297.98 1.297-.98V7.15h5.58c.23 0 .424.08.588.237z" fill="#0364B8"/>
    <path d="M16.297 10.207l-1.297.98-1.297-.98V7.15l1.297.98 1.297-.98v3.057z" fill="#0A2767"/>
    <path d="M24 7.387H15v3.057l1.297.98L24 7.387z" fill="#28A8EA"/>
    <path d="M1.002 5.468h12.996v13.064H1.002z" fill="#0078D4"/>
    <path d="M7.5 9.5c-2.003 0-3.5 1.597-3.5 3.5s1.497 3.5 3.5 3.5S11 15.003 11 13s-1.497-3.5-3.5-3.5zm0 5.5c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z" fill="#fff"/>
</svg>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/icons/
git commit -m "feat: add brand SVG icons for all integrations and AI sparkle"
```

---

## Phase 2: Database Layer

### Task 4: Create enums for model types

**Files:**
- Create: `app/Enums/IntegrationType.php`
- Create: `app/Enums/IntegrationStatus.php`
- Create: `app/Enums/TaskPriority.php`
- Create: `app/Enums/TaskStatus.php`
- Create: `app/Enums/InboxItemStatus.php`

- [ ] **Step 1: Create IntegrationType enum**

```bash
php artisan make:enum IntegrationType --no-interaction
```

Replace `app/Enums/IntegrationType.php` with:

```php
<?php

namespace App\Enums;

enum IntegrationType: string
{
    case Jira = 'jira';
    case Slack = 'slack';
    case Gmail = 'gmail';
    case Notion = 'notion';
    case GoogleCalendar = 'google_calendar';
    case GitHub = 'github';
    case Linear = 'linear';
    case Asana = 'asana';
    case Teams = 'teams';
    case Outlook = 'outlook';

    public function label(): string
    {
        return match ($this) {
            self::Jira => 'Jira',
            self::Slack => 'Slack',
            self::Gmail => 'Gmail',
            self::Notion => 'Notion',
            self::GoogleCalendar => 'Google Calendar',
            self::GitHub => 'GitHub',
            self::Linear => 'Linear',
            self::Asana => 'Asana',
            self::Teams => 'Microsoft Teams',
            self::Outlook => 'Outlook',
        };
    }

    public function iconComponent(): string
    {
        return match ($this) {
            self::Jira => 'icons.jira',
            self::Slack => 'icons.slack',
            self::Gmail => 'icons.gmail',
            self::Notion => 'icons.notion',
            self::GoogleCalendar => 'icons.google-calendar',
            self::GitHub => 'icons.github',
            self::Linear => 'icons.linear',
            self::Asana => 'icons.asana',
            self::Teams => 'icons.teams',
            self::Outlook => 'icons.outlook',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Jira => '#2684FF',
            self::Slack => '#4A154B',
            self::Gmail => '#EA4335',
            self::Notion => '#000000',
            self::GoogleCalendar => '#4285F4',
            self::GitHub => '#24292F',
            self::Linear => '#5E6AD2',
            self::Asana => '#F06A6A',
            self::Teams => '#5059C9',
            self::Outlook => '#0078D4',
        };
    }
}
```

- [ ] **Step 2: Create IntegrationStatus enum**

```bash
php artisan make:enum IntegrationStatus --no-interaction
```

Replace `app/Enums/IntegrationStatus.php` with:

```php
<?php

namespace App\Enums;

enum IntegrationStatus: string
{
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Paused = 'paused';
}
```

- [ ] **Step 3: Create TaskPriority enum**

```bash
php artisan make:enum TaskPriority --no-interaction
```

Replace `app/Enums/TaskPriority.php` with:

```php
<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Urgent = 'urgent';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Urgent => 'Urgent',
            self::High => 'High',
            self::Medium => 'Mid',
            self::Low => 'Low',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Urgent => 'text-priority-urgent',
            self::High => 'text-priority-high',
            self::Medium => 'text-priority-medium',
            self::Low => 'text-priority-low',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::Urgent => 'bg-priority-urgent',
            self::High => 'bg-priority-high',
            self::Medium => 'bg-priority-medium',
            self::Low => 'bg-priority-low',
        };
    }
}
```

- [ ] **Step 4: Create TaskStatus enum**

```bash
php artisan make:enum TaskStatus --no-interaction
```

Replace `app/Enums/TaskStatus.php` with:

```php
<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Dismissed = 'dismissed';
    case Snoozed = 'snoozed';
}
```

- [ ] **Step 5: Create InboxItemStatus enum**

```bash
php artisan make:enum InboxItemStatus --no-interaction
```

Replace `app/Enums/InboxItemStatus.php` with:

```php
<?php

namespace App\Enums;

enum InboxItemStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Snoozed = 'snoozed';
    case Dismissed = 'dismissed';
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Enums/
git commit -m "feat: add enums for integration types, statuses, task priorities"
```

---

### Task 5: Create migrations

**Files:**
- Create: migration for `integrations` table
- Create: migration for `tasks` table
- Create: migration for `calendar_events` table
- Create: migration for `inbox_items` table
- Create: migration to add preferences to `users` table

- [ ] **Step 1: Create integrations migration**

```bash
php artisan make:migration create_integrations_table --no-interaction
```

Replace the migration's `up()` method with:

```php
public function up(): void
{
    Schema::create('integrations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('type');
        $table->string('status')->default('disconnected');
        $table->json('configuration')->nullable();
        $table->timestamp('connected_at')->nullable();
        $table->timestamps();

        $table->unique(['user_id', 'type']);
    });
}
```

- [ ] **Step 2: Create tasks migration**

```bash
php artisan make:migration create_tasks_table --no-interaction
```

Replace the migration's `up()` method with:

```php
public function up(): void
{
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('integration_id')->nullable()->constrained()->nullOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('source_url')->nullable();
        $table->string('source_reference')->nullable();
        $table->string('priority')->default('medium');
        $table->unsignedInteger('estimated_duration')->nullable();
        $table->timestamp('deadline')->nullable();
        $table->timestamp('scheduled_start')->nullable();
        $table->timestamp('scheduled_end')->nullable();
        $table->boolean('is_ai_scheduled')->default(false);
        $table->text('ai_reasoning')->nullable();
        $table->string('status')->default('pending');
        $table->timestamps();

        $table->index(['user_id', 'status']);
        $table->index(['user_id', 'scheduled_start']);
    });
}
```

- [ ] **Step 3: Create calendar_events migration**

```bash
php artisan make:migration create_calendar_events_table --no-interaction
```

Replace the migration's `up()` method with:

```php
public function up(): void
{
    Schema::create('calendar_events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('integration_id')->nullable()->constrained()->nullOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->timestamp('starts_at');
        $table->timestamp('ends_at');
        $table->boolean('is_all_day')->default(false);
        $table->string('external_id')->nullable();
        $table->timestamps();

        $table->index(['user_id', 'starts_at', 'ends_at']);
    });
}
```

- [ ] **Step 4: Create inbox_items migration**

```bash
php artisan make:migration create_inbox_items_table --no-interaction
```

Replace the migration's `up()` method with:

```php
public function up(): void
{
    Schema::create('inbox_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('integration_id')->nullable()->constrained()->nullOnDelete();
        $table->string('channel_name')->nullable();
        $table->text('preview_text');
        $table->string('source_url')->nullable();
        $table->string('ai_suggested_priority')->nullable();
        $table->unsignedTinyInteger('ai_confidence')->nullable();
        $table->string('status')->default('pending');
        $table->timestamp('snoozed_until')->nullable();
        $table->timestamps();

        $table->index(['user_id', 'status']);
    });
}
```

- [ ] **Step 5: Add user preferences migration**

```bash
php artisan make:migration add_preferences_to_users_table --no-interaction
```

Replace the migration's `up()` method with:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('timezone')->default('UTC');
        $table->string('avatar_url')->nullable();
        $table->time('working_hours_start')->default('09:00');
        $table->time('working_hours_end')->default('17:00');
        $table->json('working_days')->nullable();
        $table->boolean('focus_time_enabled')->default(false);
        $table->time('focus_time_start')->nullable();
        $table->time('focus_time_end')->nullable();
        $table->unsignedInteger('focus_time_min_duration')->default(60);
        $table->unsignedInteger('max_task_duration')->default(120);
        $table->unsignedInteger('buffer_time')->default(15);
        $table->timestamp('onboarded_at')->nullable();
    });
}
```

- [ ] **Step 6: Run migrations to verify**

```bash
php artisan migrate --no-interaction
```

Expected: All migrations run successfully.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/
git commit -m "feat: add migrations for integrations, tasks, calendar events, inbox items, user preferences"
```

---

### Task 6: Create models with relationships and factories

**Files:**
- Create: `app/Models/Integration.php`
- Create: `app/Models/Task.php`
- Create: `app/Models/CalendarEvent.php`
- Create: `app/Models/InboxItem.php`
- Modify: `app/Models/User.php`
- Create: `database/factories/IntegrationFactory.php`
- Create: `database/factories/TaskFactory.php`
- Create: `database/factories/CalendarEventFactory.php`
- Create: `database/factories/InboxItemFactory.php`

- [ ] **Step 1: Write model relationship tests**

Create `tests/Feature/Models/IntegrationTest.php`:

```php
<?php

use App\Models\Integration;
use App\Models\User;
use App\Enums\IntegrationType;
use App\Enums\IntegrationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $integration = Integration::factory()->create();

    expect($integration->user)->toBeInstanceOf(User::class);
});

it('casts type to IntegrationType enum', function () {
    $integration = Integration::factory()->create(['type' => 'jira']);

    expect($integration->type)->toBe(IntegrationType::Jira);
});

it('casts status to IntegrationStatus enum', function () {
    $integration = Integration::factory()->create(['status' => 'connected']);

    expect($integration->status)->toBe(IntegrationStatus::Connected);
});
```

Create `tests/Feature/Models/TaskTest.php`:

```php
<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Integration;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $task = Task::factory()->create();

    expect($task->user)->toBeInstanceOf(User::class);
});

it('optionally belongs to an integration', function () {
    $integration = Integration::factory()->create();
    $task = Task::factory()->create(['integration_id' => $integration->id]);

    expect($task->integration)->toBeInstanceOf(Integration::class);
});

it('casts priority to TaskPriority enum', function () {
    $task = Task::factory()->create(['priority' => 'urgent']);

    expect($task->priority)->toBe(TaskPriority::Urgent);
});

it('casts status to TaskStatus enum', function () {
    $task = Task::factory()->create(['status' => 'scheduled']);

    expect($task->status)->toBe(TaskStatus::Scheduled);
});

it('scopes to scheduled tasks for a date range', function () {
    $user = User::factory()->create();
    $inRange = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'scheduled_start' => now()->setTime(10, 0),
        'scheduled_end' => now()->setTime(11, 30),
    ]);
    $outOfRange = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'scheduled',
        'scheduled_start' => now()->addWeek(),
        'scheduled_end' => now()->addWeek()->addHour(),
    ]);
    $pending = Task::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);

    $results = Task::query()
        ->where('user_id', $user->id)
        ->where('status', 'scheduled')
        ->whereBetween('scheduled_start', [now()->startOfDay(), now()->endOfDay()])
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($inRange->id);
});
```

Create `tests/Feature/Models/CalendarEventTest.php`:

```php
<?php

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $event = CalendarEvent::factory()->create();

    expect($event->user)->toBeInstanceOf(User::class);
});

it('scopes events to a date range', function () {
    $user = User::factory()->create();
    $today = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => now()->setTime(14, 0),
        'ends_at' => now()->setTime(15, 0),
    ]);
    $nextWeek = CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHour(),
    ]);

    $results = CalendarEvent::query()
        ->where('user_id', $user->id)
        ->where('starts_at', '>=', now()->startOfDay())
        ->where('starts_at', '<=', now()->endOfDay())
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($today->id);
});
```

Create `tests/Feature/Models/InboxItemTest.php`:

```php
<?php

use App\Models\InboxItem;
use App\Models\User;
use App\Enums\InboxItemStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $item = InboxItem::factory()->create();

    expect($item->user)->toBeInstanceOf(User::class);
});

it('casts status to InboxItemStatus enum', function () {
    $item = InboxItem::factory()->create(['status' => 'pending']);

    expect($item->status)->toBe(InboxItemStatus::Pending);
});

it('scopes to pending items', function () {
    $user = User::factory()->create();
    $pending = InboxItem::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
    $accepted = InboxItem::factory()->create(['user_id' => $user->id, 'status' => 'accepted']);

    $results = InboxItem::query()
        ->where('user_id', $user->id)
        ->where('status', 'pending')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($pending->id);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=IntegrationTest --filter=TaskTest --filter=CalendarEventTest --filter=InboxItemTest
```

Expected: FAIL — models and factories don't exist yet.

- [ ] **Step 3: Create Integration model and factory**

Create `app/Models/Integration.php`:

```php
<?php

namespace App\Models;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'status', 'configuration', 'connected_at'])]
class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => IntegrationType::class,
            'status' => IntegrationStatus::class,
            'configuration' => 'array',
            'connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Create `database/factories/IntegrationFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Integration> */
class IntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(IntegrationType::cases()),
            'status' => IntegrationStatus::Connected,
            'configuration' => null,
            'connected_at' => now(),
        ];
    }

    public function disconnected(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationStatus::Disconnected,
            'connected_at' => null,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn () => [
            'status' => IntegrationStatus::Paused,
        ]);
    }
}
```

- [ ] **Step 4: Create Task model and factory**

Create `app/Models/Task.php`:

```php
<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'integration_id', 'title', 'description', 'source_url',
    'source_reference', 'priority', 'estimated_duration', 'deadline',
    'scheduled_start', 'scheduled_end', 'is_ai_scheduled', 'ai_reasoning', 'status',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'estimated_duration' => 'integer',
            'deadline' => 'datetime',
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'is_ai_scheduled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function formattedDuration(): string
    {
        if (! $this->estimated_duration) {
            return '';
        }

        $hours = intdiv($this->estimated_duration, 60);
        $minutes = $this->estimated_duration % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        }

        return $hours > 0 ? "{$hours}h" : "{$minutes}m";
    }
}
```

Create `database/factories/TaskFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'estimated_duration' => fake()->randomElement([30, 45, 60, 90, 120, 150, 180]),
            'status' => TaskStatus::Pending,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Scheduled,
            'scheduled_start' => now()->addHours(fake()->numberBetween(1, 8)),
            'scheduled_end' => now()->addHours(fake()->numberBetween(9, 12)),
            'is_ai_scheduled' => true,
            'ai_reasoning' => fake()->sentence(),
        ]);
    }

    public function withSource(): static
    {
        return $this->state(fn () => [
            'source_url' => fake()->url(),
            'source_reference' => 'AUR-' . fake()->numberBetween(100, 999),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => [
            'priority' => TaskPriority::Urgent,
            'deadline' => now()->addDay(),
        ]);
    }
}
```

- [ ] **Step 5: Create CalendarEvent model and factory**

Create `app/Models/CalendarEvent.php`:

```php
<?php

namespace App\Models;

use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'integration_id', 'title', 'description',
    'starts_at', 'ends_at', 'is_all_day', 'external_id',
])]
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
```

Create `database/factories/CalendarEventFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalendarEvent> */
class CalendarEventFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement([
                'Team Standup', 'Sprint Planning', 'Design Review',
                'Client Call', '1:1 with Manager', 'Lunch', 'Tech Talk',
            ]),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(fake()->randomElement([30, 60, 90])),
            'is_all_day' => false,
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->endOfDay(),
            'is_all_day' => true,
        ]);
    }
}
```

- [ ] **Step 6: Create InboxItem model and factory**

Create `app/Models/InboxItem.php`:

```php
<?php

namespace App\Models;

use App\Enums\InboxItemStatus;
use Database\Factories\InboxItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'integration_id', 'channel_name', 'preview_text',
    'source_url', 'ai_suggested_priority', 'ai_confidence', 'status', 'snoozed_until',
])]
class InboxItem extends Model
{
    /** @use HasFactory<InboxItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => InboxItemStatus::class,
            'ai_confidence' => 'integer',
            'snoozed_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
```

Create `database/factories/InboxItemFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\InboxItemStatus;
use App\Enums\TaskPriority;
use App\Models\InboxItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InboxItem> */
class InboxItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel_name' => fake()->randomElement(['#dev-team', '#design', '#general', 'inbox', 'AUR-' . fake()->numberBetween(100, 999)]),
            'preview_text' => fake()->sentence(10),
            'source_url' => fake()->url(),
            'ai_suggested_priority' => fake()->randomElement(TaskPriority::cases())->value,
            'ai_confidence' => fake()->numberBetween(1, 3),
            'status' => InboxItemStatus::Pending,
        ];
    }

    public function snoozed(): static
    {
        return $this->state(fn () => [
            'status' => InboxItemStatus::Snoozed,
            'snoozed_until' => now()->addHours(2),
        ]);
    }
}
```

- [ ] **Step 7: Update User model with relationships and preference casts**

Replace `app/Models/User.php` with:

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'email', 'password', 'timezone', 'avatar_url',
    'working_hours_start', 'working_hours_end', 'working_days',
    'focus_time_enabled', 'focus_time_start', 'focus_time_end',
    'focus_time_min_duration', 'max_task_duration', 'buffer_time',
    'onboarded_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'working_days' => 'array',
            'focus_time_enabled' => 'boolean',
            'focus_time_min_duration' => 'integer',
            'max_task_duration' => 'integer',
            'buffer_time' => 'integer',
            'onboarded_at' => 'datetime',
        ];
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function inboxItems(): HasMany
    {
        return $this->hasMany(InboxItem::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarded_at !== null;
    }
}
```

- [ ] **Step 8: Run model tests**

```bash
php artisan test --compact --filter=IntegrationTest --filter=TaskTest --filter=CalendarEventTest --filter=InboxItemTest
```

Expected: All tests PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Models/ database/factories/ tests/Feature/Models/
git commit -m "feat: add Integration, Task, CalendarEvent, InboxItem models with factories and tests"
```

---

### Task 7: Create demo seeder

**Files:**
- Create: `database/seeders/DemoSeeder.php`

- [ ] **Step 1: Create DemoSeeder**

```bash
php artisan make:seeder DemoSeeder --no-interaction
```

Replace `database/seeders/DemoSeeder.php` with:

```php
<?php

namespace Database\Seeders;

use App\Enums\IntegrationStatus;
use App\Enums\IntegrationType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\CalendarEvent;
use App\Models\InboxItem;
use App\Models\Integration;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Kylian Wester',
            'email' => 'kylian@aura.ai',
            'password' => 'password',
            'timezone' => 'Europe/Amsterdam',
            'working_hours_start' => '09:00',
            'working_hours_end' => '17:30',
            'working_days' => [1, 2, 3, 4, 5],
            'onboarded_at' => now(),
        ]);

        $jira = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::Jira,
            'status' => IntegrationStatus::Connected,
        ]);

        $slack = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::Slack,
            'status' => IntegrationStatus::Connected,
        ]);

        $gmail = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::Gmail,
            'status' => IntegrationStatus::Connected,
        ]);

        $github = Integration::factory()->create([
            'user_id' => $user->id,
            'type' => IntegrationType::GitHub,
            'status' => IntegrationStatus::Paused,
        ]);

        $monday = now()->startOfWeek();

        // Calendar events for the week
        foreach (range(0, 4) as $dayOffset) {
            $day = $monday->copy()->addDays($dayOffset);

            CalendarEvent::factory()->create([
                'user_id' => $user->id,
                'title' => 'Team Standup',
                'starts_at' => $day->copy()->setTime(9, 30),
                'ends_at' => $day->copy()->setTime(9, 45),
            ]);
        }

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Sprint Planning',
            'starts_at' => $monday->copy()->setTime(14, 0),
            'ends_at' => $monday->copy()->setTime(15, 30),
        ]);

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Design Review',
            'starts_at' => $monday->copy()->addDays(2)->setTime(11, 0),
            'ends_at' => $monday->copy()->addDays(2)->setTime(12, 0),
        ]);

        CalendarEvent::factory()->create([
            'user_id' => $user->id,
            'title' => 'Client Demo',
            'starts_at' => $monday->copy()->addDays(3)->setTime(15, 0),
            'ends_at' => $monday->copy()->addDays(3)->setTime(16, 0),
        ]);

        // Scheduled AI tasks
        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $jira->id,
            'title' => 'Quarterly Performance Review Design',
            'description' => 'Finalize the visual direction for the upcoming Q3 performance dashboard. Focus on the data visualization components and the atmospheric precision theme.',
            'source_url' => 'https://jira.example.com/browse/AUR-402',
            'source_reference' => 'AUR-402',
            'priority' => TaskPriority::Urgent,
            'estimated_duration' => 150,
            'deadline' => $monday->copy()->addDays(1),
            'scheduled_start' => $monday->copy()->setTime(10, 0),
            'scheduled_end' => $monday->copy()->setTime(12, 30),
            'is_ai_scheduled' => true,
            'ai_reasoning' => 'Scheduled before the "Sync Meeting" due to high priority and deadline tomorrow. We\'ve allocated this slot as it\'s your peak focus period based on past task completion rates.',
            'status' => TaskStatus::Scheduled,
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $slack->id,
            'title' => 'Review API integration docs',
            'description' => 'Review the new API documentation shared in #dev-team for the payment gateway migration.',
            'source_reference' => '#dev-team',
            'priority' => TaskPriority::High,
            'estimated_duration' => 45,
            'scheduled_start' => $monday->copy()->addDays(1)->setTime(10, 0),
            'scheduled_end' => $monday->copy()->addDays(1)->setTime(10, 45),
            'is_ai_scheduled' => true,
            'ai_reasoning' => 'Placed in your morning focus block on Tuesday. Related Jira tickets suggest this is blocking other team members.',
            'status' => TaskStatus::Scheduled,
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $gmail->id,
            'title' => 'Reply to partnership proposal',
            'description' => 'Draft a response to the partnership proposal from Acme Corp received via email.',
            'priority' => TaskPriority::Medium,
            'estimated_duration' => 30,
            'scheduled_start' => $monday->copy()->addDays(2)->setTime(14, 0),
            'scheduled_end' => $monday->copy()->addDays(2)->setTime(14, 30),
            'is_ai_scheduled' => true,
            'ai_reasoning' => 'Scheduled after Design Review on Wednesday. Medium priority, no hard deadline detected.',
            'status' => TaskStatus::Scheduled,
        ]);

        // Unscheduled tasks (in queue)
        Task::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $jira->id,
            'title' => 'Fix mobile nav overflow',
            'source_reference' => 'AUR-418',
            'priority' => TaskPriority::High,
            'estimated_duration' => 60,
            'status' => TaskStatus::Pending,
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Update README with new setup steps',
            'priority' => TaskPriority::Low,
            'estimated_duration' => 20,
            'status' => TaskStatus::Pending,
        ]);

        // Inbox items
        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $slack->id,
            'channel_name' => '#dev-team',
            'preview_text' => 'Hey, can someone review the PR for the auth refactor? It\'s been sitting for 2 days now.',
            'ai_suggested_priority' => TaskPriority::High->value,
            'ai_confidence' => 3,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $jira->id,
            'channel_name' => 'AUR-425',
            'preview_text' => 'New ticket: Implement dark mode toggle for dashboard settings page.',
            'ai_suggested_priority' => TaskPriority::Medium->value,
            'ai_confidence' => 2,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $gmail->id,
            'channel_name' => 'inbox',
            'preview_text' => 'Meeting notes from yesterday\'s product sync - action items for your team attached.',
            'ai_suggested_priority' => TaskPriority::Medium->value,
            'ai_confidence' => 2,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $github->id,
            'channel_name' => 'aura-ai/core',
            'preview_text' => 'Dependabot: Bump axios from 1.12.0 to 1.14.0 — security patch for CVE-2026-1234.',
            'ai_suggested_priority' => TaskPriority::Urgent->value,
            'ai_confidence' => 3,
        ]);

        InboxItem::factory()->create([
            'user_id' => $user->id,
            'integration_id' => $slack->id,
            'channel_name' => '#general',
            'preview_text' => 'Reminder: Team lunch on Friday at 12:30. Please RSVP in the thread.',
            'ai_suggested_priority' => TaskPriority::Low->value,
            'ai_confidence' => 3,
        ]);
    }
}
```

- [ ] **Step 2: Run the seeder to verify**

```bash
php artisan db:seed --class=DemoSeeder --no-interaction
```

Expected: Seeder runs with no errors.

- [ ] **Step 3: Commit**

```bash
git add database/seeders/DemoSeeder.php
git commit -m "feat: add demo seeder with realistic calendar, task, and inbox data"
```

---

## Phase 3: Reusable Blade Components

### Task 8: Create stateless Blade components

**Files:**
- Create: `resources/views/components/priority-badge.blade.php`
- Create: `resources/views/components/source-icon.blade.php`
- Create: `resources/views/components/ai-badge.blade.php`
- Create: `resources/views/components/confidence-indicator.blade.php`
- Create: `resources/views/components/task-block.blade.php`
- Create: `resources/views/components/inbox-item.blade.php`
- Create: `resources/views/components/integration-card.blade.php`
- Create: `resources/views/components/plan-diff-row.blade.php`

- [ ] **Step 1: Create priority-badge component**

Create `resources/views/components/priority-badge.blade.php`:

```html
@props(['priority'])

@php
    $dotColor = match($priority->value) {
        'urgent' => 'bg-priority-urgent',
        'high' => 'bg-priority-high',
        'medium' => 'bg-priority-medium',
        'low' => 'bg-priority-low',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs font-medium']) }}>
    <span class="size-2 rounded-full {{ $dotColor }}"></span>
    {{ $priority->label() }}
</span>
```

- [ ] **Step 2: Create source-icon component**

Create `resources/views/components/source-icon.blade.php`:

```html
@props(['type', 'size' => 'md'])

@php
    $sizeClass = match($size) {
        'sm' => 'size-6',
        'md' => 'size-8',
        'lg' => 'size-10',
    };
    $iconSize = match($size) {
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg $sizeClass"]) }}
      style="background-color: {{ $type->color() }}15;">
    <x-dynamic-component :component="$type->iconComponent()" :class="$iconSize" />
</span>
```

- [ ] **Step 3: Create ai-badge component**

Create `resources/views/components/ai-badge.blade.php`:

```html
@props(['label' => 'AI Suggested'])

<span {{ $attributes->merge(['class' => 'ai-shimmer inline-flex items-center gap-1 rounded-full border border-accent-200 px-2 py-0.5 text-xs font-medium text-accent-700 dark:border-accent-800 dark:text-accent-300']) }}>
    <x-icons.sparkle class="size-3" />
    {{ $label }}
</span>
```

- [ ] **Step 4: Create confidence-indicator component**

Create `resources/views/components/confidence-indicator.blade.php`:

```html
@props(['level'])

@php
    $level = min(3, max(1, (int) $level));
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} title="AI confidence: {{ $level }}/3">
    @for ($i = 1; $i <= 3; $i++)
        <span class="h-1.5 w-3 rounded-full {{ $i <= $level ? 'bg-accent-500' : 'bg-neutral-200 dark:bg-neutral-700' }}"></span>
    @endfor
</span>
```

- [ ] **Step 5: Create task-block component**

Create `resources/views/components/task-block.blade.php`:

```html
@props(['task', 'variant' => 'regular'])

@php
    $isAi = $variant === 'ai' || $task->is_ai_scheduled;
    $baseClasses = 'group relative cursor-pointer rounded-lg px-3 py-2 transition-shadow hover:shadow-md';
    $variantClasses = $isAi
        ? 'border border-dashed border-accent-300 bg-accent-50 dark:border-accent-700 dark:bg-accent-950/30'
        : 'border-l-3 border-neutral-400 bg-neutral-100 shadow-sm dark:border-neutral-600 dark:bg-neutral-800';
@endphp

<div {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}
     x-data="{ showActions: false }"
     @mouseenter="showActions = true"
     @mouseleave="showActions = false">

    @if ($isAi)
        <div class="absolute right-2 top-2">
            <x-icons.sparkle class="size-3 text-accent-400" />
        </div>
    @endif

    <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate pr-6">
        {{ $task->title }}
    </p>

    <div class="mt-1 flex items-center gap-2">
        @if ($task->integration)
            <x-source-icon :type="$task->integration->type" size="sm" />
        @endif

        @if ($task->estimated_duration)
            <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ $task->formattedDuration() }}</span>
        @endif

        <x-priority-badge :priority="$task->priority" />
    </div>

    {{-- Hover action toolbar --}}
    <div x-show="showActions"
         x-transition.opacity.duration.150ms
         class="absolute -top-8 right-0 flex items-center gap-1 rounded-lg bg-white p-1 shadow-lg ring-1 ring-neutral-200 dark:bg-neutral-800 dark:ring-neutral-700">
        <button class="rounded p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-700 dark:hover:text-neutral-300" title="Edit">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
        </button>
        <button class="rounded p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-700 dark:hover:text-neutral-300" title="Reschedule">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </button>
        <button class="rounded p-1 text-neutral-400 hover:bg-green-100 hover:text-green-600 dark:hover:bg-green-900/30 dark:hover:text-green-400" title="Done">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </button>
        <button class="rounded p-1 text-neutral-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/30 dark:hover:text-red-400" title="Dismiss">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
```

- [ ] **Step 6: Create inbox-item component**

Create `resources/views/components/inbox-item.blade.php`:

```html
@props(['item'])

@php
    $isUnread = $item->status === \App\Enums\InboxItemStatus::Pending;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg bg-white p-4 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800' . ($isUnread ? ' border-l-3 border-accent-500' : '')]) }}>
    <div class="flex items-start gap-3">
        @if ($item->integration)
            <x-source-icon :type="$item->integration->type" size="md" />
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    @if ($item->integration)
                        {{ $item->integration->type->label() }} &rarr; {{ $item->channel_name }}
                    @else
                        {{ $item->channel_name }}
                    @endif
                </p>
                <span class="text-xs text-neutral-400 dark:text-neutral-500">{{ $item->created_at->diffForHumans(short: true) }}</span>
            </div>

            <p class="mt-1 text-sm text-neutral-700 dark:text-neutral-300 line-clamp-2">{{ $item->preview_text }}</p>

            <div class="mt-2 flex items-center gap-2">
                @if ($item->ai_suggested_priority)
                    <x-priority-badge :priority="\App\Enums\TaskPriority::from($item->ai_suggested_priority)" />
                @endif
                @if ($item->ai_confidence)
                    <x-confidence-indicator :level="$item->ai_confidence" />
                @endif
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button class="text-xs font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400 dark:hover:text-accent-300">Accept as task</button>
                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                <button class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">Snooze</button>
                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                <button class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">Dismiss</button>
                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                <button class="text-xs text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">Edit priority</button>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 7: Create integration-card component**

Create `resources/views/components/integration-card.blade.php`:

```html
@props(['type', 'status' => null, 'integration' => null])

@php
    $isConnected = $status === \App\Enums\IntegrationStatus::Connected || $status === \App\Enums\IntegrationStatus::Paused;
    $isPaused = $status === \App\Enums\IntegrationStatus::Paused;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800 text-center' . (! $isConnected ? ' opacity-60' : '')]) }}>
    <div class="mx-auto mb-3 flex size-12 items-center justify-center">
        <x-dynamic-component :component="$type->iconComponent()" class="size-8" />
    </div>

    <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $type->label() }}</h3>

    <div class="mt-2 flex items-center justify-center gap-1.5">
        <span class="size-2 rounded-full {{ $isConnected ? ($isPaused ? 'bg-priority-high' : 'bg-priority-low') : 'bg-neutral-300 dark:bg-neutral-600' }}"></span>
        <span class="text-xs text-neutral-500 dark:text-neutral-400">
            {{ $isConnected ? ($isPaused ? 'Paused' : 'Connected') : 'Disconnected' }}
        </span>
    </div>

    <div class="mt-4">
        @if ($isConnected)
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" class="peer sr-only" {{ ! $isPaused ? 'checked' : '' }}>
                <div class="h-5 w-9 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-accent-600 peer-checked:after:translate-x-full dark:bg-neutral-700"></div>
            </label>
            <div class="mt-2">
                <button class="text-xs font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Configure</button>
            </div>
        @else
            <button class="rounded-lg border border-accent-300 px-4 py-1.5 text-xs font-medium text-accent-600 hover:bg-accent-50 dark:border-accent-700 dark:text-accent-400 dark:hover:bg-accent-950/30">
                Connect
            </button>
        @endif
    </div>
</div>
```

- [ ] **Step 8: Create plan-diff-row component**

Create `resources/views/components/plan-diff-row.blade.php`:

```html
@props(['task', 'approved' => false])

<div {{ $attributes->merge(['class' => 'rounded-lg bg-white p-4 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800 transition-colors' . ($approved ? ' border-l-3 border-priority-low bg-green-50 dark:bg-green-950/20' : '')]) }}>
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            @if ($task->integration)
                <x-source-icon :type="$task->integration->type" size="sm" />
            @endif
            <x-priority-badge :priority="$task->priority" />
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $task->title }}</p>
            <div class="mt-1 flex items-center gap-3 text-xs text-neutral-500 dark:text-neutral-400">
                @if ($task->scheduled_start)
                    <span>{{ $task->scheduled_start->format('D M j, H:i') }} – {{ $task->scheduled_end->format('H:i') }}</span>
                @endif
                @if ($task->estimated_duration)
                    <span class="rounded bg-neutral-100 px-1.5 py-0.5 dark:bg-neutral-800">{{ $task->formattedDuration() }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button class="inline-flex items-center gap-1 rounded-lg border border-priority-low/30 px-3 py-1.5 text-xs font-medium text-priority-low hover:bg-green-50 dark:hover:bg-green-950/20">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                Approve
            </button>
            <button class="inline-flex items-center gap-1 rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-medium text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Reschedule
            </button>
            <button class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</div>
```

- [ ] **Step 9: Commit**

```bash
git add resources/views/components/
git commit -m "feat: add reusable Blade components — task-block, priority-badge, source-icon, inbox-item, integration-card, plan-diff-row, ai-badge, confidence-indicator"
```

---

## Phase 4: App Shell (TopBar + Sidebar)

### Task 9: Create TopBar Livewire component

**Files:**
- Create: `app/Livewire/TopBar.php`
- Create: `resources/views/livewire/top-bar.blade.php`
- Create: `tests/Feature/Livewire/TopBarTest.php`

- [ ] **Step 1: Write TopBar test**

Create `tests/Feature/Livewire/TopBarTest.php`:

```php
<?php

use App\Livewire\TopBar;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the top bar', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('Aura')
        ->assertSee('Auto-schedule')
        ->assertStatus(200);
});

it('shows pending inbox count as badge', function () {
    $user = User::factory()->create();
    \App\Models\InboxItem::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('3');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=TopBarTest
```

Expected: FAIL — TopBar component doesn't exist.

- [ ] **Step 3: Create TopBar Livewire component**

Create `app/Livewire/TopBar.php`:

```php
<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Livewire\Component;

class TopBar extends Component
{
    public string $title = '';

    public string $currentView = 'week';

    public Carbon $weekStart;

    public function mount(string $title = ''): void
    {
        $this->title = $title;
        $this->weekStart = now()->startOfWeek();
    }

    public function previousWeek(): void
    {
        $this->weekStart = $this->weekStart->subWeek();
        $this->dispatch('week-changed', start: $this->weekStart->toDateString());
    }

    public function nextWeek(): void
    {
        $this->weekStart = $this->weekStart->addWeek();
        $this->dispatch('week-changed', start: $this->weekStart->toDateString());
    }

    public function goToToday(): void
    {
        $this->weekStart = now()->startOfWeek();
        $this->dispatch('week-changed', start: $this->weekStart->toDateString());
    }

    public function pendingInboxCount(): int
    {
        return auth()->user()->inboxItems()->where('status', 'pending')->count();
    }

    public function render()
    {
        return view('livewire.top-bar', [
            'inboxCount' => $this->pendingInboxCount(),
            'weekLabel' => $this->weekStart->format('M j') . ' – ' . $this->weekStart->copy()->endOfWeek()->format('M j, Y'),
        ]);
    }
}
```

- [ ] **Step 4: Create TopBar Blade view**

Create `resources/views/livewire/top-bar.blade.php`:

```html
<header class="flex h-14 items-center justify-between border-b border-neutral-200 bg-white px-4 dark:border-neutral-800 dark:bg-neutral-900">
    {{-- Left: Logo + navigation --}}
    <div class="flex items-center gap-4">
        <a href="/" wire:navigate class="flex items-center gap-2 text-lg font-bold text-neutral-900 dark:text-neutral-100">
            <x-icons.sparkle class="size-6 text-accent-600" />
            <span>Aura</span>
        </a>

        <div class="ml-4 flex items-center gap-1">
            <button wire:click="goToToday"
                    class="rounded-lg border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                Today
            </button>

            <button wire:click="previousWeek" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>

            <button wire:click="nextWeek" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>

            <span class="ml-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $weekLabel }}</span>
        </div>
    </div>

    {{-- Center: View switcher --}}
    <div class="flex items-center rounded-lg bg-neutral-100 p-0.5 dark:bg-neutral-800">
        @foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $value => $label)
            <button wire:click="$set('currentView', '{{ $value }}')"
                    class="rounded-md px-3 py-1 text-xs font-medium transition-colors {{ $currentView === $value ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Right: Actions --}}
    <div class="flex items-center gap-3">
        <a href="/plan-summary" wire:navigate
           class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-4 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
            <x-icons.sparkle class="size-3.5" />
            Auto-schedule
        </a>

        {{-- Notification bell --}}
        <button x-data @click="$dispatch('toggle-inbox')"
                class="relative rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            @if ($inboxCount > 0)
                <span class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                    {{ $inboxCount > 9 ? '9+' : $inboxCount }}
                </span>
            @endif
        </button>

        {{-- Dark mode toggle --}}
        <button x-data @click="darkMode = !darkMode"
                class="rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
            <svg x-show="!darkMode" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
            <svg x-show="darkMode" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
        </button>

        {{-- User avatar --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex size-8 items-center justify-center rounded-full bg-accent-100 text-sm font-semibold text-accent-700 dark:bg-accent-900 dark:text-accent-300">
                {{ substr(auth()->user()->name, 0, 1) }}
            </button>
            <div x-show="open" @click.away="open = false" x-transition
                 class="absolute right-0 mt-2 w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-neutral-200 dark:bg-neutral-800 dark:ring-neutral-700 z-50">
                <a href="/profile" wire:navigate class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 dark:text-neutral-300 dark:hover:bg-neutral-700">Profile</a>
                <a href="/settings" wire:navigate class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50 dark:text-neutral-300 dark:hover:bg-neutral-700">Settings</a>
                <hr class="my-1 border-neutral-200 dark:border-neutral-700">
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-50 dark:text-neutral-300 dark:hover:bg-neutral-700">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</header>
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=TopBarTest
```

Expected: All PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/TopBar.php resources/views/livewire/top-bar.blade.php tests/Feature/Livewire/TopBarTest.php
git commit -m "feat: add TopBar Livewire component with navigation, view switcher, and inbox badge"
```

---

### Task 10: Create Sidebar Livewire component

**Files:**
- Create: `app/Livewire/Sidebar.php`
- Create: `resources/views/livewire/sidebar.blade.php`
- Create: `tests/Feature/Livewire/SidebarTest.php`

- [ ] **Step 1: Write Sidebar test**

Create `tests/Feature/Livewire/SidebarTest.php`:

```php
<?php

use App\Livewire\Sidebar;
use App\Models\User;
use App\Models\Task;
use App\Models\Integration;
use App\Enums\IntegrationType;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the sidebar', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Sidebar::class)
        ->assertSee('Unscheduled Tasks')
        ->assertStatus(200);
});

it('shows connected integrations', function () {
    $user = User::factory()->create();
    Integration::factory()->create(['user_id' => $user->id, 'type' => IntegrationType::Jira, 'status' => 'connected']);

    Livewire::actingAs($user)
        ->test(Sidebar::class)
        ->assertSee('Jira');
});

it('shows unscheduled tasks', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Fix the bug', 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test(Sidebar::class)
        ->assertSee('Fix the bug');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=SidebarTest
```

Expected: FAIL.

- [ ] **Step 3: Create Sidebar Livewire component**

Create `app/Livewire/Sidebar.php`:

```php
<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Livewire\Component;

class Sidebar extends Component
{
    public Carbon $viewMonth;

    public function mount(): void
    {
        $this->viewMonth = now()->startOfMonth();
    }

    public function previousMonth(): void
    {
        $this->viewMonth = $this->viewMonth->subMonth();
    }

    public function nextMonth(): void
    {
        $this->viewMonth = $this->viewMonth->addMonth();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.sidebar', [
            'integrations' => $user->integrations()->get(),
            'unscheduledTasks' => $user->tasks()->where('status', 'pending')->orderBy('priority')->get(),
            'calendarDays' => $this->buildMiniCalendar(),
        ]);
    }

    /** @return array<int, array{date: Carbon, inMonth: bool, isToday: bool}> */
    private function buildMiniCalendar(): array
    {
        $start = $this->viewMonth->copy()->startOfWeek();
        $end = $this->viewMonth->copy()->endOfMonth()->endOfWeek();

        $days = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $days[] = [
                'date' => $current->copy(),
                'inMonth' => $current->month === $this->viewMonth->month,
                'isToday' => $current->isToday(),
            ];
            $current->addDay();
        }

        return $days;
    }
}
```

- [ ] **Step 4: Create Sidebar Blade view**

Create `resources/views/livewire/sidebar.blade.php`:

```html
<aside class="flex w-[260px] flex-col border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
    {{-- Mini month calendar --}}
    <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $viewMonth->format('F Y') }}</span>
            <div class="flex gap-1">
                <button wire:click="previousMonth" class="rounded p-0.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
                <button wire:click="nextMonth" class="rounded p-0.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-0 text-center">
            @foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $day)
                <span class="py-1 text-[10px] font-medium text-neutral-400 dark:text-neutral-500">{{ $day }}</span>
            @endforeach

            @foreach ($calendarDays as $day)
                <button class="flex size-7 items-center justify-center rounded-full text-xs transition-colors
                    {{ $day['isToday'] ? 'bg-accent-600 font-semibold text-white' : '' }}
                    {{ ! $day['isToday'] && $day['inMonth'] ? 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800' : '' }}
                    {{ ! $day['inMonth'] ? 'text-neutral-300 dark:text-neutral-600' : '' }}">
                    {{ $day['date']->day }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Connected integrations --}}
    <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Integrations</h3>
        <div class="space-y-1.5">
            @forelse ($integrations as $integration)
                <div class="flex items-center gap-2">
                    <x-source-icon :type="$integration->type" size="sm" />
                    <span class="flex-1 text-xs text-neutral-700 dark:text-neutral-300">{{ $integration->type->label() }}</span>
                    <span class="size-2 rounded-full {{ $integration->status->value === 'connected' ? 'bg-priority-low' : ($integration->status->value === 'paused' ? 'bg-priority-high' : 'bg-neutral-300 dark:bg-neutral-600') }}"></span>
                </div>
            @empty
                <a href="/settings" wire:navigate class="text-xs text-accent-600 hover:text-accent-700 dark:text-accent-400">Connect an integration</a>
            @endforelse
        </div>
    </div>

    {{-- Unscheduled tasks queue --}}
    <div class="flex-1 overflow-auto p-4">
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Unscheduled Tasks</h3>
        <div class="space-y-2">
            @forelse ($unscheduledTasks as $task)
                <div class="cursor-grab rounded-lg border border-neutral-200 bg-neutral-50 p-2.5 transition-shadow hover:shadow-sm active:cursor-grabbing dark:border-neutral-700 dark:bg-neutral-800"
                     draggable="true">
                    <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 truncate">{{ $task->title }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        @if ($task->integration)
                            <x-source-icon :type="$task->integration->type" size="sm" />
                        @endif
                        <x-priority-badge :priority="$task->priority" />
                        @if ($task->estimated_duration)
                            <span class="text-[10px] text-neutral-400">{{ $task->formattedDuration() }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-neutral-400 dark:text-neutral-500">No unscheduled tasks</p>
            @endforelse
        </div>
    </div>
</aside>
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=SidebarTest
```

Expected: All PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Sidebar.php resources/views/livewire/sidebar.blade.php tests/Feature/Livewire/SidebarTest.php
git commit -m "feat: add Sidebar component with mini calendar, integrations status, and task queue"
```

---

## Phase 5: Main Screens

### Task 11: Create Calendar Livewire page

**Files:**
- Create: `app/Livewire/Calendar.php`
- Create: `resources/views/livewire/calendar.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Livewire/CalendarTest.php`

- [ ] **Step 1: Write Calendar test**

Create `tests/Feature/Livewire/CalendarTest.php`:

```php
<?php

use App\Livewire\Calendar;
use App\Models\User;
use App\Models\Task;
use App\Models\CalendarEvent;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the calendar page', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->assertStatus(200);
});

it('shows calendar events for the current week', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    CalendarEvent::factory()->create([
        'user_id' => $user->id,
        'title' => 'Team Standup',
        'starts_at' => now()->startOfWeek()->setTime(9, 30),
        'ends_at' => now()->startOfWeek()->setTime(9, 45),
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->assertSee('Team Standup');
});

it('shows scheduled tasks on the calendar', function () {
    $user = User::factory()->create(['onboarded_at' => now()]);
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Review PR',
        'status' => 'scheduled',
        'scheduled_start' => now()->startOfWeek()->setTime(10, 0),
        'scheduled_end' => now()->startOfWeek()->setTime(11, 0),
        'is_ai_scheduled' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Calendar::class)
        ->assertSee('Review PR');
});

it('requires authentication', function () {
    $this->get('/')->assertRedirect('/login');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=CalendarTest
```

Expected: FAIL.

- [ ] **Step 3: Create Calendar Livewire component**

Create `app/Livewire/Calendar.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Calendar — Aura')]
class Calendar extends Component
{
    public Carbon $weekStart;

    public function mount(): void
    {
        $this->weekStart = now()->startOfWeek();
    }

    #[On('week-changed')]
    public function onWeekChanged(string $start): void
    {
        $this->weekStart = Carbon::parse($start);
    }

    public function render()
    {
        $user = auth()->user();
        $weekEnd = $this->weekStart->copy()->endOfWeek();

        $events = $user->calendarEvents()
            ->where('starts_at', '>=', $this->weekStart)
            ->where('starts_at', '<=', $weekEnd)
            ->orderBy('starts_at')
            ->get();

        $tasks = $user->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('scheduled_start', '>=', $this->weekStart)
            ->where('scheduled_start', '<=', $weekEnd)
            ->with('integration')
            ->orderBy('scheduled_start')
            ->get();

        $days = collect(range(0, 6))->map(fn (int $i) => $this->weekStart->copy()->addDays($i));
        $hours = collect(range(8, 21));

        return view('livewire.calendar', [
            'events' => $events,
            'tasks' => $tasks,
            'days' => $days,
            'hours' => $hours,
        ]);
    }

    /** @return Collection<int, \App\Models\CalendarEvent> */
    public function eventsForDayHour(Collection $events, Carbon $day, int $hour): Collection
    {
        return $events->filter(function ($event) use ($day, $hour) {
            return $event->starts_at->isSameDay($day) && $event->starts_at->hour === $hour;
        });
    }

    /** @return Collection<int, \App\Models\Task> */
    public function tasksForDayHour(Collection $tasks, Carbon $day, int $hour): Collection
    {
        return $tasks->filter(function ($task) use ($day, $hour) {
            return $task->scheduled_start->isSameDay($day) && $task->scheduled_start->hour === $hour;
        });
    }
}
```

- [ ] **Step 4: Create Calendar Blade view**

Create `resources/views/livewire/calendar.blade.php`:

```html
<div class="flex-1 overflow-auto">
    <div class="min-w-[700px]">
        {{-- Day headers --}}
        <div class="sticky top-0 z-10 grid grid-cols-[60px_repeat(7,1fr)] border-b border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
            <div class="border-r border-neutral-200 dark:border-neutral-800"></div>
            @foreach ($days as $day)
                <div class="border-r border-neutral-200 px-2 py-3 text-center last:border-r-0 dark:border-neutral-800 {{ $day->isToday() ? 'bg-accent-50/50 dark:bg-accent-950/20' : '' }}">
                    <p class="text-xs font-medium text-neutral-400 dark:text-neutral-500">{{ $day->format('D') }}</p>
                    <p class="mt-0.5 text-lg font-semibold {{ $day->isToday() ? 'text-accent-600 dark:text-accent-400' : 'text-neutral-900 dark:text-neutral-100' }}">{{ $day->format('j') }}</p>
                </div>
            @endforeach
        </div>

        {{-- Time grid --}}
        <div class="relative grid grid-cols-[60px_repeat(7,1fr)]">
            {{-- Hour labels + rows --}}
            @foreach ($hours as $hour)
                {{-- Hour label --}}
                <div class="relative border-r border-neutral-200 dark:border-neutral-800" style="grid-row: {{ $loop->iteration }};">
                    <span class="absolute -top-2.5 right-2 text-[10px] font-medium text-neutral-400 dark:text-neutral-500">
                        {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                    </span>
                </div>

                {{-- Day cells for this hour --}}
                @foreach ($days as $day)
                    <div class="relative min-h-[60px] border-b border-r border-neutral-100 last:border-r-0 dark:border-neutral-800/50 {{ $day->isToday() ? 'bg-accent-50/30 dark:bg-accent-950/10' : '' }}"
                         style="grid-row: {{ $loop->parent->iteration }};">

                        {{-- Calendar events --}}
                        @foreach ($this->eventsForDayHour($events, $day, $hour) as $event)
                            @php
                                $durationMinutes = $event->starts_at->diffInMinutes($event->ends_at);
                                $heightPx = max(30, ($durationMinutes / 60) * 60);
                                $topOffset = $event->starts_at->minute;
                            @endphp
                            <div class="absolute inset-x-1 z-[5] rounded-lg border-l-3 border-neutral-400 bg-neutral-100 px-2 py-1 shadow-sm dark:border-neutral-600 dark:bg-neutral-800"
                                 style="top: {{ $topOffset }}px; height: {{ $heightPx }}px;">
                                <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 truncate">{{ $event->title }}</p>
                                <p class="text-[10px] text-neutral-500 dark:text-neutral-400">{{ $event->starts_at->format('H:i') }} – {{ $event->ends_at->format('H:i') }}</p>
                            </div>
                        @endforeach

                        {{-- AI-scheduled tasks --}}
                        @foreach ($this->tasksForDayHour($tasks, $day, $hour) as $task)
                            @php
                                $durationMinutes = $task->scheduled_start->diffInMinutes($task->scheduled_end);
                                $heightPx = max(30, ($durationMinutes / 60) * 60);
                                $topOffset = $task->scheduled_start->minute;
                            @endphp
                            <div class="absolute inset-x-1 z-[5] cursor-pointer"
                                 style="top: {{ $topOffset }}px; height: {{ $heightPx }}px;"
                                 x-data @click="$dispatch('open-task-modal', { taskId: {{ $task->id }} })">
                                <x-task-block :task="$task" />
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endforeach

            {{-- Current time indicator --}}
            @if ($weekStart->isCurrentWeek())
                <div class="pointer-events-none absolute left-[60px] right-0 z-20"
                     x-data="{ top: 0 }"
                     x-init="
                        const updatePosition = () => {
                            const now = new Date();
                            const hours = now.getHours() - 8;
                            const minutes = now.getMinutes();
                            top = (hours * 60) + minutes;
                        };
                        updatePosition();
                        setInterval(updatePosition, 60000);
                     "
                     :style="'top: ' + top + 'px'">
                    <div class="flex items-center">
                        <div class="size-2 rounded-full bg-red-500"></div>
                        <div class="h-px flex-1 bg-red-500"></div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
```

- [ ] **Step 5: Set up routes**

Replace `routes/web.php` with:

```php
<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Calendar;
use App\Livewire\Onboarding;
use App\Livewire\PlanSummary;
use App\Livewire\Profile;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', VerifyEmail::class)->name('verification.notice');
    Route::get('/onboarding', Onboarding::class)->name('onboarding');
    Route::get('/', Calendar::class)->name('calendar');
    Route::get('/settings', Settings::class)->name('settings');
    Route::get('/plan-summary', PlanSummary::class)->name('plan-summary');
    Route::get('/profile', Profile::class)->name('profile');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');
});
```

- [ ] **Step 6: Run tests**

```bash
php artisan test --compact --filter=CalendarTest
```

Expected: All PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Calendar.php resources/views/livewire/calendar.blade.php routes/web.php tests/Feature/Livewire/CalendarTest.php
git commit -m "feat: add Calendar page with weekly time grid, event blocks, and AI task rendering"
```

---

### Task 12: Create InboxPanel and TaskDetailModal Livewire components

**Files:**
- Create: `app/Livewire/InboxPanel.php`
- Create: `resources/views/livewire/inbox-panel.blade.php`
- Create: `app/Livewire/TaskDetailModal.php`
- Create: `resources/views/livewire/task-detail-modal.blade.php`
- Create: `tests/Feature/Livewire/InboxPanelTest.php`
- Create: `tests/Feature/Livewire/TaskDetailModalTest.php`

- [ ] **Step 1: Write InboxPanel test**

Create `tests/Feature/Livewire/InboxPanelTest.php`:

```php
<?php

use App\Livewire\InboxPanel;
use App\Models\User;
use App\Models\InboxItem;
use App\Models\Integration;
use App\Enums\IntegrationType;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the inbox panel', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(InboxPanel::class)
        ->assertSee('Inbox')
        ->assertStatus(200);
});

it('shows pending inbox items', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->create(['user_id' => $user->id, 'type' => IntegrationType::Slack]);
    InboxItem::factory()->create([
        'user_id' => $user->id,
        'integration_id' => $integration->id,
        'preview_text' => 'Review the PR please',
        'status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test(InboxPanel::class)
        ->assertSee('Review the PR please');
});

it('can dismiss an inbox item', function () {
    $user = User::factory()->create();
    $item = InboxItem::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

    Livewire::actingAs($user)
        ->test(InboxPanel::class)
        ->call('dismiss', $item->id);

    expect($item->fresh()->status->value)->toBe('dismissed');
});
```

- [ ] **Step 2: Write TaskDetailModal test**

Create `tests/Feature/Livewire/TaskDetailModalTest.php`:

```php
<?php

use App\Livewire\TaskDetailModal;
use App\Models\User;
use App\Models\Task;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the task detail modal', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskDetailModal::class)
        ->assertStatus(200);
});

it('loads a task when opened', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Design the dashboard']);

    Livewire::actingAs($user)
        ->test(TaskDetailModal::class)
        ->dispatch('open-task-modal', taskId: $task->id)
        ->assertSee('Design the dashboard');
});

it('can update task priority', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'priority' => 'medium']);

    Livewire::actingAs($user)
        ->test(TaskDetailModal::class)
        ->dispatch('open-task-modal', taskId: $task->id)
        ->call('setPriority', 'urgent');

    expect($task->fresh()->priority->value)->toBe('urgent');
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --compact --filter=InboxPanelTest --filter=TaskDetailModalTest
```

Expected: FAIL.

- [ ] **Step 4: Create InboxPanel component**

Create `app/Livewire/InboxPanel.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\InboxItemStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\InboxItem;
use Livewire\Attributes\On;
use Livewire\Component;

class InboxPanel extends Component
{
    public ?string $sourceFilter = null;

    public ?string $priorityFilter = null;

    #[On('toggle-inbox')]
    public function toggle(): void
    {
        $this->dispatch('inbox-toggled');
    }

    public function accept(int $itemId): void
    {
        $item = InboxItem::where('user_id', auth()->id())->findOrFail($itemId);

        auth()->user()->tasks()->create([
            'integration_id' => $item->integration_id,
            'title' => str($item->preview_text)->limit(80),
            'description' => $item->preview_text,
            'source_url' => $item->source_url,
            'source_reference' => $item->channel_name,
            'priority' => $item->ai_suggested_priority ?? TaskPriority::Medium->value,
            'status' => TaskStatus::Pending,
        ]);

        $item->update(['status' => InboxItemStatus::Accepted]);
    }

    public function dismiss(int $itemId): void
    {
        InboxItem::where('user_id', auth()->id())
            ->findOrFail($itemId)
            ->update(['status' => InboxItemStatus::Dismissed]);
    }

    public function snooze(int $itemId): void
    {
        InboxItem::where('user_id', auth()->id())
            ->findOrFail($itemId)
            ->update([
                'status' => InboxItemStatus::Snoozed,
                'snoozed_until' => now()->addHours(2),
            ]);
    }

    public function acceptAll(): void
    {
        $items = $this->getItems();

        foreach ($items as $item) {
            $this->accept($item->id);
        }
    }

    public function render()
    {
        return view('livewire.inbox-panel', [
            'items' => $this->getItems(),
        ]);
    }

    private function getItems()
    {
        $query = auth()->user()->inboxItems()
            ->where('status', InboxItemStatus::Pending)
            ->with('integration')
            ->latest();

        if ($this->sourceFilter) {
            $query->whereHas('integration', fn ($q) => $q->where('type', $this->sourceFilter));
        }

        if ($this->priorityFilter) {
            $query->where('ai_suggested_priority', $this->priorityFilter);
        }

        return $query->get();
    }
}
```

- [ ] **Step 5: Create InboxPanel Blade view**

Create `resources/views/livewire/inbox-panel.blade.php`:

```html
<div x-data="{ open: false }"
     @inbox-toggled.window="open = !open"
     @keydown.escape.window="open = false">

    {{-- Backdrop (mobile) --}}
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-30 bg-black/20 lg:hidden" @click="open = false"></div>

    {{-- Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-0 z-40 flex h-full w-[400px] flex-col border-l border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900">

        {{-- Header --}}
        <div class="border-b border-neutral-200 p-4 dark:border-neutral-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Inbox</h2>
                    <span class="rounded-full bg-accent-100 px-2 py-0.5 text-xs font-medium text-accent-700 dark:bg-accent-900 dark:text-accent-300">
                        {{ $items->count() }} new
                    </span>
                </div>
                <button @click="open = false" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Filters --}}
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ([null => 'All', 'jira' => 'Jira', 'slack' => 'Slack', 'gmail' => 'Gmail', 'github' => 'GitHub'] as $value => $label)
                    <button wire:click="$set('sourceFilter', {{ $value === null ? 'null' : "'$value'" }})"
                            class="rounded-full px-2.5 py-1 text-xs font-medium transition-colors {{ $sourceFilter === $value ? 'bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Items --}}
        <div class="flex-1 space-y-2 overflow-auto p-4">
            @forelse ($items as $item)
                <x-inbox-item :item="$item" />
            @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <svg class="size-12 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    <p class="mt-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">All caught up</p>
                    <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">No pending items in your inbox</p>
                </div>
            @endforelse
        </div>

        {{-- Batch actions --}}
        @if ($items->isNotEmpty())
            <div class="border-t border-neutral-200 p-4 dark:border-neutral-800">
                <div class="flex gap-2">
                    <button wire:click="acceptAll"
                            class="flex-1 rounded-lg bg-accent-600 px-4 py-2 text-xs font-medium text-white hover:bg-accent-700">
                        Accept all suggested
                    </button>
                    <button class="flex-1 inline-flex items-center justify-center gap-1 rounded-lg border border-accent-300 px-4 py-2 text-xs font-medium text-accent-600 hover:bg-accent-50 dark:border-accent-700 dark:text-accent-400 dark:hover:bg-accent-950/30">
                        <x-icons.sparkle class="size-3" />
                        Let AI decide
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
```

- [ ] **Step 6: Create TaskDetailModal component**

Create `app/Livewire/TaskDetailModal.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\TaskPriority;
use App\Models\Task;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskDetailModal extends Component
{
    public ?Task $task = null;

    public bool $showAiReasoning = true;

    #[On('open-task-modal')]
    public function open(int $taskId): void
    {
        $this->task = Task::where('user_id', auth()->id())
            ->with('integration')
            ->findOrFail($taskId);
    }

    public function close(): void
    {
        $this->task = null;
    }

    public function setPriority(string $priority): void
    {
        $this->task->update(['priority' => $priority]);
        $this->task->refresh();
    }

    public function render()
    {
        return view('livewire.task-detail-modal');
    }
}
```

- [ ] **Step 7: Create TaskDetailModal Blade view (matching reference image)**

Create `resources/views/livewire/task-detail-modal.blade.php`:

```html
<div>
    @if ($task)
        {{-- Backdrop --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4"
             x-data
             @keydown.escape.window="$wire.close()"
             x-transition.opacity>

            {{-- Modal card --}}
            <div class="w-full max-w-[640px] rounded-xl bg-white shadow-2xl ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800"
                 @click.away="$wire.close()"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                {{-- Header --}}
                <div class="flex items-center justify-between px-8 pt-6">
                    <div class="flex items-center gap-2">
                        <div class="h-6 w-1 rounded-full bg-accent-600"></div>
                        <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Edit Task</h2>
                    </div>
                    <button wire:click="close" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-8 py-6 space-y-6">
                    {{-- Task title --}}
                    <div>
                        <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Task Title</label>
                        <p class="mt-1 text-xl font-semibold text-neutral-900 dark:text-neutral-100">{{ $task->title }}</p>
                    </div>

                    {{-- Description --}}
                    @if ($task->description)
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Description</label>
                            <div class="mt-2 border-l-2 border-accent-200 pl-4 dark:border-accent-800">
                                <p class="text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed">{{ $task->description }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Priority + Duration row --}}
                    <div class="flex items-start gap-8">
                        <div>
                            <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Priority</label>
                            <div class="mt-2 flex items-center gap-2">
                                @foreach (\App\Enums\TaskPriority::cases() as $p)
                                    @if ($p !== \App\Enums\TaskPriority::Low)
                                        <button wire:click="setPriority('{{ $p->value }}')"
                                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors
                                                    {{ $task->priority === $p
                                                        ? 'border-neutral-300 bg-neutral-100 text-neutral-900 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100'
                                                        : 'border-transparent text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800' }}">
                                            <span class="size-2 rounded-full {{ $p->bgColor() }}"></span>
                                            {{ $p->label() }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Estimated Duration</label>
                                @if ($task->is_ai_scheduled)
                                    <x-ai-badge />
                                @endif
                            </div>
                            <div class="mt-2">
                                <span class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">{{ $task->formattedDuration() }}</span>
                                <span class="ml-2 text-xs text-neutral-400 dark:text-neutral-500">Total time</span>
                            </div>
                        </div>
                    </div>

                    {{-- Source + Deadline row --}}
                    <div class="flex items-start gap-8">
                        @if ($task->source_url || $task->source_reference)
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Source</label>
                                <div class="mt-2">
                                    @if ($task->source_url)
                                        <a href="{{ $task->source_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400 dark:hover:text-accent-300">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                                            View in {{ $task->integration?->type->label() ?? 'Source' }}: {{ $task->source_reference }}
                                        </a>
                                    @else
                                        <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ $task->source_reference }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($task->deadline)
                            <div>
                                <label class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">Deadline Date</label>
                                <div class="mt-2 flex items-center gap-1.5 text-sm text-neutral-700 dark:text-neutral-300">
                                    <svg class="size-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v9.75"/></svg>
                                    {{ $task->deadline->format('M j, Y') }}
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- AI Reasoning section --}}
                    @if ($task->ai_reasoning)
                        <div class="rounded-lg bg-accent-50 p-4 dark:bg-accent-950/20">
                            <button wire:click="$toggle('showAiReasoning')" class="flex w-full items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="flex size-8 items-center justify-center rounded-full bg-accent-100 dark:bg-accent-900">
                                        <x-icons.sparkle class="size-4 text-accent-600 dark:text-accent-400" />
                                    </span>
                                    <span class="text-xs font-semibold uppercase tracking-widest text-accent-700 dark:text-accent-300">AI Scheduling Logic</span>
                                </div>
                                <svg class="size-4 text-accent-400 transition-transform {{ $showAiReasoning ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            @if ($showAiReasoning)
                                <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed">{{ $task->ai_reasoning }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-neutral-200 px-8 py-4 dark:border-neutral-800">
                    <button wire:click="close" class="px-4 py-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-200">
                        Cancel
                    </button>
                    <button class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
                        Reschedule
                        <x-icons.sparkle class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
```

- [ ] **Step 8: Run tests**

```bash
php artisan test --compact --filter=InboxPanelTest --filter=TaskDetailModalTest
```

Expected: All PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Livewire/InboxPanel.php app/Livewire/TaskDetailModal.php resources/views/livewire/inbox-panel.blade.php resources/views/livewire/task-detail-modal.blade.php tests/Feature/Livewire/InboxPanelTest.php tests/Feature/Livewire/TaskDetailModalTest.php
git commit -m "feat: add InboxPanel slide-over and TaskDetailModal with AI reasoning section"
```

---

### Task 13: Create Settings Livewire page

**Files:**
- Create: `app/Livewire/Settings.php`
- Create: `resources/views/livewire/settings.blade.php`
- Create: `tests/Feature/Livewire/SettingsTest.php`

- [ ] **Step 1: Write Settings test**

Create `tests/Feature/Livewire/SettingsTest.php`:

```php
<?php

use App\Livewire\Settings;
use App\Models\User;
use App\Models\Integration;
use App\Enums\IntegrationType;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the settings page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->assertSee('Settings')
        ->assertSee('Integrations')
        ->assertStatus(200);
});

it('shows all integration types', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->assertSee('Jira')
        ->assertSee('Slack')
        ->assertSee('Gmail');
});

it('can save AI preferences', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('workingHoursStart', '08:00')
        ->set('workingHoursEnd', '18:00')
        ->set('bufferTime', 10)
        ->call('savePreferences');

    expect($user->fresh())
        ->working_hours_start->toBe('08:00')
        ->working_hours_end->toBe('18:00')
        ->buffer_time->toBe(10);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=SettingsTest
```

Expected: FAIL.

- [ ] **Step 3: Create Settings component**

Create `app/Livewire/Settings.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\IntegrationType;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settings — Aura')]
class Settings extends Component
{
    public string $activeTab = 'integrations';

    public string $workingHoursStart;

    public string $workingHoursEnd;

    public bool $focusTimeEnabled;

    public ?string $focusTimeStart;

    public ?string $focusTimeEnd;

    public int $maxTaskDuration;

    public int $bufferTime;

    public function mount(): void
    {
        $user = auth()->user();
        $this->workingHoursStart = $user->working_hours_start ?? '09:00';
        $this->workingHoursEnd = $user->working_hours_end ?? '17:00';
        $this->focusTimeEnabled = $user->focus_time_enabled;
        $this->focusTimeStart = $user->focus_time_start;
        $this->focusTimeEnd = $user->focus_time_end;
        $this->maxTaskDuration = $user->max_task_duration;
        $this->bufferTime = $user->buffer_time;
    }

    public function savePreferences(): void
    {
        auth()->user()->update([
            'working_hours_start' => $this->workingHoursStart,
            'working_hours_end' => $this->workingHoursEnd,
            'focus_time_enabled' => $this->focusTimeEnabled,
            'focus_time_start' => $this->focusTimeStart,
            'focus_time_end' => $this->focusTimeEnd,
            'max_task_duration' => $this->maxTaskDuration,
            'buffer_time' => $this->bufferTime,
        ]);

        session()->flash('message', 'Preferences saved.');
    }

    public function render()
    {
        $user = auth()->user();
        $connectedIntegrations = $user->integrations()->get()->keyBy(fn ($i) => $i->type->value);

        return view('livewire.settings', [
            'integrationTypes' => IntegrationType::cases(),
            'connectedIntegrations' => $connectedIntegrations,
        ]);
    }
}
```

- [ ] **Step 4: Create Settings Blade view**

Create `resources/views/livewire/settings.blade.php`:

```html
<div class="mx-auto max-w-4xl px-6 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Settings</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Manage your integrations and AI preferences</p>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 flex gap-1 rounded-lg bg-neutral-100 p-0.5 dark:bg-neutral-800 w-fit">
        <button wire:click="$set('activeTab', 'integrations')"
                class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors {{ $activeTab === 'integrations' ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400' }}">
            Integrations
        </button>
        <button wire:click="$set('activeTab', 'preferences')"
                class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors {{ $activeTab === 'preferences' ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-neutral-100' : 'text-neutral-500 hover:text-neutral-700 dark:text-neutral-400' }}">
            AI Preferences
        </button>
    </div>

    {{-- Integrations tab --}}
    @if ($activeTab === 'integrations')
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            @foreach ($integrationTypes as $type)
                @php $integration = $connectedIntegrations->get($type->value); @endphp
                <x-integration-card
                    :type="$type"
                    :status="$integration?->status"
                    :integration="$integration" />
            @endforeach
        </div>
    @endif

    {{-- AI Preferences tab --}}
    @if ($activeTab === 'preferences')
        <div class="space-y-8">
            @if (session('message'))
                <div class="rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/20 dark:text-green-400">
                    {{ session('message') }}
                </div>
            @endif

            {{-- Working Hours --}}
            <div class="rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Working Hours</h3>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">AI will only schedule tasks during these hours</p>
                <div class="mt-4 flex items-center gap-4">
                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Start</label>
                        <input type="time" wire:model="workingHoursStart" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                    <span class="mt-5 text-neutral-400">–</span>
                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">End</label>
                        <input type="time" wire:model="workingHoursEnd" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                    </div>
                </div>
            </div>

            {{-- Focus Time --}}
            <div class="rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Focus Time</h3>
                        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Block time for deep work — AI won't schedule small tasks here</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" wire:model.live="focusTimeEnabled" class="peer sr-only">
                        <div class="h-5 w-9 rounded-full bg-neutral-200 after:absolute after:left-[2px] after:top-[2px] after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-accent-600 peer-checked:after:translate-x-full dark:bg-neutral-700"></div>
                    </label>
                </div>
                @if ($focusTimeEnabled)
                    <div class="mt-4 flex items-center gap-4">
                        <div>
                            <label class="text-xs text-neutral-500 dark:text-neutral-400">Start</label>
                            <input type="time" wire:model="focusTimeStart" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                        </div>
                        <span class="mt-5 text-neutral-400">–</span>
                        <div>
                            <label class="text-xs text-neutral-500 dark:text-neutral-400">End</label>
                            <input type="time" wire:model="focusTimeEnd" class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                        </div>
                    </div>
                @endif
            </div>

            {{-- Task Scheduling --}}
            <div class="rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Task Scheduling</h3>
                <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Control how the AI schedules your tasks</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Max task duration (minutes)</label>
                        <input type="range" wire:model.live="maxTaskDuration" min="30" max="240" step="30"
                               class="mt-2 w-full accent-accent-600">
                        <span class="text-xs text-neutral-600 dark:text-neutral-400">{{ $maxTaskDuration }} min</span>
                    </div>

                    <div>
                        <label class="text-xs text-neutral-500 dark:text-neutral-400">Buffer between tasks</label>
                        <div class="mt-2 flex gap-2">
                            @foreach ([5, 10, 15, 30] as $minutes)
                                <button wire:click="$set('bufferTime', {{ $minutes }})"
                                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $bufferTime === $minutes ? 'bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300' : 'bg-neutral-100 text-neutral-500 hover:text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400' }}">
                                    {{ $minutes }}m
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <button wire:click="savePreferences"
                    class="rounded-lg bg-accent-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
                Save preferences
            </button>
        </div>
    @endif
</div>
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=SettingsTest
```

Expected: All PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Settings.php resources/views/livewire/settings.blade.php tests/Feature/Livewire/SettingsTest.php
git commit -m "feat: add Settings page with integration grid and AI preferences"
```

---

### Task 14: Create PlanSummary Livewire page

**Files:**
- Create: `app/Livewire/PlanSummary.php`
- Create: `resources/views/livewire/plan-summary.blade.php`
- Create: `tests/Feature/Livewire/PlanSummaryTest.php`

- [ ] **Step 1: Write PlanSummary test**

Create `tests/Feature/Livewire/PlanSummaryTest.php`:

```php
<?php

use App\Livewire\PlanSummary;
use App\Models\User;
use App\Models\Task;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the plan summary page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PlanSummary::class)
        ->assertSee('AI Schedule Proposal')
        ->assertStatus(200);
});

it('shows scheduled tasks for review', function () {
    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Review docs',
        'status' => 'scheduled',
        'is_ai_scheduled' => true,
        'scheduled_start' => now()->addHour(),
        'scheduled_end' => now()->addHours(2),
    ]);

    Livewire::actingAs($user)
        ->test(PlanSummary::class)
        ->assertSee('Review docs');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PlanSummaryTest
```

Expected: FAIL.

- [ ] **Step 3: Create PlanSummary component**

Create `app/Livewire/PlanSummary.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('AI Schedule Proposal — Aura')]
class PlanSummary extends Component
{
    /** @var array<int, bool> */
    public array $approved = [];

    public function approve(int $taskId): void
    {
        $this->approved[$taskId] = true;
    }

    public function remove(int $taskId): void
    {
        $task = auth()->user()->tasks()->findOrFail($taskId);
        $task->update([
            'status' => TaskStatus::Pending,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_ai_scheduled' => false,
        ]);

        unset($this->approved[$taskId]);
    }

    public function approveAll(): void
    {
        $tasks = $this->getTasks();

        foreach ($tasks as $task) {
            $this->approved[$task->id] = true;
        }
    }

    public function render()
    {
        $tasks = $this->getTasks();
        $totalDuration = $tasks->sum('estimated_duration');
        $hours = intdiv($totalDuration, 60);
        $minutes = $totalDuration % 60;

        return view('livewire.plan-summary', [
            'tasks' => $tasks,
            'totalTasks' => $tasks->count(),
            'totalDuration' => ($hours > 0 ? "{$hours}h " : '') . ($minutes > 0 ? "{$minutes}m" : ''),
        ]);
    }

    private function getTasks()
    {
        return auth()->user()->tasks()
            ->where('status', TaskStatus::Scheduled)
            ->where('is_ai_scheduled', true)
            ->with('integration')
            ->orderBy('scheduled_start')
            ->get();
    }
}
```

- [ ] **Step 4: Create PlanSummary Blade view**

Create `resources/views/livewire/plan-summary.blade.php`:

```html
<div class="mx-auto max-w-5xl px-6 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-2">
            <x-icons.sparkle class="size-6 text-accent-600" />
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">AI Schedule Proposal</h1>
        </div>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">Here's how I'd organize your upcoming tasks. Review and approve.</p>

        {{-- Stats --}}
        <div class="mt-4 flex items-center gap-3">
            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                {{ $totalTasks }} tasks scheduled
            </span>
            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                {{ $totalDuration }} total
            </span>
        </div>
    </div>

    {{-- Task list --}}
    <div class="space-y-3">
        @forelse ($tasks as $task)
            <x-plan-diff-row :task="$task" :approved="isset($approved[$task->id])" />
        @empty
            <div class="rounded-xl bg-white p-12 text-center ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
                <x-icons.sparkle class="mx-auto size-10 text-neutral-300 dark:text-neutral-600" />
                <p class="mt-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">No tasks to schedule</p>
                <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Accept items from your inbox first</p>
            </div>
        @endforelse
    </div>

    {{-- Bottom actions --}}
    @if ($tasks->isNotEmpty())
        <div class="sticky bottom-0 mt-6 flex items-center justify-between rounded-xl bg-white/80 p-4 shadow-lg ring-1 ring-neutral-200 backdrop-blur-sm dark:bg-neutral-900/80 dark:ring-neutral-800">
            <button class="rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-800">
                Redo
            </button>
            <button wire:click="approveAll"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-accent-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
                <x-icons.sparkle class="size-3.5" />
                Approve All
            </button>
        </div>
    @endif
</div>
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=PlanSummaryTest
```

Expected: All PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/PlanSummary.php resources/views/livewire/plan-summary.blade.php tests/Feature/Livewire/PlanSummaryTest.php
git commit -m "feat: add PlanSummary page with task review, approve, and remove actions"
```

---

## Phase 6: Auth & Supporting Pages

### Task 15: Create auth pages (Login, Register, ForgotPassword, ResetPassword, VerifyEmail)

**Files:**
- Create: `app/Livewire/Auth/Login.php`
- Create: `resources/views/livewire/auth/login.blade.php`
- Create: `app/Livewire/Auth/Register.php`
- Create: `resources/views/livewire/auth/register.blade.php`
- Create: `app/Livewire/Auth/ForgotPassword.php`
- Create: `resources/views/livewire/auth/forgot-password.blade.php`
- Create: `app/Livewire/Auth/ResetPassword.php`
- Create: `resources/views/livewire/auth/reset-password.blade.php`
- Create: `app/Livewire/Auth/VerifyEmail.php`
- Create: `resources/views/livewire/auth/verify-email.blade.php`
- Create: `tests/Feature/Auth/LoginTest.php`
- Create: `tests/Feature/Auth/RegisterTest.php`

- [ ] **Step 1: Write auth tests**

Create `tests/Feature/Auth/LoginTest.php`:

```php
<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the login page', function () {
    $this->get('/login')->assertStatus(200);
});

it('can log in with valid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/');
});

it('shows error with invalid credentials', function () {
    Livewire::test(Login::class)
        ->set('email', 'wrong@example.com')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');
});

it('redirects authenticated users away from login', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/login')->assertRedirect('/');
});
```

Create `tests/Feature/Auth/RegisterTest.php`:

```php
<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the register page', function () {
    $this->get('/register')->assertStatus(200);
});

it('can register a new user', function () {
    Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect('/onboarding');

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

it('requires matching password confirmation', function () {
    Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors('password');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=LoginTest --filter=RegisterTest
```

Expected: FAIL.

- [ ] **Step 3: Create Login component**

Create `app/Livewire/Auth/Login.php`:

```php
<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Sign in — Aura')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', __('auth.failed'));

            return;
        }

        session()->regenerate();

        $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
```

Create `resources/views/livewire/auth/login.blade.php`:

```html
<div>
    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Sign in to your account</h2>
    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Welcome back to Aura</p>

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2">
                <input wire:model="remember" type="checkbox" class="rounded border-neutral-300 text-accent-600 focus:ring-accent-500 dark:border-neutral-600">
                <span class="text-xs text-neutral-600 dark:text-neutral-400">Remember me</span>
            </label>
            <a href="/forgot-password" wire:navigate class="text-xs font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Forgot password?</a>
        </div>

        <button type="submit" class="w-full rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
            Sign in
        </button>
    </form>

    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-200 dark:border-neutral-700"></div></div>
            <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-neutral-400 dark:bg-neutral-900">or continue with</span></div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                <svg class="size-4" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                Google
            </button>
            <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                <x-icons.github class="size-4" />
                GitHub
            </button>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-neutral-500 dark:text-neutral-400">
        Don't have an account? <a href="/register" wire:navigate class="font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Sign up</a>
    </p>
</div>
```

- [ ] **Step 4: Create Register component**

Create `app/Livewire/Auth/Register.php`:

```php
<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Create account — Aura')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect('/onboarding', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
```

Create `resources/views/livewire/auth/register.blade.php`:

```html
<div>
    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Create your account</h2>
    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Get started with Aura</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Full name</label>
            <input wire:model="name" id="name" type="text" autocomplete="name" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Email</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Confirm password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
        </div>

        <button type="submit" class="w-full rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
            Create account
        </button>
    </form>

    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-200 dark:border-neutral-700"></div></div>
            <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-neutral-400 dark:bg-neutral-900">or continue with</span></div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                <svg class="size-4" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                Google
            </button>
            <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800">
                <x-icons.github class="size-4" />
                GitHub
            </button>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-neutral-500 dark:text-neutral-400">
        Already have an account? <a href="/login" wire:navigate class="font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Sign in</a>
    </p>
</div>
```

- [ ] **Step 5: Create ForgotPassword, ResetPassword, VerifyEmail components**

Create `app/Livewire/Auth/ForgotPassword.php`:

```php
<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Forgot password — Aura')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => $this->email]);

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
```

Create `resources/views/livewire/auth/forgot-password.blade.php`:

```html
<div>
    @if ($sent)
        <div class="text-center">
            <svg class="mx-auto size-12 text-accent-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
            <h2 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">Check your email</h2>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">We've sent a password reset link to {{ $email }}</p>
        </div>
    @else
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Forgot your password?</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Enter your email and we'll send you a reset link</p>

        <form wire:submit="sendResetLink" class="mt-6 space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Email</label>
                <input wire:model="email" id="email" type="email" required
                       class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
                Send reset link
            </button>
        </form>
    @endif

    <p class="mt-6 text-center text-xs text-neutral-500 dark:text-neutral-400">
        <a href="/login" wire:navigate class="font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">Back to sign in</a>
    </p>
</div>
```

Create `app/Livewire/Auth/ResetPassword.php`:

```php
<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Reset password — Aura')]
class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->redirect('/login', navigate: true);
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
```

Create `resources/views/livewire/auth/reset-password.blade.php`:

```html
<div>
    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Reset your password</h2>
    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Choose a new password for your account</p>

    <form wire:submit="resetPassword" class="mt-6 space-y-4">
        <div>
            <label for="password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">New password</label>
            <input wire:model="password" id="password" type="password" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Confirm password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" required
                   class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-accent-500 focus:ring-accent-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
        </div>

        @error('email') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

        <button type="submit" class="w-full rounded-lg bg-accent-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-accent-700 transition-colors">
            Reset password
        </button>
    </form>
</div>
```

Create `app/Livewire/Auth/VerifyEmail.php`:

```php
<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Verify email — Aura')]
class VerifyEmail extends Component
{
    public function resend(): void
    {
        auth()->user()->sendEmailVerificationNotification();

        session()->flash('message', 'Verification link sent!');
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
```

Create `resources/views/livewire/auth/verify-email.blade.php`:

```html
<div class="text-center">
    <svg class="mx-auto size-12 text-accent-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>

    <h2 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">Check your email</h2>
    <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">We've sent a verification link to your email address. Please check your inbox.</p>

    @if (session('message'))
        <p class="mt-4 text-sm text-green-600 dark:text-green-400">{{ session('message') }}</p>
    @endif

    <button wire:click="resend" class="mt-6 text-sm font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">
        Resend verification email
    </button>
</div>
```

- [ ] **Step 6: Run tests**

```bash
php artisan test --compact --filter=LoginTest --filter=RegisterTest
```

Expected: All PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Auth/ resources/views/livewire/auth/ tests/Feature/Auth/
git commit -m "feat: add auth pages — Login, Register, ForgotPassword, ResetPassword, VerifyEmail"
```

---

### Task 16: Create Onboarding, Profile, and 404 pages

**Files:**
- Create: `app/Livewire/Onboarding.php`
- Create: `resources/views/livewire/onboarding.blade.php`
- Create: `app/Livewire/Profile.php`
- Create: `resources/views/livewire/profile.blade.php`
- Create: `resources/views/errors/404.blade.php`
- Create: `tests/Feature/Livewire/OnboardingTest.php`
- Create: `tests/Feature/Livewire/ProfileTest.php`

- [ ] **Step 1: Write Onboarding and Profile tests**

Create `tests/Feature/Livewire/OnboardingTest.php`:

```php
<?php

use App\Livewire\Onboarding;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the onboarding page', function () {
    $user = User::factory()->create(['onboarded_at' => null]);

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->assertSee('Welcome to Aura')
        ->assertStatus(200);
});

it('can complete onboarding', function () {
    $user = User::factory()->create(['onboarded_at' => null]);

    Livewire::actingAs($user)
        ->test(Onboarding::class)
        ->set('step', 3)
        ->call('complete')
        ->assertRedirect('/');

    expect($user->fresh()->onboarded_at)->not->toBeNull();
});
```

Create `tests/Feature/Livewire/ProfileTest.php`:

```php
<?php

use App\Livewire\Profile;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the profile page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->assertSee($user->name)
        ->assertStatus(200);
});

it('can update profile name and email', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('name', 'New Name')
        ->set('email', 'new@example.com')
        ->call('updateProfile');

    expect($user->fresh())
        ->name->toBe('New Name')
        ->email->toBe('new@example.com');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=OnboardingTest --filter=ProfileTest
```

Expected: FAIL.

- [ ] **Step 3: Create Onboarding component**

Create `app/Livewire/Onboarding.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\IntegrationType;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Get started — Aura')]
class Onboarding extends Component
{
    public int $step = 1;

    public string $workingHoursStart = '09:00';

    public string $workingHoursEnd = '17:00';

    /** @var array<int, int> */
    public array $workingDays = [1, 2, 3, 4, 5];

    public function nextStep(): void
    {
        if ($this->step === 2) {
            auth()->user()->update([
                'working_hours_start' => $this->workingHoursStart,
                'working_hours_end' => $this->workingHoursEnd,
                'working_days' => $this->workingDays,
            ]);
        }

        $this->step = min(3, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function complete(): void
    {
        auth()->user()->update(['onboarded_at' => now()]);

        $this->redirect('/', navigate: true);
    }

    public function skip(): void
    {
        $this->complete();
    }

    public function render()
    {
        return view('livewire.onboarding', [
            'integrationTypes' => IntegrationType::cases(),
        ]);
    }
}
```

Create `resources/views/livewire/onboarding.blade.php`:

```html
<div>
    {{-- Progress dots --}}
    <div class="mb-6 flex justify-center gap-2">
        @for ($i = 1; $i <= 3; $i++)
            <div class="size-2 rounded-full {{ $step >= $i ? 'bg-accent-600' : 'bg-neutral-200 dark:bg-neutral-700' }}"></div>
        @endfor
    </div>

    {{-- Step 1: Connect integrations --}}
    @if ($step === 1)
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Welcome to Aura</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Connect your tools to get started</p>

        <div class="mt-6 grid grid-cols-2 gap-3">
            @foreach ($integrationTypes as $type)
                <button class="flex items-center gap-3 rounded-lg border border-neutral-200 p-3 text-left hover:border-accent-300 hover:bg-accent-50 dark:border-neutral-700 dark:hover:border-accent-700 dark:hover:bg-accent-950/20 transition-colors">
                    <x-dynamic-component :component="$type->iconComponent()" class="size-6" />
                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $type->label() }}</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Step 2: Working hours --}}
    @if ($step === 2)
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Set your working hours</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">AI will schedule tasks within these times</p>

        <div class="mt-6 flex items-center gap-4">
            <div>
                <label class="text-xs text-neutral-500">Start</label>
                <input type="time" wire:model="workingHoursStart"
                       class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            </div>
            <span class="mt-5 text-neutral-400">–</span>
            <div>
                <label class="text-xs text-neutral-500">End</label>
                <input type="time" wire:model="workingHoursEnd"
                       class="mt-1 block rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            </div>
        </div>

        <div class="mt-4">
            <label class="text-xs text-neutral-500">Working days</label>
            <div class="mt-2 flex gap-2">
                @foreach (['Mo' => 1, 'Tu' => 2, 'We' => 3, 'Th' => 4, 'Fr' => 5, 'Sa' => 6, 'Su' => 7] as $label => $day)
                    <button wire:click="$toggle('workingDays', {{ $day }})"
                            class="flex size-9 items-center justify-center rounded-full text-xs font-medium transition-colors {{ in_array($day, $workingDays) ? 'bg-accent-600 text-white' : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Step 3: Ready --}}
    @if ($step === 3)
        <div class="text-center">
            <x-icons.sparkle class="mx-auto size-12 text-accent-600" />
            <h2 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">You're all set!</h2>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">Aura will start organizing your tasks intelligently</p>
        </div>
    @endif

    {{-- Navigation --}}
    <div class="mt-8 flex items-center justify-between">
        @if ($step > 1)
            <button wire:click="previousStep" class="text-sm text-neutral-500 hover:text-neutral-700 dark:text-neutral-400">Back</button>
        @else
            <button wire:click="skip" class="text-sm text-neutral-500 hover:text-neutral-700 dark:text-neutral-400">Skip</button>
        @endif

        @if ($step < 3)
            <button wire:click="nextStep" class="rounded-lg bg-accent-600 px-6 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">
                Next
            </button>
        @else
            <button wire:click="complete" class="rounded-lg bg-accent-600 px-6 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">
                Get started
            </button>
        @endif
    </div>
</div>
```

- [ ] **Step 4: Create Profile component**

Create `app/Livewire/Profile.php`:

```php
<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Profile — Aura')]
class Profile extends Component
{
    public string $name;

    public string $email;

    public string $timezone;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $user->timezone ?? 'UTC';
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(auth()->id())],
            'timezone' => ['required', 'string'],
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
            'timezone' => $this->timezone,
        ]);

        session()->flash('profile-message', 'Profile updated.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'The current password is incorrect.');

            return;
        }

        auth()->user()->update(['password' => Hash::make($this->new_password)]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('password-message', 'Password updated.');
    }

    public function render()
    {
        return view('livewire.profile');
    }
}
```

Create `resources/views/livewire/profile.blade.php`:

```html
<div class="mx-auto max-w-2xl px-6 py-8">
    <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Profile</h1>

    {{-- Avatar + profile info --}}
    <div class="mt-8 rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
        <div class="flex items-center gap-4 mb-6">
            <div class="flex size-16 items-center justify-center rounded-full bg-accent-100 text-2xl font-bold text-accent-700 dark:bg-accent-900 dark:text-accent-300">
                {{ substr($name, 0, 1) }}
            </div>
            <div>
                <p class="font-semibold text-neutral-900 dark:text-neutral-100">{{ $name }}</p>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $email }}</p>
            </div>
        </div>

        @if (session('profile-message'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/20 dark:text-green-400">{{ session('profile-message') }}</div>
        @endif

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Name</label>
                <input wire:model="name" type="text" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Email</label>
                <input wire:model="email" type="email" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Timezone</label>
                <select wire:model="timezone" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                    @foreach (timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">Save</button>
        </form>
    </div>

    {{-- Password --}}
    <div class="mt-6 rounded-xl bg-white p-6 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-800">
        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">Change password</h3>

        @if (session('password-message'))
            <div class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/20 dark:text-green-400">{{ session('password-message') }}</div>
        @endif

        <form wire:submit="updatePassword" class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Current password</label>
                <input wire:model="current_password" type="password" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('current_password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">New password</label>
                <input wire:model="new_password" type="password" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                @error('new_password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Confirm new password</label>
                <input wire:model="new_password_confirmation" type="password" class="mt-1 block w-full rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
            </div>

            <button type="submit" class="rounded-lg bg-accent-600 px-5 py-2 text-sm font-medium text-white hover:bg-accent-700 transition-colors">Update password</button>
        </form>
    </div>

    {{-- Danger zone --}}
    <div class="mt-6 rounded-xl border border-red-200 bg-white p-6 dark:border-red-900 dark:bg-neutral-900">
        <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">Danger Zone</h3>
        <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Once you delete your account, there is no going back.</p>
        <button class="mt-4 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950/20">
            Delete account
        </button>
    </div>
</div>
```

- [ ] **Step 5: Create 404 page**

Create `resources/views/errors/404.blade.php`:

```html
<!DOCTYPE html>
<html lang="en"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Aura</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-neutral-50 dark:bg-neutral-950">
    <div class="text-center">
        <p class="text-7xl font-bold text-neutral-200 dark:text-neutral-800">404</p>
        <h1 class="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">Page not found</h1>
        <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">The page you're looking for doesn't exist.</p>
        <a href="/" class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-accent-600 hover:text-accent-700 dark:text-accent-400">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to calendar
        </a>
    </div>
</body>
</html>
```

- [ ] **Step 6: Run tests**

```bash
php artisan test --compact --filter=OnboardingTest --filter=ProfileTest
```

Expected: All PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Onboarding.php app/Livewire/Profile.php resources/views/livewire/onboarding.blade.php resources/views/livewire/profile.blade.php resources/views/errors/404.blade.php tests/Feature/Livewire/OnboardingTest.php tests/Feature/Livewire/ProfileTest.php
git commit -m "feat: add Onboarding wizard, Profile page, and 404 error page"
```

---

## Phase 7: Final Polish

### Task 17: Enable RefreshDatabase globally, run full test suite, and run Pint

**Files:**
- Modify: `tests/Pest.php`

- [ ] **Step 1: Enable RefreshDatabase in Pest.php**

In `tests/Pest.php`, uncomment the RefreshDatabase line:

```php
pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');
```

Then remove the individual `uses(RefreshDatabase::class);` lines from all test files (they'll inherit from Pest.php).

- [ ] **Step 2: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests PASS.

- [ ] **Step 3: Run Pint on all modified PHP files**

```bash
vendor/bin/pint --format agent
```

Expected: Formatting applied, no errors.

- [ ] **Step 4: Build frontend**

```bash
npm run build
```

Expected: Build succeeds.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "chore: enable global RefreshDatabase, run Pint, build frontend assets"
```

---

### Task 18: Seed demo data and verify in browser

- [ ] **Step 1: Reset and seed database**

```bash
php artisan migrate:fresh --seed --seeder=DemoSeeder --no-interaction
```

Expected: Database reset, demo data seeded.

- [ ] **Step 2: Start dev server**

```bash
composer run dev
```

- [ ] **Step 3: Verify in browser**

Open the application URL (use `get-absolute-url` MCP tool to get the URL). Log in with `kylian@aura.ai` / `password`. Verify:

1. Login page renders with correct styling
2. Calendar shows weekly grid with events and AI-scheduled tasks
3. Sidebar shows mini calendar, integrations, and unscheduled queue
4. TopBar shows correct week label, view switcher, and inbox badge
5. Clicking notification bell opens InboxPanel slide-over
6. Clicking an AI task block opens TaskDetailModal
7. Settings page shows integration grid and AI preferences
8. Plan Summary shows task review list
9. Profile page shows editable user info
10. Dark mode toggles correctly
11. 404 page renders for unknown routes

- [ ] **Step 4: Final commit if any fixes needed**

```bash
git add -A
git commit -m "fix: address visual issues found during browser verification"
```
