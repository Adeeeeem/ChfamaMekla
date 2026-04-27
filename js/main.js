/**
 * MAIN JAVASCRIPT - Chfama Mekla?
 */

$(document).ready(function() {
    initApp();
});

function initApp() {
    // Immediately hide preloader
    $('#preloader').fadeOut(300, function() {
        $(this).hide();
    });

    // Load filters and restaurants
    loadFilters();
    loadRestaurants();
}

function loadFilters() {
    // Populate governorates
    var governorates = ['Tunis', 'Ariana', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte', 'Gabès', 'Nabeul'];
    governorates.forEach(function(g) {
        $('#governorateFilter').append('<option value="' + g + '">' + g + '</option>');
    });

    // Populate categories
    var categories = ['Burger', 'Pizza', 'Traditional', 'Kebab', 'Seafood', 'Sandwich', 'Spicy', 'Dessert', 'Drink'];
    categories.forEach(function(c) {
        $('#categoryFilter').append('<option value="' + c + '">' + c + '</option>');
    });

    // Populate dietary
    var dietary = ['Vegetarian', 'Vegan', 'Gluten-Free', 'Nut-Free'];
    dietary.forEach(function(d) {
        $('#dietaryFilter').append('<option value="' + d + '">' + d + '</option>');
    });
}

function loadRestaurants() {
    var restaurants = [
        { id: 1, name: 'Le Chef', place: 'Tunis', governorate: 'Tunis', rating: 4.5, category: 'Traditional' },
        { id: 2, name: 'Pizza Palace', place: 'Sfax', governorate: 'Sfax', rating: 4.8, category: 'Pizza' },
        { id: 3, name: 'Burger House', place: 'Ariana', governorate: 'Ariana', rating: 4.2, category: 'Burger' },
        { id: 4, name: 'Kebab Elite', place: 'Sousse', governorate: 'Sousse', rating: 4.6, category: 'Kebab' },
        { id: 5, name: 'Sea Fresh', place: 'Nabeul', governorate: 'Nabeul', rating: 4.4, category: 'Seafood' },
        { id: 6, name: 'Spicy House', place: 'Kairouan', governorate: 'Kairouan', rating: 4.1, category: 'Spicy' }
    ];

    renderRestaurants(restaurants);
}

function renderRestaurants(restaurants) {
    var $grid = $('#restaurantsGrid');
    var icons = {
        'Traditional': '🥙',
        'Pizza': '🍕',
        'Burger': '🍔',
        'Kebab': '🥩',
        'Seafood': '🦐',
        'Sandwich': '🥪',
        'Spicy': '🌶️',
        'Dessert': '🍰',
        'Drink': '🥤'
    };

    $grid.html('');

    if (restaurants.length === 0) {
        $('#emptyState').removeClass('hidden');
        $('#resultsCount').text('0');
        return;
    }

    $('#emptyState').addClass('hidden');
    $('#resultsCount').text(restaurants.length);

    restaurants.forEach(function(r, index) {
        var icon = icons[r.category] || '🍽️';
        var card = $(
            '<div class="restaurant-card" style="opacity:0">' +
                '<div class="restaurant-image">' +
                    '<span class="restaurant-icon">' + icon + '</span>' +
                    '<span class="restaurant-badge">' + r.category + '</span>' +
                '</div>' +
                '<div class="restaurant-info">' +
                    '<h3 class="restaurant-name">' + r.name + '</h3>' +
                    '<p class="restaurant-location">📍 ' + r.place + ', ' + r.governorate + '</p>' +
                    '<div class="restaurant-footer">' +
                        '<span class="restaurant-rating">⭐ ' + r.rating + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        $grid.append(card);

        // Staggered fade in
        setTimeout(function() {
            card.animate({ opacity: 1 }, 300);
        }, index * 100);
    });
}

// Filter handlers
$('#governorateFilter, #categoryFilter, #dietaryFilter').on('change', function() {
    // TODO: Implement real filtering with AJAX
});

$('#searchInput').on('keyup', function() {
    var query = $(this).val().toLowerCase();
    $('.restaurant-card').each(function() {
        var name = $(this).find('.restaurant-name').text().toLowerCase();
        $(this).toggle(name.indexOf(query) > -1);
    });
});