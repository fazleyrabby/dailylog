# DailyLOG 🚀

> **DailyLOG** is a personal Life OS and single source of truth dashboard. It provides high-density, high-productivity modules to track tasks, notes, journal reflections, bookmarks, learning paths, and project progress.

<img width="1439" height="839" alt="image" src="https://github.com/user-attachments/assets/05bc1251-a934-4c2c-88e2-c06fe66ebc69" />

## 🛠️ Tech Stack & Architecture

*   **Backend**: Laravel 12 monolith utilizing PostgreSQL (with native enums, CITEXT, GIN full-text search index, check constraints, and integrity triggers).
*   **Frontend**: Tailwind CSS v4, Alpine.js, and Vite.
*   **Markdown Rendering**: Integrated `marked` library for rich client-side Markdown rendering and inline backlink parsing.

---

## ✨ Key Features Implemented

### 📝 Notes & Bidirectional Backlinks
*   **Eloquent CRUD**: Complete backend persistence for note creation, modification, and archiving.
*   **Bidirectional Backlinking**: Automatic parsing of internal links (`[[Note Title]]` or `[[note-slug]]`) via a backend `LinkService`. Connected notes dynamically show references in the **Linked Backlinks** context drawer.
*   **Markdown Preview**: Instantly toggle between Markdown text editing and parsed HTML preview.
*   **Resizable Panels**: Left navigation and note canvas split by a draggable border handle built with Alpine.js mouse trackers.

### 📅 Daily Journal & Reflections
*   **Dynamic Calendar**: A custom 30-day interactive calendar highlighting dates containing journal reflections.
*   **Logs History**: Sidebar list displaying chronological reflections.
*   **Multi-Section Logs**: Captures structured daily updates across four dimensions:
    *   *What I Learned Today*
    *   *What I Worked On Today*
    *   *Wins & Milestones*
    *   *Ideas Captured*
*   **Serialization**: Data is stored as JSON in the database `body` field of the `entries` table, maintaining an indexable, migration-free structure.

### ☑️ Persistent Task Board
*   **Smart Lists**: Partitioned tasks categorized into *Inbox*, *Today*, *Upcoming*, and *Completed*.
*   **Tactile Actions**: Toggle task completion, edit titles/tags, and cycle priorities (Low, Medium, High).
*   **Dynamic Defaults**: Auto-attaches attributes (e.g. `due:today`) based on active filtering tabs.

---

## 🎨 Design System

*   **Typography**: Styled completely using **IBM Plex Sans** for clean, readable sans-serif elements.
*   **Color Palette**: A curated "Warm Stone" light theme and high-contrast dark theme (class-based `html.dark` variant).
*   **Tactile Controls**: Custom 3D button interactions (`border-b-[3px]`, `hover:border-b-[4px]`, `active:border-b-[1px]`) that feel tactile and responsive.
*   **Sleek Scrollbars**: Custom premium `6px` scrollbars that dynamically match the active theme.

---

## 🚀 Installation & Setup

1.  **Clone & Install Dependencies**:
    ```bash
    composer install
    npm install
    ```

2.  **Environment Setup**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3.  **Run Migrations & Seed**:
    ```bash
    php artisan migrate --seed
    ```

4.  **Launch Local Servers**:
    ```bash
    # Run Vite assets compilation
    npm run dev
    
    # Run Laravel local development server
    php artisan serve
    ```

---

## 🧪 Testing

The codebase includes feature tests to verify endpoint integrity and data serialization. Run tests using PHPUnit:

```bash
php artisan test
```
