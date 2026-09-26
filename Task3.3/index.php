<?php
// index.php — Campus Lost and Found Board

$storageFile = __DIR__ . '/items_data.json';

// Seed a few starting notices the first time this runs
if (!file_exists($storageFile)) {
    $seed = [
        [
            'id' => 'LF-101',
            'title' => 'Student ID Card',
            'category' => 'Cards & IDs',
            'location' => 'Library ground floor turnstiles',
            'type' => 'found',
            'date' => '2026-09-24',
            'description' => 'Left near the scanning barrier. Name partly visible on the front.',
            'reporter' => 'Campus Security Desk',
            'status' => 'posted',
        ],
        [
            'id' => 'LF-102',
            'title' => 'Scientific Calculator, fx-570EX',
            'category' => 'Electronics',
            'location' => 'Engineering Block, Lab 204',
            'type' => 'lost',
            'date' => '2026-09-25',
            'description' => 'Black casing, initials scratched into the battery cover.',
            'reporter' => 'Alex Tan',
            'status' => 'posted',
        ],
        [
            'id' => 'LF-103',
            'title' => 'Steel Water Bottle',
            'category' => 'Personal Items',
            'location' => 'Student Pavilion, outside bench',
            'type' => 'found',
            'date' => '2026-09-26',
            'description' => 'Silver insulated bottle, a few sticker decals on the side.',
            'reporter' => 'Sarah Lee',
            'status' => 'returned',
        ],
    ];
    file_put_contents($storageFile, json_encode($seed, JSON_PRETTY_PRINT));
}

$records = json_decode(file_get_contents($storageFile), true) ?? [];
$flash = '';

function next_ref_id(array $records): string {
    $max = 100;
    foreach ($records as $r) {
        if (preg_match('/^LF-(\d+)$/', $r['id'] ?? '', $m)) {
            $max = max($max, (int)$m[1]);
        }
    }
    return 'LF-' . ($max + 1);
}

// pin a new report 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'new_report') {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $type        = ($_POST['type'] ?? '') === 'found' ? 'found' : 'lost';
    $description = trim($_POST['description'] ?? '');
    $reporter    = trim($_POST['reporter'] ?? '');

    if ($title !== '' && $location !== '' && $reporter !== '') {
        $newItem = [
            'id' => next_ref_id($records),
            'title' => $title,
            'category' => $category !== '' ? $category : 'Other',
            'location' => $location,
            'type' => $type,
            'date' => date('Y-m-d'),
            'description' => $description,
            'reporter' => $reporter,
            'status' => 'posted',
        ];
        array_unshift($records, $newItem);
        file_put_contents($storageFile, json_encode($records, JSON_PRETTY_PRINT));
        $flash = "Pinned to the board — reference {$newItem['id']}.";
    } else {
        $flash = 'Couldn\'t pin that — item, location and your name are all required.';
    }
}

// toggle returned/posted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $targetId = $_POST['item_id'] ?? '';
    foreach ($records as &$item) {
        if ($item['id'] === $targetId) {
            $item['status'] = ($item['status'] === 'returned') ? 'posted' : 'returned';
            $flash = $item['status'] === 'returned'
                ? "Marked {$targetId} as returned."
                : "Put {$targetId} back on the board.";
            break;
        }
    }
    unset($item);
    file_put_contents($storageFile, json_encode($records, JSON_PRETTY_PRINT));
}

// Counts for the plaque strip 
$total    = count($records);
$lostN    = count(array_filter($records, fn($r) => $r['type'] === 'lost' && $r['status'] === 'posted'));
$foundN   = count(array_filter($records, fn($r) => $r['type'] === 'found' && $r['status'] === 'posted'));
$returnedN = count(array_filter($records, fn($r) => $r['status'] === 'returned'));

// Search and filter
$q      = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$visible = array_filter($records, function ($r) use ($q, $filter) {
    $matchesQ = $q === ''
        || stripos($r['title'], $q) !== false
        || stripos($r['location'], $q) !== false
        || stripos($r['category'], $q) !== false;

    $matchesFilter = match ($filter) {
        'lost'     => $r['type'] === 'lost' && $r['status'] === 'posted',
        'found'    => $r['type'] === 'found' && $r['status'] === 'posted',
        'returned' => $r['status'] === 'returned',
        default    => true,
    };

    return $matchesQ && $matchesFilter;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>The Lost &amp; Found Board</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@500;700&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root {
        --board: #8B6B43;
        --board-deep: #6E5233;
        --card: #FBF6EA;
        --card-edge: #EADFC4;
        --ink: #2B2318;
        --ink-soft: #6B5F4B;
        --lost: #B33F32;
        --found: #2F6F5E;
        --accent: #C98A2C;
        --accent-deep: #A66F1E;
        --returned: #55724A;
        --rule: rgba(43, 35, 24, 0.16);
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: 'Courier Prime', monospace;
        color: var(--ink);
        background-color: var(--board);
        background-image:
            radial-gradient(circle at 12% 22%, rgba(0,0,0,0.10) 1px, transparent 1.4px),
            radial-gradient(circle at 68% 8%, rgba(0,0,0,0.08) 1.2px, transparent 1.6px),
            radial-gradient(circle at 38% 62%, rgba(0,0,0,0.09) 1px, transparent 1.4px),
            radial-gradient(circle at 84% 48%, rgba(0,0,0,0.07) 1.3px, transparent 1.6px),
            radial-gradient(circle at 55% 88%, rgba(0,0,0,0.09) 1.1px, transparent 1.5px),
            radial-gradient(circle at 20% 78%, rgba(255,255,255,0.05) 1px, transparent 1.4px);
        background-size: 140px 140px;
        padding-bottom: 60px;
    }
    a { color: inherit; }

    /* --- Header rail --- */
    .rail {
        background: var(--board-deep);
        border-bottom: 6px solid var(--ink);
        padding: 22px 28px;
    }
    .rail-inner {
        max-width: 1080px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 14px;
    }
    .wordmark {
        font-family: 'Roboto Slab', Georgia, serif;
        font-weight: 700;
        font-size: 2rem;
        color: var(--card);
        margin: 0;
        line-height: 1.1;
    }
    .wordmark span { color: var(--accent); }
    .tagline {
        font-family: 'Courier Prime', monospace;
        color: #d9c9a6;
        margin: 6px 0 0;
        font-size: 0.92rem;
        max-width: 46ch;
    }
    .course-tag {
        font-family: 'Courier Prime', monospace;
        color: #a68f66;
        margin: 8px 0 0;
        font-size: 0.78rem;
        letter-spacing: 0.03em;
    }

    /* Pin-a-report control */
    .pin-toggle {
        font-family: 'Roboto Slab', Georgia, serif;
        font-weight: 700;
        background: var(--accent);
        color: var(--ink);
        border: none;
        padding: 11px 20px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.95rem;
        list-style: none;
    }
    .pin-toggle::-webkit-details-marker { display: none; }
    .pin-toggle:hover { background: var(--accent-deep); }
    .pin-panel { max-width: 1080px; margin: 0 auto; }
    .pin-panel[open] summary.pin-toggle { margin: 22px auto 0; display: block; width: fit-content; }
    .pin-panel:not([open]) summary.pin-toggle { margin: 22px auto 0; display: block; width: fit-content; }

    .pin-form {
        background: var(--card);
        border: 1px solid var(--card-edge);
        border-radius: 4px;
        margin: 16px auto 0;
        padding: 26px 28px;
        max-width: 1080px;
        box-shadow: 0 10px 26px rgba(0,0,0,0.18);
    }
    .pin-form h2 {
        font-family: 'Roboto Slab', Georgia, serif;
        font-size: 1.15rem;
        margin: 0 0 4px;
    }
    .pin-form p.hint { color: var(--ink-soft); margin: 0 0 18px; font-size: 0.88rem; }
    .field-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 24px; }
    .field-grid .full { grid-column: 1 / -1; }
    label { display: block; font-size: 0.85rem; margin-bottom: 4px; color: var(--ink-soft); }
    input[type=text], select, textarea {
        width: 100%;
        font-family: 'Courier Prime', monospace;
        font-size: 0.95rem;
        padding: 8px 2px;
        border: none;
        border-bottom: 2px solid var(--rule);
        background: transparent;
        color: var(--ink);
    }
    input[type=text]:focus, select:focus, textarea:focus {
        outline: none;
        border-bottom-color: var(--accent-deep);
    }
    textarea { resize: vertical; min-height: 56px; }
    .submit-row { margin-top: 20px; display: flex; gap: 12px; align-items: center; }
    .btn-primary {
        font-family: 'Roboto Slab', Georgia, serif;
        font-weight: 700;
        background: var(--ink);
        color: var(--card);
        border: none;
        padding: 10px 22px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.92rem;
    }
    .btn-primary:hover { background: #171106; }

    /*  Flash message */
    .flash {
        max-width: 1080px;
        margin: 18px auto 0;
        background: var(--card);
        border-left: 5px solid var(--accent);
        padding: 10px 16px;
        border-radius: 3px;
        font-size: 0.9rem;
    }

    /* Plaque strip (stats) */
    .plaque {
        max-width: 1080px;
        margin: 22px auto 0;
        background: var(--card);
        border: 1px solid var(--card-edge);
        border-radius: 4px;
        display: flex;
        flex-wrap: wrap;
        box-shadow: 0 6px 16px rgba(0,0,0,0.14);
    }
    .plaque-item {
        flex: 1 1 140px;
        padding: 14px 18px;
        border-right: 1px solid var(--rule);
        text-align: center;
    }
    .plaque-item:last-child { border-right: none; }
    .plaque-num {
        font-family: 'Roboto Slab', Georgia, serif;
        font-size: 1.5rem;
        font-weight: 700;
        display: block;
    }
    .plaque-label { font-size: 0.78rem; color: var(--ink-soft); }

    /* Toolbar: search and filter tabs */
    .toolbar {
        max-width: 1080px;
        margin: 18px auto 0;
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: center;
    }
    .search-box {
        flex: 1 1 240px;
        background: var(--card);
        border-radius: 3px;
        padding: 9px 14px;
        border: 1px solid var(--card-edge);
    }
    .search-box input {
        border: none;
        width: 100%;
        font-family: 'Courier Prime', monospace;
        font-size: 0.92rem;
        background: transparent;
        color: var(--ink);
    }
    .search-box input:focus { outline: none; }
    .tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .tab {
        font-family: 'Roboto Slab', Georgia, serif;
        font-size: 0.82rem;
        padding: 8px 14px;
        border-radius: 3px;
        background: rgba(251, 246, 234, 0.55);
        color: var(--card);
        text-decoration: none;
        border: 1px solid rgba(251, 246, 234, 0.4);
    }
    .tab:hover { background: rgba(251, 246, 234, 0.8); color: var(--ink); }
    .tab.active { background: var(--card); color: var(--ink); font-weight: 700; }

    /* Board grid */
    .board {
        max-width: 1080px;
        margin: 30px auto 0;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 34px 26px;
        padding: 0 4px;
    }
    .empty-board {
        max-width: 1080px;
        margin: 40px auto 0;
        text-align: center;
        color: #f1e8d2;
        font-size: 1rem;
    }

    .notice { position: relative; }
    .notice:nth-of-type(4n+1) { transform: rotate(-1.6deg); }
    .notice:nth-of-type(4n+2) { transform: rotate(1.1deg); }
    .notice:nth-of-type(4n+3) { transform: rotate(-0.6deg); }
    .notice:nth-of-type(4n+4) { transform: rotate(1.7deg); }
    .notice:hover, .notice:focus-within { transform: rotate(0deg); z-index: 2; }
    .notice { transition: transform 0.18s ease; }

    .notice::before {
        content: '';
        position: absolute;
        top: -9px;
        left: 50%;
        transform: translateX(-50%);
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: radial-gradient(circle at 35% 30%, #fff 0%, var(--pin-color, var(--lost)) 45%, #7a1f16 100%);
        box-shadow: 0 3px 4px rgba(0,0,0,0.4);
        z-index: 3;
    }
    .notice.type-found::before { --pin-color: var(--found); background: radial-gradient(circle at 35% 30%, #fff 0%, var(--found) 45%, #123c30 100%); }

    .notice-card {
        background: var(--card);
        border: 1px solid var(--card-edge);
        border-radius: 3px;
        box-shadow: 0 8px 18px rgba(0,0,0,0.22);
        overflow: hidden;
    }
    .notice-card > summary {
        list-style: none;
        cursor: pointer;
        padding: 20px 18px 16px;
    }
    .notice-card > summary::-webkit-details-marker { display: none; }

    .tag-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .tag {
        font-family: 'Roboto Slab', Georgia, serif;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 2px;
        color: #fff;
    }
    .tag.lost { background: var(--lost); }
    .tag.found { background: var(--found); }
    .ref { font-size: 0.75rem; color: var(--ink-soft); }

    .notice-title { font-size: 1.05rem; font-weight: 700; margin: 0 0 6px; line-height: 1.3; }
    .notice-meta { font-size: 0.85rem; color: var(--ink-soft); margin: 0; }
    .expand-hint { font-size: 0.78rem; color: var(--accent-deep); margin-top: 10px; }
    .notice-card[open] .expand-hint { display: none; }

    .notice-body { padding: 0 18px 20px; border-top: 1px dashed var(--rule); margin-top: 2px; }
    .notice-body p { font-size: 0.88rem; line-height: 1.5; margin: 14px 0; }
    .notice-body .field-line { font-size: 0.82rem; color: var(--ink-soft); margin: 4px 0; }
    .claim-form { margin-top: 14px; }
    .btn-claim {
        font-family: 'Roboto Slab', Georgia, serif;
        font-size: 0.85rem;
        font-weight: 700;
        width: 100%;
        padding: 9px;
        border-radius: 3px;
        border: 1.5px solid var(--ink);
        background: transparent;
        color: var(--ink);
        cursor: pointer;
    }
    .btn-claim:hover { background: var(--ink); color: var(--card); }

    .stamp {
        position: absolute;
        top: 44%;
        right: 14px;
        transform: rotate(-14deg);
        border: 3px solid var(--returned);
        color: var(--returned);
        font-family: 'Roboto Slab', Georgia, serif;
        font-weight: 700;
        font-size: 0.85rem;
        padding: 4px 10px;
        border-radius: 4px;
        opacity: 0.85;
        pointer-events: none;
    }

    @media (max-width: 600px) {
        .field-grid { grid-template-columns: 1fr; }
        .wordmark { font-size: 1.5rem; }
    }
</style>
</head>
<body>

<div class="rail">
    <div class="rail-inner">
        <div>
            <h1 class="wordmark">The Lost <span>&amp;</span> Found Board</h1>
            <p class="tagline">Pin what you've lost. Post what you've found. Take it down once it's back home.</p>
            <p class="course-tag">SWE40006 &mdash; Task 3.3</p>
        </div>
    </div>
</div>

<details class="pin-panel" id="pinPanel">
    <summary class="pin-toggle">+ Pin a report</summary>
    <div class="pin-form">
        <h2>What happened?</h2>
        <p class="hint">A few details help someone recognise it. Reference number gets assigned once it's posted.</p>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="new_report">
            <div class="field-grid">
                <div>
                    <label for="type">This item was...</label>
                    <select id="type" name="type" required>
                        <option value="lost">Lost — I'm missing this</option>
                        <option value="found">Found — I found this</option>
                    </select>
                </div>
                <div>
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option>Electronics</option>
                        <option>Cards &amp; IDs</option>
                        <option>Personal Items</option>
                        <option>Books &amp; Stationery</option>
                        <option>Clothing</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="full">
                    <label for="title">Item</label>
                    <input type="text" id="title" name="title" placeholder="e.g. Navy backpack, wireless mouse" required>
                </div>
                <div class="full">
                    <label for="location">Where</label>
                    <input type="text" id="location" name="location" placeholder="e.g. Block A Level 3 hallway" required>
                </div>
                <div class="full">
                    <label for="description">Anything that helps identify it</label>
                    <textarea id="description" name="description" placeholder="Colour, brand, stickers, scratches..."></textarea>
                </div>
                <div class="full">
                    <label for="reporter">Your name or student ID</label>
                    <input type="text" id="reporter" name="reporter" required>
                </div>
            </div>
            <div class="submit-row">
                <button type="submit" class="btn-primary">Pin it to the board</button>
            </div>
        </form>
    </div>
</details>

<?php if ($flash): ?>
    <div class="flash"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="plaque">
    <div class="plaque-item"><span class="plaque-num"><?= $total ?></span><span class="plaque-label">pinned in total</span></div>
    <div class="plaque-item"><span class="plaque-num"><?= $lostN ?></span><span class="plaque-label">still lost</span></div>
    <div class="plaque-item"><span class="plaque-num"><?= $foundN ?></span><span class="plaque-label">waiting to be claimed</span></div>
    <div class="plaque-item"><span class="plaque-num"><?= $returnedN ?></span><span class="plaque-label">returned</span></div>
</div>

<div class="toolbar">
    <form method="GET" action="index.php" class="search-box">
        <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
        <input type="text" name="q" placeholder="Search item, location or category..." value="<?= htmlspecialchars($q) ?>" onchange="this.form.submit()">
    </form>
    <div class="tabs">
        <a class="tab <?= $filter === 'all' ? 'active' : '' ?>" href="index.php?filter=all<?= $q ? '&q='.urlencode($q) : '' ?>">All</a>
        <a class="tab <?= $filter === 'lost' ? 'active' : '' ?>" href="index.php?filter=lost<?= $q ? '&q='.urlencode($q) : '' ?>">Lost</a>
        <a class="tab <?= $filter === 'found' ? 'active' : '' ?>" href="index.php?filter=found<?= $q ? '&q='.urlencode($q) : '' ?>">Found</a>
        <a class="tab <?= $filter === 'returned' ? 'active' : '' ?>" href="index.php?filter=returned<?= $q ? '&q='.urlencode($q) : '' ?>">Returned</a>
    </div>
</div>

<?php if (empty($visible)): ?>
    <p class="empty-board">Nothing pinned under these filters. Clear the search, or be the first to post one.</p>
<?php else: ?>
    <div class="board">
        <?php foreach ($visible as $item): ?>
            <div class="notice type-<?= $item['type'] ?>">
                <details class="notice-card">
                    <summary>
                        <div class="tag-row">
                            <span class="tag <?= $item['type'] ?>"><?= $item['type'] === 'lost' ? 'Lost' : 'Found' ?></span>
                            <span class="ref"><?= htmlspecialchars($item['id']) ?></span>
                        </div>
                        <h3 class="notice-title"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="notice-meta"><?= htmlspecialchars($item['location']) ?> · <?= htmlspecialchars($item['date']) ?></p>
                        <p class="expand-hint">Tap to read more</p>
                    </summary>
                    <div class="notice-body">
                        <p><?= htmlspecialchars($item['description'] ?: 'No further details given.') ?></p>
                        <p class="field-line">Category: <?= htmlspecialchars($item['category']) ?></p>
                        <p class="field-line">Posted by: <?= htmlspecialchars($item['reporter']) ?></p>
                        <form method="POST" action="index.php" class="claim-form">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">
                            <button type="submit" class="btn-claim">
                                <?= $item['status'] === 'returned' ? 'Put back on the board' : 'Mark as returned' ?>
                            </button>
                        </form>
                    </div>
                </details>
                <?php if ($item['status'] === 'returned'): ?>
                    <div class="stamp">RETURNED</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>