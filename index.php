<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'tn';
$langFile = __DIR__ . '/lang/' . $lang . '.json';
$labels = file_exists($langFile) ? json_decode(file_get_contents($langFile), true) : [];
$defaultLabels = json_decode(file_get_contents(__DIR__ . '/lang/en.json'), true);
$l = array_merge($defaultLabels, $labels);

try {
    $stmt = $conn->query("SELECT id, name_fr, name_ar FROM gouvernorats ORDER BY id");
    $governorates = $stmt->fetchAll();

    $stmt = $conn->query("SELECT id, category_key FROM restaurant_categories ORDER BY category_key");
    $categories = $stmt->fetchAll();

    $stmt = $conn->query("
        SELECT r.id, r.name, r.average_rating, p.name_fr as place, g.name_fr as governorate,
               STRING_AGG(rc.category_key, ',') as categories
        FROM restaurants r
        JOIN places p ON r.place_id = p.id
        JOIN gouvernorats g ON p.gouvernorat_id = g.id
        LEFT JOIN restaurant_categories_junction rcj ON r.id = rcj.restaurant_id
        LEFT JOIN restaurant_categories rc ON rcj.category_id = rc.id
        GROUP BY r.id, r.name, r.average_rating, p.name_fr, g.name_fr
        ORDER BY r.name
    ");
    $restaurants = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($l['title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; min-height: 100vh; display: flex; flex-direction: column; }
        
        .container { max-width: 900px; margin: 0 auto; padding: 20px; flex: 1; }
        
        h1 { text-align: center; margin: 40px 0 20px; }
        h1 span { display: block; font-size: 1rem; font-weight: 400; color: #666; margin-top: 5px; }
        
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 15px 20px; font-size: 18px; border: 2px solid #ddd; border-radius: 8px; outline: none; }
        .search-box input:focus { border-color: #FF6B35; }
        .search-box button { padding: 15px 30px; font-size: 18px; background: #FF6B35; color: white; border: none; border-radius: 8px; cursor: pointer; }
        
        .filter-group { margin-bottom: 15px; }
        .filter-label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .filter-options { display: flex; flex-wrap: wrap; gap: 8px; }
        .filter-option { display: none; }
        .filter-option + label {
            padding: 8px 16px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .filter-option:checked + label {
            background: #FF6B35;
            border-color: #FF6B35;
            color: white;
        }
        
        .count { color: #666; margin-bottom: 15px; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .card-icon { font-size: 40px; margin-bottom: 10px; }
        .card-name { font-size: 1.2rem; font-weight: 600; margin-bottom: 5px; }
        .card-place { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
        .card-rating { color: #FFB800; }
        
        .empty { text-align: center; padding: 60px; color: #999; }
        
        .error { background: #fee; color: #c00; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        
        .footer { text-align: center; padding: 15px; background: #333; color: #999; margin-top: 30px; font-size: 14px; }
        .lang-switch { margin-bottom: 8px; }
        .lang-switch a { color: #fff; text-decoration: none; margin: 0 10px; }
        .lang-switch a.active { color: #FF6B35; font-weight: bold; }
    </style>
</head>
<body dir="<?= in_array($lang, ['ar', 'tn']) ? 'rtl' : 'ltr' ?>">
    <div class="container">
        <h1><?= htmlspecialchars($l['title']) ?><span><?= htmlspecialchars($l['subtitle']) ?></span></h1>
        
        <?php if (isset($error)): ?>
            <div class="error">Database Error: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="search-box">
            <input type="text" id="search" placeholder="<?= htmlspecialchars($l['search']) ?>">
            <button onclick="filterData()"><?= htmlspecialchars($l['search_btn']) ?></button>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <span class="filter-label"><?= htmlspecialchars($l['governorate']) ?></span>
                <div class="filter-options" id="governorateFilters">
                    <input type="checkbox" class="filter-option" id="gov_all" value="" checked>
                    <label for="gov_all"><?= htmlspecialchars($l['all'] ?? 'All') ?></label>
                    <?php foreach ($governorates as $g): ?>
                        <?php $name = ($lang === 'ar' || $lang === 'tn') ? ($g['name_ar'] ?: $g['name_fr']) : $g['name_fr']; ?>
                        <input type="checkbox" class="filter-option" id="gov_<?= htmlspecialchars($g['name_fr']) ?>" value="<?= htmlspecialchars($g['name_fr']) ?>">
                        <label for="gov_<?= htmlspecialchars($g['name_fr']) ?>"><?= htmlspecialchars($name) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-group">
                <span class="filter-label"><?= htmlspecialchars($l['category']) ?></span>
                <div class="filter-options" id="categoryFilters">
                    <input type="checkbox" class="filter-option" id="cat_all" value="" checked>
                    <label for="cat_all"><?= htmlspecialchars($l['all'] ?? 'All') ?></label>
                    <?php foreach ($categories as $c): 
                        $catKey = $c['category_key'];
                        $catLabel = isset($l['categories'][$catKey]) ? $l['categories'][$catKey] : ucfirst($catKey);
                    ?>
                        <input type="checkbox" class="filter-option" id="cat_<?= htmlspecialchars($catKey) ?>" value="<?= htmlspecialchars($catKey) ?>">
                        <label for="cat_<?= htmlspecialchars($catKey) ?>"><?= htmlspecialchars($catLabel) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <p class="count" id="count"></p>
        <div class="grid" id="grid"></div>
    </div>

    <footer class="footer">
        <div class="lang-switch">
            <a href="#" onclick="setLang('en'); return false;" <?= $lang === 'en' ? 'class="active"' : '' ?>>English</a> | 
            <a href="#" onclick="setLang('fr'); return false;" <?= $lang === 'fr' ? 'class="active"' : '' ?>>Français</a> |
            <a href="#" onclick="setLang('tn'); return false;" <?= $lang === 'tn' ? 'class="active"' : '' ?>>التونسية</a> |
            <a href="#" onclick="setLang('ar'); return false;" <?= $lang === 'ar' ? 'class="active"' : '' ?>>العربية</a>
        </div>
        <p><?= htmlspecialchars($l['footer']) ?></p>
    </footer>

    <script>
        const restaurants = <?= json_encode($restaurants) ?>;
        const labels = <?= json_encode($l) ?>;
        
        function setLang(lang) {
            localStorage.setItem('lang', lang);
            window.location.href = 'index.php?lang=' + lang;
        }
        
        const icons = {
            pizza: '🍕', burger: '🍔', kebab: '🥙', traditional: '🥘',
            seafood: '🦐', sandwich: '🥪', dessert: '🍰', sweet: '🍬',
            spicy: '🌶️', drink: '🥤'
        };

        function render(data) {
            const grid = document.getElementById('grid');
            document.getElementById('count').textContent = data.length + ' ' + labels.restaurants;
            
            if (data.length === 0) {
                grid.innerHTML = '<div class="empty">' + labels.no_results + '</div>';
                return;
            }

            grid.innerHTML = data.map(r => {
                const cats = r.categories ? r.categories.split(',') : [];
                const icon = cats.length ? icons[cats[0]] || '🍽️' : '🍽️';
                return `
                    <div class="card">
                        <div class="card-icon">${icon}</div>
                        <div class="card-name">${r.name}</div>
                        <div class="card-place">${r.place}, ${r.governorate}</div>
                        <div class="card-rating">⭐ ${parseFloat(r.average_rating).toFixed(1)}</div>
                    </div>
                `;
            }).join('');
        }

        function filterData() {
            const q = document.getElementById('search').value.toLowerCase();
            
            const govAll = document.getElementById('gov_all').checked;
            const catAll = document.getElementById('cat_all').checked;
            
            const govFilters = Array.from(document.querySelectorAll('#governorateFilters input:checked'))
                .map(cb => cb.value).filter(v => v !== '');
            const catFilters = Array.from(document.querySelectorAll('#categoryFilters input:checked'))
                .map(cb => cb.value).filter(v => v !== '');
            
            const filtered = restaurants.filter(r => {
                const matchSearch = !q || r.name.toLowerCase().includes(q) || (r.categories && r.categories.includes(q));
                const matchGov = govAll || govFilters.length === 0 || govFilters.includes(r.governorate);
                const matchCat = catAll || catFilters.length === 0 || (r.categories && catFilters.some(cat => r.categories.includes(cat)));
                return matchSearch && matchGov && matchCat;
            });
            render(filtered);
        }

        document.getElementById('search').addEventListener('input', filterData);
        
        document.querySelectorAll('#governorateFilters .filter-option').forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.id === 'gov_all' && this.checked) {
                    document.querySelectorAll('#governorateFilters .filter-option:not(#gov_all)').forEach(c => c.checked = false);
                } else if (this.id !== 'gov_all' && this.checked) {
                    document.getElementById('gov_all').checked = false;
                }
                filterData();
            });
        });
        
        document.querySelectorAll('#categoryFilters .filter-option').forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.id === 'cat_all' && this.checked) {
                    document.querySelectorAll('#categoryFilters .filter-option:not(#cat_all)').forEach(c => c.checked = false);
                } else if (this.id !== 'cat_all' && this.checked) {
                    document.getElementById('cat_all').checked = false;
                }
                filterData();
            });
        });
        
        render(restaurants);
    </script>
</body>
</html>