# A6-ERP User Guide

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Navigation](#2-navigation)
3. [Dashboard](#3-dashboard)
4. [Announcements](#4-announcements)
5. [Projects](#5-projects)
6. [Tasks](#6-tasks)
7. [Timesheet](#7-timesheet)
8. [Teams](#8-teams)
9. [Users](#9-users)
10. [Leave Requests](#10-leave-requests)
11. [Overtime (OT) Requests](#11-overtime-ot-requests)
12. [Roles & Permissions](#12-roles--permissions)
13. [Notifications](#13-notifications)
14. [Dark Mode](#14-dark-mode)
15. [Permission Reference](#15-permission-reference)

---

## 1. Getting Started

### Logging In

Navigate to the application URL and sign in with your email and password. All features require authentication — you will be redirected to the login page if you are not signed in.

### Access Control

Not all features are available to every user. What you can see and do depends on the **permissions assigned to your role**. If you try to access a page you do not have permission for, you will see a `403 Forbidden` error. Contact your administrator to request access.

---

## 2. Navigation

The interface has two navigation areas:

### Top Bar
| Element | Description |
|---|---|
| **Logo / App Name** | Click to go to the Dashboard |
| **Dark Mode Toggle** | Sun/Moon icon — switches between light and dark theme (persisted across sessions) |
| **Bell Icon** | Notifications — shows unread count badge; click to open dropdown preview |
| **Profile Menu** | Your name with a dropdown for Profile settings and Log Out |

### Left Sidebar
The sidebar contains all module links grouped by category:

| Group | Links |
|---|---|
| — | Dashboard |
| **Work** | Announcements, Projects, Tasks, Timesheet |
| **People** | Teams, Users |
| **Requests** | Leave Requests, OT Requests |
| **Admin** | Roles *(visible only with `module roles` permission)* |

On **mobile**, the sidebar is hidden by default. Tap the **hamburger menu (☰)** in the top bar to slide it open.

---

## 3. Dashboard

The Dashboard gives you a quick overview of what matters most.

### Stats Bar
Four summary cards at the top:

| Card | Description |
|---|---|
| **Leave Balance** | Your remaining leave hours |
| **This Week** | Total hours you have logged this week |
| **This Month** | Total hours you have logged this month |
| **OT This Month** | Your approved overtime hours this month |

### Announcements Panel *(left)*
- Shows the **latest announcement** with its full content rendered.
- Below it, a list of the **5 previous announcements** with links to read each one.

### Notifications Panel *(right)*
Three sections stacked vertically:

**Pending Approvals** *(visible to approvers)*
Lists pending Leave and OT requests waiting for your review, with direct links to approve or reject.

**Upcoming Approved Leaves**
Shows approved leaves happening in the **next 14 days** — your own if you are a regular user, or your team's/all users' if you have broader access.

**Tasks Nearing Deadline**
Tasks with status **In Progress** whose expected end date is **within 5 days**. Click a task name to go to its detail page.

---

## 4. Announcements

**Route:** `/announcements`

Announcements are company-wide posts created by authorised staff. They support rich text formatting including bold, italic, lists, headings, and embedded images.

### Viewing Announcements

- The index page shows all announcements as cards with the title, author name, and date.
- Click an announcement title or card to read the full content.

### Creating an Announcement *(requires `edit announcements`)*

1. Click **New Announcement** on the index page.
2. Enter a **Title**.
3. Write content using the rich text editor (Quill). Use the toolbar for formatting.
4. To insert an image, click the image icon in the toolbar — the image is uploaded automatically.
5. Click **Save**.

### Editing / Deleting

- **Edit** button appears on the index card and the show page if you have `edit announcements`.
- **Delete** button appears if you have `delete announcements`. Deletion is soft — the record is archived.

---

## 5. Projects

**Route:** `/projects`

Projects track large bodies of work. Each project can have assigned teams, members, files, and tasks.

### Project ID Format

Every project has a unique identifier displayed as **`PJ-{number}`** (e.g. `PJ-12`). This appears on list views and inside task/time-log references.

### Index Page

The table shows:
- **ID** (clickable link to detail page)
- **Name**
- **Status** — colour-coded badge: grey = Not Started, blue = In Progress, green = Done
- **Teams** — teams assigned to the project
- **Members** — count of directly assigned users
- **Start Date / Expected End**
- **Actions** — View, Edit, Delete *(permission-gated)*

### Project Detail Page

Split into two panels:

**Left Panel — Details**
- Status, description, start and end dates
- Assigned teams and members (with names)
- **Activity Log** — chronological list of all changes (who changed what and when)

**Right Panel — Work**
- **File Explorer** — upload, download, rename, and organise files into folders
- **Tasks** — all tasks belonging to this project, with status and progress bars
- **Log Time** button — quick link to log time against this project

### Statuses

| Status | Meaning |
|---|---|
| Not Started | Work has not begun |
| In Progress | Actively being worked on |
| Done | Completed |

### File Explorer

- **Upload** — drag a file or click Upload to add files.
- **Create Folder** — organise files into folders.
- **Rename** — click the rename icon next to any file or folder.
- **Download** — click the download icon to save a file locally.
- **Delete** — removes the file from the project. This action cannot be undone.

Navigate into folders by clicking the folder name. Use the breadcrumb trail at the top to navigate back up.

---

## 6. Tasks

**Route:** `/tasks`

Tasks are units of work that can be standalone or linked to a project.

### Task ID Format

Every task has a unique identifier displayed as **`TK-{number}`** (e.g. `TK-7`).

### Index Page

The table shows:
- **ID** (clickable link)
- **Name**
- **Status** badge
- **Project** — the linked project's `PJ-{id}` (if any)
- **Assignees** — user avatars or names
- **Progress** — visual progress bar (0–100%)
- **Start / Expected End dates**
- **Actions** — View, Edit, Delete

### Task Detail Page

- Task ID badge, name, status
- Linked project (if any)
- Description
- Progress bar
- Start, expected end, and actual end dates
- **Assignees** list
- **Activity Log** — all changes to the task
- **Edit** and **Log Time** buttons *(permission-gated)*

### Progress

Progress is a percentage (0–100) set manually when editing a task. The progress bar on the index and detail pages reflects this value.

### Statuses

| Status | Meaning |
|---|---|
| Not Started | Not yet started |
| In Progress | Currently being worked on |
| Done | Completed |

---

## 7. Timesheet

There are two views for tracking time.

### Log List

**Route:** `/time-logs`

A filterable table of all time entries. Available filters:
- **User** *(visible to those with team/all timesheet permissions)*
- **Team** *(visible to those with team/all timesheet permissions)*
- **Date**
- **Project**
- **Task**

Each row shows: Date, User (if visible), Context (Task / Project / Other), Description, Time Spent (formatted as `Xh Ym`).

Actions (View, Edit, Delete) appear based on your edit permissions.

#### Logging Time

Click **Log Time** (available on the time-log index, project detail, and task detail pages).

Fill in:
| Field | Description |
|---|---|
| **Date** | The date worked (defaults to today) |
| **Project** | Optional — link to a project |
| **Task** | Optional — filters to tasks in the selected project |
| **Description** | What you worked on |
| **Time Spent** | Hours as a decimal (e.g. `1.5` = 1h 30m). Min: 0.25 (15 min), Max: 24 |

**Quick-time buttons** (30m, 1h, 2h, 4h, 8h) fill the time field instantly.

### Weekly View

**Route:** `/timesheets/weekly`

A grid showing the current week with days as columns and tasks/projects/other as rows.

- **Navigate weeks** using the Prev / Next buttons, or click **This Week** to return to the current week.
- **Hover** over any time cell to see a tooltip with the log description.
- The **Today** column is highlighted.
- **Day totals** appear in the bottom row.
- **Week total** is shown in the bottom-right corner.
- Click a cell with time logged to view that entry (or a filtered list if multiple entries exist that day).

If you have team/all timesheet permissions, use the **User** or **Team** filter dropdowns to view other users' weekly logs.

---

## 8. Teams

**Route:** `/teams`

Teams group users together. Teams can be assigned to projects and are used for scoping leave/OT approvals and timesheet visibility.

### Index Page

Lists all teams *(or only your own teams, depending on your permission)* with:
- Team name
- Member count
- Leader names (displayed as badges)
- Actions: View, Edit, Delete *(permission-gated)*

### Team Detail Page

Shows two panels side by side:
- **Leaders** — users with the Leader role in this team
- **Members** — regular members

### Creating / Editing a Team *(requires `edit teams`)*

1. Click **Create Team** or the Edit button.
2. Enter the **Team Name**.
3. Check users to **add as members**.
4. Among the checked users, check the **Leader** checkbox next to those who should lead the team.
5. Click **Save**.

> Leaders have elevated permissions for approving leave/OT requests from their team members.

---

## 9. Users

**Route:** `/users`

Manage user accounts and their roles.

### User Profile

Every user has a profile page showing:
- **Profile picture** (or initial avatar)
- Name, position, email
- **Roles** assigned
- **Leave Balance** (in hours) with a link to the full change history
- **Leave Requests** — a summary table of recent leave requests
- **Teams** — teams the user belongs to, with Leader/Member label

### Editing Your Own Profile

Click **Profile** in the top-right profile menu to edit your own:
- Name
- Email
- Password
- Position
- Profile picture (crop tool provided — upload a photo and adjust the crop area)

### Managing Users *(requires appropriate `users` permissions)*

Admins and HR staff can:
- **Create** a new user (`create all user`)
- **Edit** any user's name, email, position, role assignments, and leave balance (`edit all user`)
- **Reset passwords** for users (`edit all user` or own account)
- **Delete** users (`delete all user`) — soft delete, the account is archived

### Leave Balance History

Click the **Leave Balance History** link on a user's profile to see a chronological log of all balance adjustments, including:
- Change amount (positive = added, negative = deducted)
- Balance after the change
- Reason for the change
- Who made the change

---

## 10. Leave Requests

**Route:** `/leave-requests`

Used to request time off. Leave is measured in **hours**.

### Submitting a Leave Request

1. Click **New Request**.
2. Fill in:
   | Field | Description |
   |---|---|
   | **Type** | Type of leave (e.g. Annual, Sick, Emergency) |
   | **Start** | Start date and time |
   | **End** | End date and time |
   | **Hours** | Total leave hours being consumed |
   | **Description** | Reason for the leave |
3. Click **Submit**.

Your request will be in **Pending** status until approved or rejected.

### Request Statuses

| Status | Meaning |
|---|---|
| Pending | Awaiting approval |
| Approved | Approved — leave balance will be/has been deducted |
| Rejected | Not approved — a rejection reason is provided |

### Approving / Rejecting *(requires approve permission)*

Users with `approve team leaves` or `approve all leaves` will see **Approve** and **Reject** buttons on pending requests.

- **Approve** — sets status to Approved and sends a notification to the requester.
- **Reject** — prompts for a rejection reason, then sends a notification.

The requester will receive a **bell notification** when their request is actioned.

---

## 11. Overtime (OT) Requests

**Route:** `/overtime-requests`

The process mirrors Leave Requests but tracks overtime hours worked outside regular hours.

### Submitting an OT Request

1. Click **New OT Request**.
2. Fill in start time, end time, hours, type, and description.
3. Click **Submit**.

### Approval Flow

Same as leave requests — approvers with `approve team ot` or `approve all ot` can approve or reject. The requester receives a notification either way.

---

## 12. Roles & Permissions

**Route:** `/roles` *(requires `module roles`)*

Roles bundle a set of permissions together and are assigned to users.

### Managing Roles *(requires `edit roles`)*

1. Go to **Roles** in the sidebar (Admin group).
2. Click **Create Role** to define a new role.
3. Give the role a **Name**.
4. Check the **permissions** to grant. Permissions are grouped by module.
5. Click **Save**.

### Assigning Roles to Users

Roles are assigned from the **User edit form** — look for the Roles section when editing a user.

### Deleting Roles *(requires `delete roles`)*

Use the Delete button on the roles index. Deleting a role removes it from all users currently assigned to it.

---

## 13. Notifications

The **bell icon** (🔔) in the top navigation bar shows a count of unread notifications.

### Notification Dropdown

Click the bell to open a dropdown showing your **5 most recent** unread notifications. Each entry shows:
- The sender's avatar (or initial)
- Notification title and short description
- Relative time (e.g. "2 minutes ago")

Click a notification to go to the relevant page (e.g. the leave request that was approved).

Click **View All** at the bottom to open the full notifications page.

### Notifications Page

**Route:** `/notifications`

All notifications are listed here with read/unread styling (unread entries have an indigo highlight). Opening this page marks all unread notifications as read.

### When Are Notifications Sent?

| Event | Who is notified |
|---|---|
| Leave request **approved** | The requester |
| Leave request **rejected** | The requester |
| OT request **approved** | The requester |
| OT request **rejected** | The requester |

---

## 14. Dark Mode

Click the **sun/moon icon** in the top navigation bar to toggle between light and dark themes.

Your preference is **saved automatically** and will persist the next time you open the application, even after logging out. On first visit, the theme follows your operating system's default (light or dark).

---

## 15. Permission Reference

The table below summarises what each permission grants. Permissions are grouped into roles by your administrator.

### Teams

| Permission | Access Granted |
|---|---|
| `module teams` | Access the Teams module |
| `view own teams` | See only teams you belong to |
| `view teams` | See all teams |
| `edit teams` | Create and edit teams |
| `delete teams` | Delete teams |

### Roles

| Permission | Access Granted |
|---|---|
| `module roles` | Access the Roles module |
| `edit roles` | Create and edit roles |
| `delete roles` | Delete roles |

### Users

| Permission | Access Granted |
|---|---|
| `module user` | Access the Users module |
| `view team user` | View profiles of users in your teams |
| `edit team user` | Edit users in your teams |
| `delete team user` | Delete users in your teams |
| `view all user` | View all user profiles |
| `create all user` | Create new user accounts |
| `edit all user` | Edit any user, reset passwords, adjust leave balance |
| `delete all user` | Delete any user |

### Leave Requests

| Permission | Access Granted |
|---|---|
| `module leaves` | Access the Leave Requests module |
| `view own leaves` | See your own leave requests |
| `edit own leaves` | Submit and edit your own leave requests |
| `delete own leaves` | Delete your own leave requests |
| `view team leaves` | See leave requests from your team |
| `edit team leaves` | Edit leave requests from your team |
| `approve team leaves` | Approve/reject leave requests from your team |
| `delete team leaves` | Delete leave requests from your team |
| `edit team leaves balance` | Adjust leave balance for team members |
| `view all leaves` | See all leave requests |
| `edit all leaves` | Edit any leave request |
| `approve all leaves` | Approve/reject any leave request |
| `delete all leaves` | Delete any leave request |
| `edit all leaves balance` | Adjust leave balance for any user |

### Overtime Requests

| Permission | Access Granted |
|---|---|
| `module ot` | Access the OT Requests module |
| `view own ot` | See your own OT requests |
| `edit own ot` | Submit and edit your own OT requests |
| `delete own ot` | Delete your own OT requests |
| `view team ot` | See OT requests from teams you **lead** |
| `edit team ot` | Edit OT requests from teams you lead |
| `approve team ot` | Approve/reject OT requests from teams you lead |
| `delete team ot` | Delete OT requests from teams you lead |
| `view all ot` | See all OT requests |
| `edit all ot` | Edit any OT request |
| `approve all ot` | Approve/reject any OT request |
| `delete all ot` | Delete any OT request |

### Projects

| Permission | Access Granted |
|---|---|
| `module projects` | Access the Projects module |
| `view all projects` | View all projects |
| `view assigned projects` | View only projects you are assigned to |
| `edit projects` | Create and edit any project |
| `edit assigned projects` | Edit projects you are assigned to |
| `delete projects` | Delete projects |

### Tasks

| Permission | Access Granted |
|---|---|
| `module tasks` | Access the Tasks module |
| `view all tasks` | View all tasks |
| `view team tasks` | View tasks assigned to your team members |
| `view assigned tasks` | View only tasks assigned to you |
| `edit tasks` | Create and edit any task |
| `edit team tasks` | Edit tasks for your team |
| `edit assigned tasks` | Edit tasks assigned to you |
| `delete tasks` | Delete tasks |

### Timesheet

| Permission | Access Granted |
|---|---|
| `module timesheet` | Access the Timesheet module |
| `view all timesheet` | See time logs for all users |
| `view team timesheet` | See time logs from users in your teams |
| `view own timesheet` | See only your own time logs |
| `edit timesheet` | Log, edit, and delete time for any user |
| `edit team timesheet` | Log, edit, and delete time for team members |
| `edit own timesheet` | Log, edit, and delete your own time entries |

### Announcements

| Permission | Access Granted |
|---|---|
| `module announcements` | Access the Announcements module |
| `edit announcements` | Create and edit announcements |
| `delete announcements` | Delete announcements |
| `edit policies` | Edit company policies *(reserved for future use)* |
