<?php
// Decorative interface icons, not university insignia. Fixed paths only.
function ui_icon(string $name): string {
    $paths = [
        // Original tag-and-magnifier project mark; no institutional symbols.
        'lost-found' => '<path d="M3 3h8l7 7-8 8-7-7Z"/><circle cx="7" cy="7" r="1.2"/><circle cx="16" cy="15" r="4"/><path d="m19 18 3 3"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'pin' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4m8-4v4M4 11h16m-11 4h2m3 0h2m-7 3h2"/>',
        'bag' => '<rect x="5" y="7" width="14" height="14" rx="3"/><path d="M9 7V5a3 3 0 0 1 6 0v2M8 13h8m-8 4h8"/>',
        'book' => '<path d="M12 5C8 2 3 4 3 4v15s5-2 9 1c4-3 9-1 9-1V4s-5-2-9 1Zm0 0v15"/>',
        'device' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 18h4"/>',
        'key' => '<circle cx="8" cy="8" r="5"/><path d="m12 12 9 9m-5-5 3-3m-1 5 3-3"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'edit' => '<path d="m15 4 5 5M4 20l5-1L21 7a2 2 0 0 0-5-5L4 14Z"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m8 12 3 3 5-6"/>',
        'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? $paths['bag']) . '</svg>';
}
function category_icon(string $category): string {
    return ui_icon(match ($category) { 'Electronics' => 'device', 'Books and stationery' => 'book', 'Keys' => 'key', default => 'bag' });
}
