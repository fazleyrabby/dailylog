/**
 * DailyLOG theme registry.
 *
 * Single source of truth for the available visual themes. The theme engine in
 * layouts/app.blade.php applies the active theme by setting `data-theme="<id>"`
 * on <html> and syncing the `.dark` class to the theme's `family` (so Tailwind's
 * `dark:` variant keeps working for dark-family themes).
 *
 * `labels` is an optional canonical -> display map. An empty map means the UI
 * uses canonical labels unchanged. A future theme (e.g. Quest Mode) can relabel
 * nav terminology purely in the presentation layer, e.g. { Tasks: 'Quests' }.
 */
export const THEMES = [
    { id: 'dark',            name: 'DailyLOG Dark',    family: 'dark',  labels: {} },
    { id: 'paper',           name: 'DailyLOG Paper',   family: 'light', labels: {} },
    { id: 'neo-brutalist',   name: 'Neo Brutalist OS', family: 'light', labels: {} },
    { id: 'retro',           name: 'Retro 8-Bit',      family: 'light', labels: {} },
    { id: 'macintosh',       name: 'Macintosh 1998',   family: 'light', labels: {} },
    { id: 'mission-control', name: 'Mission Control',  family: 'dark',  labels: {} },
    { id: 'quest',           name: 'Quest Mode',       family: 'dark',  labels: {} },
];

export const THEME_MAP = Object.fromEntries(THEMES.map(t => [t.id, t]));

/**
 * Map any stored / legacy value to a valid theme id.
 * Existing users may have 'dark' or 'light' persisted; 'light' -> 'paper'.
 */
export function normalizeThemeId(val) {
    if (!val) return 'dark';
    if (val === 'light') return 'paper';
    return THEME_MAP[val] ? val : 'dark';
}

export function themeFamily(id) {
    return (THEME_MAP[normalizeThemeId(id)] || THEME_MAP.dark).family;
}
