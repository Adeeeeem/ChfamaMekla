/**
 * =============================================================================
 * MAIN JAVASCRIPT - Chfama Mekla?
 * =============================================================================
 * Restaurant discovery app functionality
 * =============================================================================
 */

$(document).ready(function()
{
    // Initialize app
    initApp();
});

function initApp()
{
    // Hide preloader after page load
    $(window).on('load', function()
    {
        setTimeout(function()
        {
            $('#preloader').addClass('loaded');
            setTimeout(function()
            {
                $('#preloader').hide();
            }, 500);
        }, 1000);
    });

    // Navbar scroll effect
    $(window).on('scroll', function()
    {
        if ($(this).scrollTop() > 50)
        {
            $('.navbar').addClass('scrolled');
        }
        else
        {
            $('.navbar').removeClass('scrolled');
        }
    });

    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e)
    {
        const target = $(this).attr('href');
        if (target === '#') return;
        
        e.preventDefault();
        const offset = 70;
        $('html, body').animate(
        {
            scrollTop: $(target).offset().top - offset
        }, 800, 'easeInOutQuad');
    });

    // Search functionality (placeholder)
    $('.search-btn').on('click', function()
    {
        const query = $('.search-input').val().trim();
        if (query)
        {
            console.log('Searching for:', query);
            // TODO: Implement search
        }
    });

    // Category card click
    $('.category-card').on('click', function()
    {
        const category = $(this).data('category');
        console.log('Selected category:', category);
        // TODO: Filter restaurants by category
    });

    // Load restaurants (AJAX placeholder)
    loadRestaurants();
}

function loadRestaurants()
{
    const $container = $('#restaurantsList');
    
    // Simulated restaurant data (will be replaced with AJAX)
    const restaurants = [
    {
        id: 1,
        name: 'Le Chef',
        place: 'Tunis',
        governorate: 'Tunis',
        rating: 4.5,
        category: 'Traditional'
    },
    {
        id: 2,
        name: 'Pizza Palace',
        place: 'Ariana',
        governorate: 'Ariana',
        rating: 4.8,
        category: 'Pizza'
    },
    {
        id: 3,
        name: 'Burger House',
        place: 'Sfax',
        governorate: 'Sfax',
        rating: 4.2,
        category: 'Burger'
    },
    {
        id: 4,
        name: 'Kebab Elite',
        place: 'Sousse',
        governorate: 'Sousse',
        rating: 4.6,
        category: 'Kebab'
    }
    ];

    // Render restaurant cards
    setTimeout(function()
    {
        $container.html('');
        
        restaurants.forEach(function(restaurant, index)
        {
            const card = createRestaurantCard(restaurant);
            $container.append(card);
            
            // Staggered animation
            setTimeout(function()
            {
                card.fadeIn(400);
            }, index * 100);
        });
    }, 500);
}

function createRestaurantCard(restaurant)
{
    const icons = {
        'Traditional': '🥙',
        'Pizza': '🍕',
        'Burger': '🍔',
        'Kebab': '🥩',
        'Seafood': '🦐',
        'Dessert': '🍰',
        'Sandwich': '🥪',
        'Spicy': '🌶️',
        'Sweet': '🍰',
        'Drink': '🥤'
    };
    
    const icon = icons[restaurant.category] || '🍽️';
    
    const card = $(`
        <div class="restaurant-card">
            <div class="restaurant-image">
                ${icon}
                <span class="restaurant-badge">${restaurant.category}</span>
            </div>
            <div class="restaurant-info">
                <h3 class="restaurant-name">${restaurant.name}</h3>
                <div class="restaurant-location">
                    <span>📍</span>
                    ${restaurant.place}, ${restaurant.governorate}
                </div>
                <div class="restaurant-meta">
                    <div class="restaurant-rating">⭐ ${restaurant.rating}</div>
                </div>
            </div>
        </div>
    `);
    
    // Click handler
    card.on('click', function()
    {
        console.log('View restaurant:', restaurant.id);
        // TODO: Navigate to restaurant details
    });
    
    return card;
}

// Easing function
$.easing.easeInOutQuad = function(x, t, b, c, d)
{
    t /= d / 2;
    if (t < 1) return c / 2 * t * t + b;
    t--;
    return -c / 2 * (t * (t - 2) - 1) + b;
};