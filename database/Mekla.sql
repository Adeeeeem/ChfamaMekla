-- =============================================================================
-- CHFAMA MEKLA? - DATABASE SCHEMA
-- PostgreSQL 13+
-- Tunisian Restaurant Discovery Webapp
-- =============================================================================
--
-- Run with:
--   psql -U postgres -d Mekla -f Mekla.sql
--
-- =============================================================================

-- Drop existing objects if they exist (in reverse order of dependencies)
DROP TRIGGER IF EXISTS update_restaurant_rating ON public.reviews;
DROP FUNCTION IF EXISTS public.update_restaurant_average_rating();
DROP TABLE IF EXISTS public.restaurant_categories_junction;
DROP TABLE IF EXISTS public.restaurant_dietary_junction;
DROP TABLE IF EXISTS public.reviews;
DROP TABLE IF EXISTS public.restaurant_dietary_options;
DROP TABLE IF EXISTS public.restaurant_categories;
DROP TABLE IF EXISTS public.restaurants;
DROP TABLE IF EXISTS public.places;
DROP TABLE IF EXISTS public.gouvernorats;
DROP TABLE IF EXISTS public.users;

-- =============================================================================
-- TABLE : gouvernorats (no dependencies)
-- Governorates (regions) of Tunisia, including Grand Tunis hierarchy
-- name_ar : Arabic/Tunisian name
-- name_fr : French/English name (same value)
-- =============================================================================
CREATE TABLE public.gouvernorats
(
	id SERIAL PRIMARY KEY,
	name_ar VARCHAR(100) NOT NULL,
	name_fr VARCHAR(100) NOT NULL,
	parent_id INTEGER,

	CONSTRAINT fk_gouvernorats_parent
		FOREIGN KEY (parent_id)
		REFERENCES public.gouvernorats(id)
		ON DELETE RESTRICT
);

-- =============================================================================
-- TABLE : places (depends on gouvernorats)
-- Cities/areas within governorates
-- name_ar : Arabic/Tunisian name
-- name_fr : French/English name (same value)
-- =============================================================================
CREATE TABLE public.places
(
	id SERIAL PRIMARY KEY,
	name_ar VARCHAR(100) NOT NULL,
	name_fr VARCHAR(100) NOT NULL,
	gouvernorat_id INTEGER NOT NULL,

	CONSTRAINT fk_places_gouvernorat
		FOREIGN KEY (gouvernorat_id)
		REFERENCES public.gouvernorats(id)
		ON DELETE RESTRICT
);

-- =============================================================================
-- TABLE : restaurants (depends on places)
-- Main restaurant entity with location and rating info
-- =============================================================================
CREATE TABLE public.restaurants
(
	id SERIAL PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	description TEXT,
	place_id INTEGER NOT NULL,
	address VARCHAR(500),
	average_rating NUMERIC(3,2) DEFAULT 0.00,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

	CONSTRAINT fk_restaurants_place
		FOREIGN KEY (place_id)
		REFERENCES public.places(id)
		ON DELETE RESTRICT
);

-- =============================================================================
-- TABLE : restaurant_categories (no dependencies)
-- Food categories (burger, pizza, spicy, sweet, traditional, etc.)
-- =============================================================================
CREATE TABLE public.restaurant_categories
(
	id SERIAL PRIMARY KEY,
	category_key VARCHAR(50) NOT NULL UNIQUE,

	CONSTRAINT chk_category_key
		CHECK
		(
			category_key IN
			(
				'burger',
				'pizza',
				'spicy',
				'sweet',
				'traditional',
				'sandwich',
				'kebab',
				'seafood',
				'dessert',
				'drink'
			)
		)
);

-- =============================================================================
-- TABLE : restaurant_dietary_options (no dependencies)
-- Dietary options (gluten_free, dairy_free, sugar_free, nut_free, etc.)
-- =============================================================================
CREATE TABLE public.restaurant_dietary_options
(
	id SERIAL PRIMARY KEY,
	dietary_key VARCHAR(50) NOT NULL UNIQUE,

	CONSTRAINT chk_dietary_key
		CHECK
		(
			dietary_key IN
			(
				'gluten_free',
				'dairy_free',
				'sugar_free',
				'nut_free',
				'vegan',
				'vegetarian'
			)
		)
);

-- =============================================================================
-- TABLE : restaurant_categories_junction (depends on restaurants, restaurant_categories)
-- Many-to-many relationship between restaurants and categories
-- =============================================================================
CREATE TABLE public.restaurant_categories_junction
(
	restaurant_id INTEGER NOT NULL,
	category_id INTEGER NOT NULL,
	PRIMARY KEY (restaurant_id, category_id),

	CONSTRAINT fk_rcj_restaurant
		FOREIGN KEY (restaurant_id)
		REFERENCES public.restaurants(id)
		ON DELETE CASCADE,
	CONSTRAINT fk_rcj_category
		FOREIGN KEY (category_id)
		REFERENCES public.restaurant_categories(id)
		ON DELETE CASCADE
);

-- =============================================================================
-- TABLE : restaurant_dietary_junction (depends on restaurants, restaurant_dietary_options)
-- Many-to-many relationship between restaurants and dietary options
-- =============================================================================
CREATE TABLE public.restaurant_dietary_junction
(
	restaurant_id INTEGER NOT NULL,
	dietary_id INTEGER NOT NULL,
	PRIMARY KEY (restaurant_id, dietary_id),

	CONSTRAINT fk_rdj_restaurant
		FOREIGN KEY (restaurant_id)
		REFERENCES public.restaurants(id)
		ON DELETE CASCADE,
	CONSTRAINT fk_rdj_dietary
		FOREIGN KEY (dietary_id)
		REFERENCES public.restaurant_dietary_options(id)
		ON DELETE CASCADE
);

-- =============================================================================
-- TABLE : users (no dependencies)
-- Application users (customers and admins)
-- =============================================================================
CREATE TABLE public.users
(
	id SERIAL PRIMARY KEY,
	email VARCHAR(255) NOT NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	is_admin BOOLEAN DEFAULT FALSE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- TABLE : reviews (depends on restaurants, users)
-- User reviews for restaurants with ratings
-- =============================================================================
CREATE TABLE public.reviews
(
	id SERIAL PRIMARY KEY,
	restaurant_id INTEGER NOT NULL,
	user_id INTEGER NOT NULL,
	rating INTEGER NOT NULL CHECK
	(
		rating BETWEEN 1 AND 5
	),
	comment TEXT,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

	CONSTRAINT fk_reviews_restaurant
		FOREIGN KEY (restaurant_id)
		REFERENCES public.restaurants(id)
		ON DELETE CASCADE,
	CONSTRAINT fk_reviews_user
		FOREIGN KEY (user_id)
		REFERENCES public.users(id)
		ON DELETE CASCADE,
	CONSTRAINT uq_restaurant_user_review
		UNIQUE (restaurant_id, user_id)
);

-- =============================================================================
-- INDEXES : Foreign key columns for query performance
-- =============================================================================
CREATE INDEX idx_gouvernorats_parent_id ON public.gouvernorats(parent_id);
CREATE INDEX idx_places_gouvernorat_id ON public.places(gouvernorat_id);
CREATE INDEX idx_restaurants_place_id ON public.restaurants(place_id);
CREATE INDEX idx_restaurant_categories_junction_restaurant_id ON public.restaurant_categories_junction(restaurant_id);
CREATE INDEX idx_restaurant_categories_junction_category_id ON public.restaurant_categories_junction(category_id);
CREATE INDEX idx_restaurant_dietary_junction_restaurant_id ON public.restaurant_dietary_junction(restaurant_id);
CREATE INDEX idx_restaurant_dietary_junction_dietary_id ON public.restaurant_dietary_junction(dietary_id);
CREATE INDEX idx_reviews_restaurant_id ON public.reviews(restaurant_id);
CREATE INDEX idx_reviews_user_id ON public.reviews(user_id);

-- =============================================================================
-- FUNCTION : update_restaurant_average_rating
-- Trigger function to update restaurant's average_rating when reviews change
-- =============================================================================
CREATE OR REPLACE FUNCTION public.update_restaurant_average_rating()
RETURNS TRIGGER AS $$
BEGIN
	UPDATE public.restaurants
	SET average_rating =
	(
		SELECT COALESCE(ROUND(AVG(rating)::numeric, 2), 0.00)
		FROM public.reviews
		WHERE restaurant_id = COALESCE(NEW.restaurant_id, OLD.restaurant_id)
	)
	WHERE id = COALESCE(NEW.restaurant_id, OLD.restaurant_id);

	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- =============================================================================
-- TRIGGER : update_restaurant_rating
-- Automatically updates restaurant rating when reviews are inserted, updated, or deleted
-- =============================================================================
CREATE TRIGGER update_restaurant_rating
AFTER INSERT OR UPDATE OR DELETE ON public.reviews
FOR EACH ROW
EXECUTE FUNCTION public.update_restaurant_average_rating();

-- =============================================================================
-- INITIAL DATA : Tunisian Governorates (24 governorates + Grand Tunis)
-- Grand Tunis (Tunis + Ariana + Ben Arous + Manouba) as parent region
-- name_ar : Arabic/Tunisian name
-- name_fr : French/English name
-- =============================================================================
INSERT INTO public.gouvernorats (name_ar, name_fr) VALUES
(
	'تونس الكبرى',
	'Grand Tunis'
),
(
	'أريانة',
	'Ariana'
),
(
	'باجة',
	'Béja'
),
(
	'بن عروس',
	'Ben Arous'
),
(
	'بسكرة',
	'Bizerte'
),
(
	'قفصى',
	'Gabès'
),
(
	'قفص',
	'Gafsa'
),
(
	'جندوبة',
	'Jendouba'
),
(
	'القيروان',
	'Kairouan'
),
(
	'القصرين',
	'Kasserine'
),
(
	'قبلي',
	'Kebili'
),
(
	'الكاف',
	'Kef'
),
(
	'المهدية',
	'Mahdia'
),
(
	'منوبة',
	'Manouba'
),
(
	'مدنين',
	'Medenine'
),
(
	'المنستير',
	'Monastir'
),
(
	'نابل',
	'Nabeul'
),
(
	'صفاقس',
	'Sfax'
),
(
	'سيدي بوزيد',
	'Sidi Bouzid'
),
(
	'سليانة',
	'Siliana'
),
(
	'سوسة',
	'Sousse'
),
(
	'تطاوين',
	'Tataouine'
),
(
	'توزر',
	'Tozeur'
),
(
	'تونس',
	'Tunis'
),
(
	'زغوان',
	'Zaghouan'
);

-- Link Grand Tunis governorates to parent
UPDATE public.gouvernorats
SET parent_id = 1
WHERE name_fr IN
(
	'Tunis',
	'Ariana',
	'Ben Arous',
	'Manouba'
);

-- =============================================================================
-- INITIAL DATA : Restaurant Categories
-- =============================================================================
INSERT INTO public.restaurant_categories (category_key) VALUES
('burger'),
('pizza'),
('spicy'),
('sweet'),
('traditional'),
('sandwich'),
('kebab'),
('seafood'),
('dessert'),
('drink');

-- =============================================================================
-- INITIAL DATA : Dietary Options
-- =============================================================================
INSERT INTO public.restaurant_dietary_options (dietary_key) VALUES
('gluten_free'),
('dairy_free'),
('sugar_free'),
('nut_free'),
('vegan'),
('vegetarian');